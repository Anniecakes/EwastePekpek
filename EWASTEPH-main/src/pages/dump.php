<?php
session_start();
include 'db_connect.php';

// Product Status Constants
define('STATUS_PENDING', 'Pending Review');
define('STATUS_APPROVED', 'Approved');
define('STATUS_REJECTED', 'Rejected');

// Order Status Constants
define('ORDER_STATUS_PENDING', 'Pending');
define('ORDER_STATUS_PROCESSING', 'Processing');
define('ORDER_STATUS_SHIPPED', 'Shipped');
define('ORDER_STATUS_DELIVERED', 'Delivered');
define('ORDER_STATUS_CANCELLED', 'Cancelled');

$conn = $conn ?? new mysqli("localhost", "root", "", "ewaste_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// PRODUCT STATUS UPDATES
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['product_id']) && isset($_POST['action'])) {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

    if ($product_id && in_array($action, ["approve", "reject", "reset"])) {
        switch ($action) {
            case "approve":
                $new_status = STATUS_APPROVED;
                $message_action = "approved";
                break;
            case "reject":
                $new_status = STATUS_REJECTED;
                $message_action = "rejected";
                break;
            case "reset":
                $new_status = STATUS_PENDING;
                $message_action = "reset to pending review";
                break;
        }

        $stmt = $conn->prepare("UPDATE listings SET product_status = ?, updated_at = NOW() WHERE listing_id = ?");
        $stmt->bind_param("si", $new_status, $product_id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Product #$product_id has been successfully $message_action.";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Error updating product: " . $conn->error;
            $_SESSION['message_type'] = 'error';
        }

        $stmt->close();
    }
}

// ORDER STATUS UPDATES
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_id']) && isset($_POST['action'])) {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);

    if ($order_id && $action) {
        $valid_actions = ["process", "ship", "deliver", "cancel", "reset"];
        if (in_array($action, $valid_actions)) {
            switch ($action) {
                case "process":
                    $new_status = ORDER_STATUS_PROCESSING;
                    $message_action = "marked as processing";
                    break;
                case "ship":
                    $new_status = ORDER_STATUS_SHIPPED;
                    $message_action = "marked as shipped";
                    break;
                case "deliver":
                    $new_status = ORDER_STATUS_DELIVERED;
                    $message_action = "marked as delivered";
                    break;
                case "cancel":
                    $new_status = ORDER_STATUS_CANCELLED;
                    $message_action = "cancelled";
                    break;
                case "reset":
                    $new_status = ORDER_STATUS_PENDING;
                    $message_action = "reset to pending";
                    break;
            }

            $stmt = $conn->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE order_id = ?");
            $stmt->bind_param("si", $new_status, $order_id);

            if ($stmt->execute()) {
                $_SESSION['message'] = "Order #$order_id has been successfully $message_action.";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = "Error updating order: " . $conn->error;
                $_SESSION['message_type'] = 'error';
            }

            $stmt->close();
        } else {
            $_SESSION['message'] = "Invalid action specified.";
            $_SESSION['message_type'] = 'error';
        }
    }
}

// ADD PRODUCT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $category = $_POST['category'];
    $image = $_POST['image'];
    $condition = $_POST['condition'];

    $stmt = $conn->prepare("INSERT INTO products (name, price, quantity, category, image, product_condition) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdisss", $name, $price, $quantity, $category, $image, $condition);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Product added successfully!";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Error: " . $stmt->error;
        $_SESSION['message_type'] = 'error';
    }
    $stmt->close();
}

// UPDATE PRODUCT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $product_id = $_POST['product_id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity']; 
    $category = $_POST['category'];
    $image = $_POST['image']; 
    $condition = $_POST['condition'];
    
    $stmt = $conn->prepare("UPDATE products SET name=?, price=?, quantity=?, category=?, image=?, product_condition=? WHERE product_id=?");
    $stmt->bind_param("sdisssi", $name, $price, $quantity, $category, $image, $condition, $product_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Product with ID $product_id updated successfully!";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Error updating product: " . $stmt->error;
        $_SESSION['message_type'] = 'error';
    }
    $stmt->close();
}
// DELETE PRODUCT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    $id = $_POST['product_id'];
    if ($conn->query("DELETE FROM products WHERE product_id=$id")) {
        $_SESSION['message'] = "Product deleted successfully!";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Error deleting product.";
        $_SESSION['message_type'] = 'error';
    }
}
$result = $conn->query("SELECT * FROM products ORDER BY created_at DESC");

