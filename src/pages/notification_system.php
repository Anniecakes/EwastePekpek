<?php
// notification_system.php - Core notification handling system

// Database connection (use your actual connection parameters)
function getDbConnection()
{
    global $conn;

    // If there's already a connection, use it
    if (isset($conn) && $conn instanceof mysqli) {
        return $conn;
    }

    // Otherwise create a new connection (should use the same connection info as db_connect.php)
    $conn = new mysqli("localhost", "root", "", "ewaste_db");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Create a new notification
function createNotification($user_id, $type, $message, $related_id = null, $additional_data = null)
{
    $conn = getDbConnection();

    // Convert additional_data to JSON if it exists
    $json_data = $additional_data ? json_encode($additional_data) : null;

    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message, related_id, additional_data, created_at, is_read) 
                           VALUES (?, ?, ?, ?, ?, NOW(), 0)");
    $stmt->bind_param("issss", $user_id, $type, $message, $related_id, $json_data);

    $result = $stmt->execute();
    $notification_id = $result ? $conn->insert_id : null;

    $stmt->close();
    $conn->close();

    return $notification_id;
}

// Get unread notifications for a user
function getUnreadNotifications($user_id)
{
    $conn = getDbConnection();

    $stmt = $conn->prepare("SELECT * FROM notifications 
                           WHERE user_id = ? AND is_read = 0 
                           ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $notifications = [];

    while ($row = $result->fetch_assoc()) {
        // Convert JSON data back to array if it exists
        if ($row['additional_data']) {
            $row['additional_data'] = json_decode($row['additional_data'], true);
        }
        $notifications[] = $row;
    }

    $stmt->close();
    $conn->close();

    return $notifications;
}

// Get all notifications for a user (with pagination)
function getAllNotifications($user_id, $page = 1, $limit = 10)
{
    $conn = getDbConnection();

    $offset = ($page - 1) * $limit;

    $stmt = $conn->prepare("SELECT * FROM notifications 
                           WHERE user_id = ? 
                           ORDER BY created_at DESC 
                           LIMIT ? OFFSET ?");
    $stmt->bind_param("iii", $user_id, $limit, $offset);
    $stmt->execute();

    $result = $stmt->get_result();
    $notifications = [];

    while ($row = $result->fetch_assoc()) {
        // Convert JSON data back to array if it exists
        if ($row['additional_data']) {
            $row['additional_data'] = json_decode($row['additional_data'], true);
        }
        $notifications[] = $row;
    }

    // Get total count for pagination
    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ?");
    $countStmt->bind_param("i", $user_id);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalCount = $countResult->fetch_assoc()['total'];

    $countStmt->close();
    $stmt->close();
    $conn->close();

    return [
        'notifications' => $notifications,
        'total' => $totalCount,
        'pages' => ceil($totalCount / $limit),
        'current_page' => $page
    ];
}

// Mark a notification as read
function markNotificationAsRead($notification_id, $user_id)
{
    $conn = getDbConnection();

    // Ensure the notification belongs to the user (security)
    $stmt = $conn->prepare("UPDATE notifications 
                           SET is_read = 1 
                           WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();

    $affected = $stmt->affected_rows;

    $stmt->close();
    $conn->close();

    return $affected > 0;
}

// Mark all notifications as read for a user
function markAllNotificationsAsRead($user_id)
{
    $conn = getDbConnection();

    $stmt = $conn->prepare("UPDATE notifications 
                           SET is_read = 1 
                           WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $affected = $stmt->affected_rows;

    $stmt->close();
    $conn->close();

    return $affected;
}

// Delete a notification
function deleteNotification($notification_id, $user_id)
{
    $conn = getDbConnection();

    // Ensure the notification belongs to the user (security)
    $stmt = $conn->prepare("DELETE FROM notifications 
                           WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();

    $affected = $stmt->affected_rows;

    $stmt->close();
    $conn->close();

    return $affected > 0;
}

// Create order update notification
function notifyOrderUpdate($order_id, $status, $message = null)
{
    $conn = getDbConnection();

    // Get user_id from order (adjust query to match your actual table structure)
    $stmt = $conn->prepare("SELECT user_id FROM orders WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $user_id = $row['user_id'];

        // Create default message if none provided
        if (!$message) {
            $message = "Your order #$order_id has been updated to: $status";
        }

        // Additional data for the notification
        $additional_data = [
            'order_id' => $order_id,
            'status' => $status
        ];

        // Create the notification
        createNotification($user_id, 'order_update', $message, $order_id, $additional_data);

        $stmt->close();
        return true;
    }

    $stmt->close();
    return false;
}

// Create product update notification
function notifyProductUpdate($product_id, $update_type, $message = null)
{
    $conn = getDbConnection();

    // Get user ID and product name from product_sell
    $stmt = $conn->prepare("SELECT seller_id, product_name FROM product_sell WHERE listing_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $seller_id = $row['seller_id'];
        $product_name = $row['product_name'];

        // Create default message if none provided
        if (!$message) {
            switch ($update_type) {
                case 'sold':
                    $message = "Your product \"$product_name\" has been sold!";
                    break;
                case 'view_increase':
                    $message = "Your product \"$product_name\" is getting attention! Views have increased.";
                    break;
                case 'interest':
                    $message = "Someone has shown interest in your product \"$product_name\".";
                    break;
                case 'price_update':
                    $message = "The price for your product \"$product_name\" has been updated.";
                    break;
                case 'status_change':
                    $message = "Your product \"$product_name\" status has been updated.";
                    break;
                case 'comment':
                    $message = "Someone commented on your product \"$product_name\".";
                    break;
                default:
                    $message = "Your product \"$product_name\" has been updated.";
                    break;
            }
        }

        // Additional data for the notification
        $additional_data = [
            'product_id' => $product_id,
            'product_name' => $product_name,
            'update_type' => $update_type
        ];

        // Create the notification
        createNotification($seller_id, 'product_update', $message, $product_id, $additional_data);

        $stmt->close();
        $conn->close();
        return true;
    }

    $stmt->close();
    $conn->close();
    return false;
}

// Check for new notifications (for AJAX calls)
function checkNewNotifications($user_id, $last_check_time)
{
    $conn = getDbConnection();

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications 
                           WHERE user_id = ? AND created_at > ? AND is_read = 0");
    $stmt->bind_param("is", $user_id, $last_check_time);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $count = $row['count'];

    $stmt->close();
    $conn->close();

    return $count;
}

// When updating an order status
function updateOrderStatus($order_id, $new_status)
{
    $conn = getDbConnection();

    // Your existing order update code
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    $result = $stmt->execute();

    if ($result) {
        // Create notification about the order update
        notifyOrderUpdate($order_id, $new_status);
    }

    $stmt->close();
    $conn->close();

    return $result;
}

// When a product status changes (e.g., it's approved or rejected)
function updateProductStatus($product_id, $new_status)
{
    $conn = getDbConnection();

    // Update product status
    $stmt = $conn->prepare("UPDATE product_sell SET product_status = ? WHERE listing_id = ?");
    $stmt->bind_param("si", $new_status, $product_id);
    $result = $stmt->execute();

    if ($result) {
        // Create notification about the status change
        $message = "Your product status has been updated to: $new_status";
        notifyProductUpdate($product_id, 'status_change', $message);
    }

    $stmt->close();
    $conn->close();

    return $result;
}

// When a product is sold
function markProductAsSold($product_id)
{
    $conn = getDbConnection();

    // Update product status to indicate it's sold (you might have a specific status for this)
    $sold_status = "Sold"; // Adjust according to your actual status values
    $stmt = $conn->prepare("UPDATE product_sell SET product_status = ? WHERE listing_id = ?");
    $stmt->bind_param("si", $sold_status, $product_id);
    $result = $stmt->execute();

    if ($result) {
        // Create notification that the product was sold
        notifyProductUpdate($product_id, 'sold');
    }

    $stmt->close();
    $conn->close();

    return $result;
}

// When a product price is updated
function updateProductPrice($product_id, $new_price)
{
    $conn = getDbConnection();

    // Update product price
    $stmt = $conn->prepare("UPDATE product_sell SET product_price = ?, updated_at = NOW() WHERE listing_id = ?");
    $stmt->bind_param("di", $new_price, $product_id);
    $result = $stmt->execute();

    if ($result) {
        // Create notification about the price update
        $message = "Your product price has been updated to: $" . number_format($new_price, 2);
        notifyProductUpdate($product_id, 'price_update', $message);
    }

    $stmt->close();
    $conn->close();

    return $result;
}

// When someone comments on a product
function addCommentToProduct($product_id, $user_id, $comment_text)
{
    $conn = getDbConnection();

    // Your comment adding code - adjust table name as needed
    $stmt = $conn->prepare("INSERT INTO product_comments (product_id, user_id, comment, created_at) 
                           VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $product_id, $user_id, $comment_text);
    $result = $stmt->execute();

    if ($result) {
        // Get the product seller
        $sellerStmt = $conn->prepare("SELECT seller_id FROM product_sell WHERE listing_id = ?");
        $sellerStmt->bind_param("i", $product_id);
        $sellerStmt->execute();
        $sellerResult = $sellerStmt->get_result();

        if ($row = $sellerResult->fetch_assoc()) {
            $seller_id = $row['seller_id'];

            // Only notify if the commenter is not the seller
            if ($seller_id != $user_id) {
                // Create notification about the new comment
                notifyProductUpdate($product_id, 'comment');
            }
        }
        $sellerStmt->close();
    }

    $stmt->close();
    $conn->close();

    return $result;
}

// Track product views and notify seller when views increase significantly
function incrementProductViews($product_id, $view_threshold = 10)
{
    $conn = getDbConnection();

    // Assuming you have a product_views table or similar
    // First, increment the view count
    $stmt = $conn->prepare("INSERT INTO product_views (product_id, view_date) VALUES (?, NOW())");
    $stmt->bind_param("i", $product_id);
    $result = $stmt->execute();

    if ($result) {
        // Check if view count has reached a threshold
        $viewStmt = $conn->prepare("SELECT COUNT(*) as view_count FROM product_views WHERE product_id = ?");
        $viewStmt->bind_param("i", $product_id);
        $viewStmt->execute();
        $viewResult = $viewStmt->get_result();
        $viewCount = $viewResult->fetch_assoc()['view_count'];
        
        // If views are at a threshold (e.g., 10, 50, 100), notify the seller
        if ($viewCount % $view_threshold === 0) {
            notifyProductUpdate($product_id, 'view_increase', "Your product has reached $viewCount views!");
        }
        
        $viewStmt->close();
    }

    $stmt->close();
    $conn->close();

    return $result;
}

// API endpoint for fetching notifications
if (basename($_SERVER['SCRIPT_NAME']) == 'notification_system.php' && isset($_GET['action'])) {
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $action = $_GET['action'] ?? 'get_unread';

    header('Content-Type: application/json');

    switch ($action) {
        case 'get_unread':
            $notifications = getUnreadNotifications($user_id);
            echo json_encode(['success' => true, 'notifications' => $notifications]);
            break;

        case 'get_all':
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            $result = getAllNotifications($user_id, $page, $limit);
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        case 'mark_read':
            $notification_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($notification_id > 0) {
                $success = markNotificationAsRead($notification_id, $user_id);
                echo json_encode(['success' => $success]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
            }
            break;

        case 'mark_all_read':
            $count = markAllNotificationsAsRead($user_id);
            echo json_encode(['success' => true, 'count' => $count]);
            break;

        case 'delete':
            $notification_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($notification_id > 0) {
                $success = deleteNotification($notification_id, $user_id);
                echo json_encode(['success' => $success]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Invalid notification ID']);
            }
            break;

        case 'check_new':
            $last_check = $_GET['last_check'] ?? date('Y-m-d H:i:s', strtotime('-1 hour'));
            $count = checkNewNotifications($user_id, $last_check);
            echo json_encode(['success' => true, 'count' => $count, 'timestamp' => date('Y-m-d H:i:s')]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
    exit;
}