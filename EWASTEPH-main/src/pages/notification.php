<?php
// notifications.php - Full page for viewing all notifications

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Include notification system
require_once 'notification_system.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$user_id = $_SESSION['user_id'];
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$notificationData = getAllNotifications($user_id, $page, 15);
$notifications = $notificationData['notifications'];
$totalPages = $notificationData['pages'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Notifications</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Styling */
        :root {
            --primary-color: #2e7d32;
            --primary-hover: #1b5e20;
            --secondary-color: #2196F3;
            --text-color: #333;
            --text-light: #666;
            --border-color: #e0e0e0;
            --bg-color: #f9f9f9;
            --card-bg: #ffffff;
            --shadow: 0 2px 8px rgba(0,0,0,0.1);
            --border-radius: 8px;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            background-color: var(--bg-color);
            color: var(--text-color);
            padding-bottom: 30px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        h1 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .mark-all-btn {
            background-color: var(--secondary-color);
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            font-size: 14px;
            padding: 8px 16px;
            transition: background-color 0.2s;
        }
        
        .mark-all-btn:hover {
            background-color: #1976D2;
        }
        
        .notification-list {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .notification-item {
            border-bottom: 1px solid var(--border-color);
            display: flex;
            padding: 16px 20px;
            transition: background-color 0.2s;
        }
        
        .notification-item:hover {
            background-color: #f5f5f5;
        }
        
        .notification-item.unread {
            background-color: #f5f9ff;
        }
        
        .notification-icon {
            background-color: #e3f2fd;
            border-radius: 50%;
            color: #2196F3;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 16px;
            height: 48px;
            margin-right: 16px;
            width: 48px;
        }
        
        .notification-item[data-type="order_update"] .notification-icon {
            background-color: #e8f5e9;
            color: #4CAF50;
        }
        
        .notification-item[data-type="listing_update"] .notification-icon {
            background-color: #fff8e1;
            color: #FFC107;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-message {
            font-size: 15px;
            margin-bottom: 6px;
        }
        
        .notification-time {
            color: var(--text-light);
            font-size: 13px;
        }
        
        .notification-actions {
            display: flex;
            align-items: center;
        }
        
        .notification-mark-read {
            background: none;
            border: none;
            color: #bbb;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
            transition: color 0.2s;
        }
        
        .notification-mark-read:hover {
            color: #2196F3;
        }
        
        .no-notifications {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 30px;
            text-align: center;
        }
        
        .no-notifications i {
            color: #ddd;
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        .no-notifications h3 {
            color: #888;
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .no-notifications p {
            color: #aaa;
            font-size: 14px;
            max-width: 350px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 25px;
        }
        
        .pagination a, .pagination span {
            border: 1px solid var(--border-color);
            color: var(--text-color);
            display: inline-block;
            margin: 0 3px;
            padding: 8px 12px;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .pagination a:hover {
            background-color: #f0f0f0;
        }
        
        .pagination .active {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            color: white;
        }
        
        .pagination .disabled {
            color: #ccc;
            cursor: not-allowed;
        }
        
        /* Notification dropdown and toasts styling */
        .notification-dropdown {
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            max-height: 400px;
            min-width: 300px;
            overflow-y: auto;
            position: absolute;
            right: 0;
            top: 100%;
            z-index: 1000;
        }
        
        .notification-counter {
            align-items: center;
            background-color: #f44336;
            border-radius: 50%;
            color: white;
            display: flex;
            font-size: 10px;
            height: 18px;
            justify-content: center;
            position: absolute;
            right: -5px;
            top: -5px;
            width: 18px;
        }
        
        .notification-toasts {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 350px;
        }
        
        .notification-toast {
            background: white;
            border-left: 4px solid #4CAF50;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 10px;
            max-width: 350px;
            opacity: 0;
            padding: 12px;
            transition: opacity 0.3s ease;
            cursor: pointer;
        }
        
        .toast-header {
            align-items: center;
            display: flex;
            margin-bottom: 8px;
        }
        
        .toast-icon {
            background-color: #e3f2fd;
            border-radius: 50%;
            color: #2196F3;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            height: 24px;
            margin-right: 8px;
            width: 24px;
        }
        
        .toast-title {
            flex: 1;
            font-weight: 600;
            font-size: 14px;
        }
        
        .toast-close {
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 16px;
            padding: 0 5px;
        }
        
        .toast-body {
            font-size: 13px;
            line-height: 1.4;
        }
        
        /* Animation for new/removed notifications */
        .notification-item.read {
            animation: fadeOut 0.3s forwards;
        }
        
        @keyframes fadeOut {
            to {
                opacity: 0;
                height: 0;
                padding: 0;
                margin: 0;
                border: 0;
            }
        }
        
        /* Responsive adjustments */
        @media (max-width: 576px) {
            .notification-item {
                flex-direction: column;
            }
            
            .notification-icon {
                margin-bottom: 10px;
            }
            
            .notification-actions {
                margin-top: 10px;
                justify-content: flex-end;
            }
            
            .notification-dropdown {
                left: 0;
                min-width: 100%;
                position: fixed;
                top: 60px;
            }
            
            .notification-toasts {
                left: 20px;
                right: 20px;
                max-width: calc(100% - 40px);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="notifications-header">
            <h1>Notifications</h1>
            <?php if (count($notifications) > 0): ?>
            <button class="mark-all-btn" id="mark-all-btn">
                <i class="fas fa-check-double"></i> Mark all as read
            </button>
            <?php endif; ?>
        </div>
        
        <div class="notification-list">
            <?php if (count($notifications) > 0): ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>" 
                         data-id="<?php echo $notification['id']; ?>"
                         data-type="<?php echo htmlspecialchars($notification['type']); ?>"
                         data-additional='<?php echo htmlspecialchars(json_encode($notification['additional_data'] ?? new stdClass())); ?>'>
                        <div class="notification-icon">
                            <?php
                            $icon = '';
                            switch ($notification['type']) {
                                case 'order_update':
                                    $icon = '<i class="fas fa-box"></i>';
                                    break;
                                case 'listing_update':
                                    $icon = '<i class="fas fa-tag"></i>';
                                    break;
                                case 'system':
                                    $icon = '<i class="fas fa-bell"></i>';
                                    break;
                                case 'message':
                                    $icon = '<i class="fas fa-envelope"></i>';
                                    break;
                                default:
                                    $icon = '<i class="fas fa-info-circle"></i>';
                                    break;
                            }
                            echo $icon;
                            ?>
                        </div>
                        <div class="notification-content">
                            <div class="notification-message">
                                <?php echo htmlspecialchars($notification['message']); ?>
                            </div>
                            <div class="notification-time" data-timestamp="<?php echo $notification['created_at']; ?>">
                                <?php
                                $date = new DateTime($notification['created_at']);
                                echo $date->format('M j, Y • g:i A');
                                ?>
                            </div>
                        </div>
                        <div class="notification-actions">
                            <?php if (!$notification['is_read']): ?>
                            <button class="notification-mark-read" data-id="<?php echo $notification['id']; ?>" title="Mark as read">
                                <i class="fas fa-check"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-notifications">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No notifications yet</h3>
                    <p>When you receive notifications about your orders or listings, they will appear here</p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>">&laquo; Previous</a>
            <?php else: ?>
                <span class="disabled">&laquo; Previous</span>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>">Next &raquo;</a>
            <?php else: ?>
                <span class="disabled">Next &raquo;</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    /**
     * NotificationManager class for handling all notification functionality
     */
    class NotificationManager {
        constructor(options = {}) {
            this.options = {
                checkInterval: options.checkInterval || 60000, // 1 minute by default
                notificationContainer: options.notificationContainer || '#notification-dropdown',
                counterElement: options.counterElement || '#notification-counter',
                toastContainer: options.toastContainer || '#notification-toasts',
                apiEndpoint: options.apiEndpoint || 'notification_system.php',
                showToasts: options.showToasts !== undefined ? options.showToasts : true,
                ...options
            };
            
            this.lastCheckTime = new Date().toISOString();
            this.notificationCount = 0;
            this.isDropdownOpen = false;
            
            // Initialize
            this.initializeUI();
            this.startPolling();
        }
        
        initializeUI() {
            // Create toast container if it doesn't exist
            if (this.options.showToasts && !document.querySelector(this.options.toastContainer)) {
                const toastContainer = document.createElement('div');
                toastContainer.id = this.options.toastContainer.replace('#', '');
                toastContainer.className = 'notification-toasts';
                document.body.appendChild(toastContainer);
            }
            
            // Attach event listeners
            document.addEventListener('click', (e) => {
                // Mark notification as read
                if (e.target.closest('.notification-mark-read')) {
                    const button = e.target.closest('.notification-mark-read');
                    const notificationId = button.dataset.id;
                    this.markAsRead(notificationId);
                }
                
                // Mark all as read
                if (e.target.closest('#mark-all-btn')) {
                    this.markAllAsRead();
                }
                
                // Handle notification item click
                if (e.target.closest('.notification-item')) {
                    const notificationItem = e.target.closest('.notification-item');
                    const notificationId = notificationItem.dataset.id;
                    const additionalData = JSON.parse(notificationItem.dataset.additional || '{}');
                    
                    if (!notificationItem.querySelector('.notification-mark-read')) {
                        // If notification is already read, handle the click
                        this.handleNotificationClick(notificationId, additionalData);
                    } else {
                        // If notification is unread, mark it as read first
                        this.markAsRead(notificationId, () => {
                            this.handleNotificationClick(notificationId, additionalData);
                        });
                    }
                }
                
                // Close toast when close button is clicked
                if (e.target.closest('.toast-close')) {
                    const toast = e.target.closest('.notification-toast');
                    this.closeToast(toast);
                }
                
                // Handle toast click
                if (e.target.closest('.notification-toast') && !e.target.closest('.toast-close')) {
                    const toast = e.target.closest('.notification-toast');
                    const notificationId = toast.dataset.id;
                    const additionalData = JSON.parse(toast.dataset.additional || '{}');
                    
                    this.handleNotificationClick(notificationId, additionalData);
                    this.closeToast(toast);
                }
            });
            
            // Format relative timestamps
            this.formatTimestamps();
            
            // Check for new notifications immediately
            this.checkNewNotifications();
        }
        
        startPolling() {
            // Set up interval to check for new notifications
            setInterval(() => {
                this.checkNewNotifications();
            }, this.options.checkInterval);
        }
        
        checkNewNotifications() {
            fetch(`${this.options.apiEndpoint}?action=get_new_notifications&since=${encodeURIComponent(this.lastCheckTime)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.notifications.length > 0) {
                        this.updateNotificationCount(data.unread_count);
                        this.lastCheckTime = new Date().toISOString();
                        
                        // Show toasts for new notifications
                        if (this.options.showToasts) {
                            data.notifications.forEach(notification => {
                                this.showToast(notification);
                            });
                        }
                    }
                })
                .catch(error => {
                    console.error('Error checking for new notifications:', error);
                });
        }
        
        updateNotificationCount(count) {
            this.notificationCount = count;
            
            // Update counter display
            const counterElement = document.querySelector(this.options.counterElement);
            if (counterElement) {
                if (count > 0) {
                    counterElement.textContent = count > 99 ? '99+' : count;
                    counterElement.style.display = 'flex';
                } else {
                    counterElement.style.display = 'none';
                }
            }
        }
        
        markAsRead(notificationId, callback) {
            const notificationItem = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
            
            fetch(`${this.options.apiEndpoint}?action=mark_read&id=${notificationId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI
                        if (notificationItem) {
                            notificationItem.classList.remove('unread');
                            notificationItem.classList.add('read');
                            const markReadBtn = notificationItem.querySelector('.notification-mark-read');
                            if (markReadBtn) {
                                markReadBtn.remove();
                            }
                            
                            // Decrease notification count
                            this.updateNotificationCount(Math.max(0, this.notificationCount - 1));
                            
                            // Execute callback if provided
                            if (typeof callback === 'function') {
                                setTimeout(callback, 300); // Wait for animation to complete
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error marking notification as read:', error);
                });
        }
        
        markAllAsRead() {
            fetch(`${this.options.apiEndpoint}?action=mark_all_read`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI
                        document.querySelectorAll('.notification-item.unread').forEach(item => {
                            item.classList.remove('unread');
                            item.classList.add('read');
                            const markReadBtn = item.querySelector('.notification-mark-read');
                            if (markReadBtn) {
                                markReadBtn.remove();
                            }
                        });
                        
                        // Reset notification count
                        this.updateNotificationCount(0);
                        
                        // Hide mark all button
                        const markAllBtn = document.getElementById('mark-all-btn');
                        if (markAllBtn) {
                            markAllBtn.style.display = 'none';
                        }
                        
                        // Reload page after animation completes
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    }
                })
                .catch(error => {
                    console.error('Error marking all notifications as read:', error);
                });
        }
        
        handleNotificationClick(notificationId, additionalData) {
            // Default behavior: redirect if URL is provided
            if (additionalData && additionalData.url) {
                window.location.href = additionalData.url;
            }
        }
        
        showToast(notification) {
            const toastContainer = document.querySelector(this.options.toastContainer);
            if (!toastContainer) return;
            
            // Create toast element
            const toast = document.createElement('div');
            toast.className = 'notification-toast';
            toast.dataset.id = notification.id;
            toast.dataset.type = notification.type;
            toast.dataset.additional = JSON.stringify(notification.additional_data || {});
            
            // Set border color based on notification type
            switch (notification.type) {
                case 'order_update':
                    toast.style.borderLeftColor = '#4CAF50';
                    break;
                case 'listing_update':
                    toast.style.borderLeftColor = '#FFC107';
                    break;
                case 'system':
                    toast.style.borderLeftColor = '#2196F3';
                    break;
                case 'message':
                    toast.style.borderLeftColor = '#9C27B0';
                    break;
            }
            
            // Get icon based on notification type
            let iconClass = 'fas fa-info-circle';
            switch (notification.type) {
                case 'order_update':
                    iconClass = 'fas fa-box';
                    break;
                case 'listing_update':
                    iconClass = 'fas fa-tag';
                    break;
                case 'system':
                    iconClass = 'fas fa-bell';
                    break;
                case 'message':
                    iconClass = 'fas fa-envelope';
                    break;
            }
            
            // Build toast content
            toast.innerHTML = `
                <div class="toast-header">
                    <div class="toast-icon">
                        <i class="${iconClass}"></i>
                    </div>
                    <div class="toast-title">New notification</div>
                    <button class="toast-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="toast-body">${notification.message}</div>
            `;
            
            // Add to container
            toastContainer.appendChild(toast);
            
            // Show with animation
            setTimeout(() => {
                toast.style.opacity = '1';
            }, 10);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                this.closeToast(toast);
            }, 5000);
        }
        
        closeToast(toast) {
            if (!toast) return;
            
            toast.style.opacity = '0';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }
        
        formatTimestamps() {
            document.querySelectorAll('.notification-time[data-timestamp]').forEach(element => {
                const timestamp = element.dataset.timestamp;
                if (!timestamp) return;
                
                const date = new Date(timestamp);
                const now = new Date();
                const diffMs = now - date;
                const diffSecs = Math.floor(diffMs / 1000);
                const diffMins = Math.floor(diffSecs / 60);
                const diffHours = Math.floor(diffMins / 60);
                const diffDays = Math.floor(diffHours / 24);
                
                // Show relative time if recent, otherwise show date
                if (diffSecs < 60) {
                    element.textContent = 'Just now';
                } else if (diffMins < 60) {
                    element.textContent = `${diffMins} minute${diffMins !== 1 ? 's' : ''} ago`;
                } else if (diffHours < 24) {
                    element.textContent = `${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`;
                } else if (diffDays < 7) {
                    element.textContent = `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`;
                } else {
                    // Keep original formatted date for older notifications
                }
            });
        }
    }
    
    // Initialize notification manager on page load
    document.addEventListener('DOMContentLoaded', () => {
        // Set up notification manager for the notifications page
        const notificationManager = new NotificationManager({
            showToasts: true,
            apiEndpoint: 'notification_system.php'
        });
        
        // Update timestamps every minute
        setInterval(() => {
            notificationManager.formatTimestamps();
        }, 60000);
    });
    </script>
</body>
</html>