$active_view = isset($_GET['view']) ? $_GET['view'] : 'products';
// Set empty product status to pending
$update_empty = $conn->query("UPDATE listings SET product_status = '" . STATUS_PENDING . "' 
                              WHERE product_status = '' OR product_status IS NULL");

if ($update_empty) {
    $affected = $conn->affected_rows;
    if ($affected > 0) {
        $_SESSION['message'] = "$affected products were automatically set to Pending Review status.";
        $_SESSION['message_type'] = 'info';
    }
}

// Navigation and active view handling
$main_section = isset($_GET['section']) ? $_GET['section'] : 'product_review';
$sub_section = isset($_GET['view']) ? $_GET['view'] : 'pending';

// Pagination for listings
$records_per_page = 10;
$page = isset($_GET['page']) ? filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) : 1;
if (!$page) $page = 1;
$offset = ($page - 1) * $records_per_page;

// Product Review Counts
$count_pending = $conn->query("SELECT COUNT(*) as count FROM listings WHERE product_status = '" . STATUS_PENDING . "'")->fetch_assoc()['count'];
$count_approved = $conn->query("SELECT COUNT(*) as count FROM listings WHERE product_status = '" . STATUS_APPROVED . "'")->fetch_assoc()['count'];
$count_rejected = $conn->query("SELECT COUNT(*) as count FROM listings WHERE product_status = '" . STATUS_REJECTED . "'")->fetch_assoc()['count'];

// Order Counts
$order_count_query = "SELECT 
    COUNT(CASE WHEN order_status = '" . ORDER_STATUS_PENDING . "' THEN 1 END) as pending_count,
    COUNT(CASE WHEN order_status = '" . ORDER_STATUS_PROCESSING . "' THEN 1 END) as processing_count,
    COUNT(CASE WHEN order_status = '" . ORDER_STATUS_SHIPPED . "' THEN 1 END) as shipped_count,
    COUNT(CASE WHEN order_status = '" . ORDER_STATUS_DELIVERED . "' THEN 1 END) as delivered_count,
    COUNT(CASE WHEN order_status = '" . ORDER_STATUS_CANCELLED . "' THEN 1 END) as cancelled_count,
    COUNT(*) as total_count
    FROM orders";

$order_counts = $conn->query($order_count_query)->fetch_assoc();

// Query data based on active view
if ($main_section == 'product_review') {
    // Product reviews based on status
    if ($sub_section == 'pending') {
        $products_data = $conn->query("SELECT * FROM listings WHERE product_status = '" . STATUS_PENDING . "' ORDER BY created_at DESC LIMIT $offset, $records_per_page");
        $total_pages = ceil($count_pending / $records_per_page);
    } elseif ($sub_section == 'approved') {
        $products_data = $conn->query("SELECT * FROM listings WHERE product_status = '" . STATUS_APPROVED . "' ORDER BY created_at DESC LIMIT $offset, $records_per_page");
        $total_pages = ceil($count_approved / $records_per_page);
    } elseif ($sub_section == 'rejected') {
        $products_data = $conn->query("SELECT * FROM listings WHERE product_status = '" . STATUS_REJECTED . "' ORDER BY created_at DESC LIMIT $offset, $records_per_page");
        $total_pages = ceil($count_rejected / $records_per_page);
    }
} elseif ($main_section == 'product_management') {
    // Product management
    $products = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
} elseif ($main_section == 'orders') {
    // Orders based on status
    $count_sql = "SELECT COUNT(*) as count FROM orders WHERE ";
    if ($sub_section !== 'all') {
        $order_status = ucfirst($sub_section);
        $count_sql .= "order_status = '$order_status'";
        $orders_data = $conn->query("SELECT o.*, DATE_FORMAT(o.order_date, '%b %d, %Y %h:%i %p') as formatted_date 
                                     FROM orders o 
                                     WHERE o.order_status = '$order_status'
                                     ORDER BY o.order_date DESC LIMIT $offset, $records_per_page");
    } else {
        $count_sql .= "1=1";
        $orders_data = $conn->query("SELECT o.*, DATE_FORMAT(o.order_date, '%b %d, %Y %h:%i %p') as formatted_date 
                                     FROM orders o 
                                     ORDER BY o.order_date DESC LIMIT $offset, $records_per_page");
    }
    $count_total = $conn->query($count_sql)->fetch_assoc()['count'];
    $total_pages = ceil($count_total / $records_per_page);
}

function getProductList($order_id, $conn)
{
    $product_sql = "SELECT * FROM order_items WHERE order_id = ?";
    $stmt = $conn->prepare($product_sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $product_result = $stmt->get_result();

    $product_list = "";
    while ($product = $product_result->fetch_assoc()) {
        $product_list .= '<div class="product-item">' .
            '<span class="quantity">' . $product['quantity'] . 'x</span> ' .
            '<span class="product-name">' . htmlspecialchars($product['product_name']) . '</span>' .
            '</div>';
    }
    return $product_list ? $product_list : "<span class='no-products'>No products</span>";
}

function displayStatus($status)
{
    $status_lower = strtolower(str_replace(' ', '-', $status));
    return '<span class="status-badge ' . $status_lower . '">' . $status . '</span>';
}

function paginationLinks($current_page, $total_pages, $main_section, $sub_section)
{
    $links = '';
    if ($total_pages <= 1) return '';
    $links .= '<div class="pagination">';
    if ($current_page > 1) {
        $links .= '<a href="?section=' . $main_section . '&view=' . $sub_section . '&page=' . ($current_page - 1) . '" class="page-link">&laquo; Previous</a>';
    }
    for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
        if ($i == $current_page) {
            $links .= '<span class="page-link active">' . $i . '</span>';
        } else {
            $links .= '<a href="?section=' . $main_section . '&view=' . $sub_section . '&page=' . $i . '" class="page-link">' . $i . '</a>';
        }
    }

    if ($current_page < $total_pages) {
        $links .= '<a href="?section=' . $main_section . '&view=' . $sub_section . '&page=' . ($current_page + 1) . '" class="page-link">Next &raquo;</a>';
    }
    $links .= '</div>';

    return $links;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Waste Management System - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="test.css">
      
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>E-Waste Admin</h2>
            <p>Management Dashboard</p>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Main Menu</div>
            <a href="?section=product_review&view=pending" class="nav-item <?php echo ($main_section == 'product_review') ? 'active' : ''; ?>">
                <i class="fas fa-box-open"></i>
                <span>Product Management</span>
                <?php if ($count_pending > 0): ?>
                <span class="badge"><?php echo $count_pending; ?></span>
                <?php endif; ?>
            </a>
            <a href="?section=product_management" class="nav-item <?php echo ($main_section == 'product_management') ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i>
                <span>Add Product</span>
            </a>
            <a href="?section=orders&view=all" class="nav-item <?php echo ($main_section == 'orders') ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i>
                <span>Orders</span>
                <?php if ($order_counts['pending_count'] > 0): ?>
                <span class="badge"><?php echo $order_counts['pending_count']; ?></span>
                <?php endif; ?>
            </a>
            <a href="logout.php" class="nav-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <h1>
                <?php if ($main_section == 'product_review'): ?>
                    Product Review Dashboard
                <?php elseif ($main_section == 'product_management'): ?>
                    Product Management
                <?php elseif ($main_section == 'orders'): ?>
                    Order Management
                <?php endif; ?>
            </h1>
            <div class="user-info">
                <span>Welcome, Admin</span>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="flash-message <?php echo $_SESSION['message_type']; ?>">
                <i class="fas fa-<?php echo $_SESSION['message_type'] == 'success' ? 'check-circle' : ($_SESSION['message_type'] == 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <!-- Product Review Section -->
        <?php if ($main_section == 'product_review'): ?>
            <!-- Sub-tabs for product review -->
            <div class="sub-tabs">
                <a href="?section=product_review&view=pending" class="sub-tab <?php echo $sub_section == 'pending' ? 'active' : ''; ?>">
                    <i class="fas fa-hourglass"></i> Pending Review
                    <span class="tab-count"><?php echo $count_pending; ?></span>
                </a>
                <a href="?section=product_review&view=approved" class="sub-tab <?php echo $sub_section == 'approved' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> Approved
                    <span class="tab-count"><?php echo $count_approved; ?></span>
                </a>
                <a href="?section=product_review&view=rejected" class="sub-tab <?php echo $sub_section == 'rejected' ? 'active' : ''; ?>">
                    <i class="fas fa-times-circle"></i> Rejected
                    <span class="tab-count"><?php echo $count_rejected; ?></span>
                </a>
            </div>

            <!-- Product Review Table -->
            <?php if ($products_data && $products_data->num_rows > 0): ?>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Description</th>
                        <th>Condition</th>
                        <th>Price (₱)</th>
                        <th>Image</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    <?php while ($product = $products_data->fetch_assoc()): ?>
                        <tr>
                            <td><?= $product['listing_id'] ?></td>
                            <td><?= htmlspecialchars($product['product_name']) ?></td>
                            <td class="truncate"><?= htmlspecialchars($product['product_description']) ?></td>
                            <td><?= htmlspecialchars($product['product_condition']) ?></td>
                            <td>₱<?= number_format($product['product_price'], 2) ?></td>
                            <td>
                                <?php if (!empty($product['product_image'])): ?>
                                    <a href="<?= htmlspecialchars($product['product_image']) ?>" target="_blank">
                                        <i class="fas fa-image"></i> View
                                    </a>
                                <?php else: ?>
                                    <i class="fas fa-ban"></i> N/A
                                <?php endif; ?>
                            </td>
                            <td><?= displayStatus($product['product_status']) ?></td>
                            <td>
                                <?php if ($product['product_status'] == STATUS_PENDING): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="product_id" value="<?= $product['listing_id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-approve">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="product_id" value="<?= $product['listing_id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-reject">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST">
                                        <input type="hidden" name="product_id" value="<?= $product['listing_id'] ?>">
                                        <input type="hidden" name="action" value="reset">
                                        <button type="submit" class="btn btn-reset">
                                            <i class="fas fa-redo"></i> Reset
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
                
                <!-- Pagination -->
                <?php echo paginationLinks($page, $total_pages, $main_section, $sub_section); ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>No Products Found</h3>
                    <p>There are no products in this category at the moment.</p>
                </div>
            <?php endif; ?>
        
        <!-- IMPORTANT: Closed the if statement for product_review section here -->
        <?php endif; ?>

        <!-- Product Management Section - This was incorrectly nested before -->
        <?php if ($main_section == 'product_management'): ?>
            <div class="card">
                <div class="card-header">Add New Product</div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name">Product Name</label>
                                <input type="text" id="name" name="name" class="form-control" placeholder="Enter product name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="price">Price (₱)</label>
                                <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" placeholder="99.99" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="quantity">Quantity</label>
                                <input type="number" id="quantity" name="quantity" class="form-control" min="1" placeholder="Available quantity" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select id="category" name="category" class="form-control" required>
                                    <option value="">Select Category</option>
                                    <option value="1">Electronics</option>
                                    <option value="2">Components</option>
                                    <option value="3">Accessories</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="condition">Condition</label>
                                <select id="condition" name="condition" class="form-control" required>
                                    <option value="">Select Condition</option>
                                    <option value="New">New</option>
                                    <option value="Like New">Like New</option>
                                    <option value="Good">Good</option>
                                    <option value="Fair">Fair</option>
                                    <option value="Poor">Poor</option>
                                    <option value="Refurbished">Refurbished</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="image">Image URL</label>
                                <input type="text" id="image" name="image" class="form-control" placeholder="https://example.com/image.jpg">
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="add" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Product List with Inline Editing -->
            <?php if (isset($products) && $products->num_rows > 0): ?>
                <div class="card mt-4">
                    <div class="card-header">Manage Products</div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Product Name</th>
                                        <th>Category</th>
                                        <th>Price (₱)</th>
                                        <th>Quantity</th>
                                        <th>Condition</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($product = $products->fetch_assoc()): ?>
                                        <tr>
                                            <form method="POST">
                                                <td>
                                                    <?= $product['product_id'] ?>
                                                    <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                                                </td>
                                                <td>
                                                    <?php if (!empty($product['image'])): ?>
                                                        <img src="<?= htmlspecialchars($product['image']) ?>" alt="Product" class="product-image">
                                                    <?php else: ?>
                                                        <span>No image</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" class="form-control">
                                                </td>
                                                <td>
                                                    <select name="category" class="form-control">
                                                        <option value="1" <?= $product['category'] == 1 ? 'selected' : '' ?>>Electronics</option>
                                                        <option value="2" <?= $product['category'] == 2 ? 'selected' : '' ?>>Components</option>
                                                        <option value="3" <?= $product['category'] == 3 ? 'selected' : '' ?>>Accessories</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" name="price" value="<?= $product['price'] ?>" step="0.01" class="form-control input-sm">
                                                </td>
                                                <td>
                                                    <input type="number" name="quantity" value="<?= $product['quantity'] ?>" class="form-control input-sm">
                                                </td>
                                                <td>
                                                    <select name="condition" class="form-control">
                                                        <option value="New" <?= $product['product_condition'] == 'New' ? 'selected' : '' ?>>New</option>
                                                        <option value="Like New" <?= $product['product_condition'] == 'Like New' ? 'selected' : '' ?>>Like New</option>
                                                        <option value="Good" <?= $product['product_condition'] == 'Good' ? 'selected' : '' ?>>Good</option>
                                                        <option value="Fair" <?= $product['product_condition'] == 'Fair' ? 'selected' : '' ?>>Fair</option>
                                                        <option value="Poor" <?= $product['product_condition'] == 'Poor' ? 'selected' : '' ?>>Poor</option>

                                                    </select>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button type="submit" name="update" class="btn btn-sm btn-success">
                                                            <i class="fas fa-save"></i> Update
                                                        </button>
                                                        <button type="submit" name="delete" class="btn btn-sm btn-danger">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </div>
                                                </td>
                                            </form>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-boxes"></i>
                    <h3>No Products Yet</h3>
                    <p>Start by adding new products to your inventory.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
 <!-- Order Management Section -->
 <?php if ($main_section == 'orders'): ?>
            <!-- Sub-tabs for orders -->
            <div class="sub-tabs">
                <a href="?section=orders&view=pending" class="sub-tab <?php echo $sub_section == 'pending' ? 'active' : ''; ?>">


                <?php echo $sub_section == 'pending' ? 'active' : ''; ?>">
                    <i class="fas fa-hourglass"></i> Pending
                    <span class="tab-count"><?php echo $order_counts['pending_count']; ?></span>
                </a>
                <a href="?section=orders&view=processing" class="sub-tab <?php echo $sub_section == 'processing' ? 'active' : ''; ?>">
                    <i class="fas fa-cogs"></i> Processing
                    <span class="tab-count"><?php echo $order_counts['processing_count']; ?></span>
                </a>
                <a href="?section=orders&view=shipped" class="sub-tab <?php echo $sub_section == 'shipped' ? 'active' : ''; ?>">
                    <i class="fas fa-shipping-fast"></i> Shipped
                    <span class="tab-count"><?php echo $order_counts['shipped_count']; ?></span>
                </a>
                <a href="?section=orders&view=delivered" class="sub-tab <?php echo $sub_section == 'delivered' ? 'active' : ''; ?>">
                    <i class="fas fa-check-double"></i> Delivered
                    <span class="tab-count"><?php echo $order_counts['delivered_count']; ?></span>
                </a>
                <a href="?section=orders&view=cancelled" class="sub-tab <?php echo $sub_section == 'cancelled' ? 'active' : ''; ?>">
                    <i class="fas fa-ban"></i> Cancelled
                    <span class="tab-count"><?php echo $order_counts['cancelled_count']; ?></span>
                </a>
                <a href="?section=orders&view=all" class="sub-tab <?php echo $sub_section == 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-th-list"></i> All Orders
                    <span class="tab-count"><?php echo $order_counts['total_count']; ?></span>
                </a>
            </div>

            <!-- Orders Table -->
            <?php if ($orders_data && $orders_data->num_rows > 0): ?>
                <table>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Products</th>
                        <th>Total (₱)</th>
                        <th>Order Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    <?php while ($order = $orders_data->fetch_assoc()): ?>
                        <tr>
                        <td>
                            #<?= $order['order_id'] ?>
                            <span class="date-time"><?= $order['formatted_date'] ?></span>
                        </td>
                        <td><?= htmlspecialchars($order['full_name']) ?></td>
                        <td class="hide-sm"><?= htmlspecialchars($order['phone_number']) ?></td>
                        <td class="address">
                            <?= htmlspecialchars($order['street']) ?>,
                            <?= htmlspecialchars($order['city']) ?>,
                            <?= htmlspecialchars($order['province']) ?>,
                            <?= htmlspecialchars($order['zipcode']) ?>
                        </td>
                        <td><?= getProductList($order['order_id'], $conn) ?></td>
                        <td>₱<?= number_format($order['totalPrice'], 2) ?></td>
                        <td class="hide-sm">
                            <?= htmlspecialchars($order['payment_method']) ?>
                            <?php if (!empty($order['proofOfPayment'])): ?>
                                <br>
                                <a href="<?= htmlspecialchars($order['proofOfPayment']) ?>" target="_blank" class="btn btn-view">
                                    <i class="fas fa-receipt"></i> View Receipt
                                </a>
                            <?php endif; ?>
                        </td>
                            <td><?= displayStatus($order['order_status']) ?></td>
                            <td>
                                <div class="btn-group">
                                    <?php if ($order['order_status'] == ORDER_STATUS_PENDING): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                            <input type="hidden" name="action" value="process">
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="fas fa-cogs"></i> Process
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                            <input type="hidden" name="action" value="cancel">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-ban"></i> Cancel
                                            </button>
                                        </form>
                                    <?php elseif ($order['order_status'] == ORDER_STATUS_PROCESSING): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                            <input type="hidden" name="action" value="ship">
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="fas fa-shipping-fast"></i> Ship
                                            </button>
                                        </form>
                                    <?php elseif ($order['order_status'] == ORDER_STATUS_SHIPPED): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                            <input type="hidden" name="action" value="deliver">
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fas fa-check-double"></i> Mark Delivered
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($order['order_status'] != ORDER_STATUS_CANCELLED && $order['order_status'] != ORDER_STATUS_PENDING): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                            <input type="hidden" name="action" value="reset">
                                            <button type="submit" class="btn btn-sm btn-secondary">
                                                <i class="fas fa-redo"></i> Reset
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
                
                <!-- Pagination -->
                <?php echo paginationLinks($page, $total_pages, $main_section, $sub_section); ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>No Orders Found</h3>
                    <p>There are no orders in this category at the moment.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        // Auto-hide flash messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const flashMessage = document.querySelector('.flash-message');
            if (flashMessage) {
                setTimeout(function() {
                    flashMessage.style.opacity = '0';
                    setTimeout(function() {
                        flashMessage.style.display = 'none';
                    }, 500);
                }, 5000);
            }
        });
    </script>
</body>
</html>
