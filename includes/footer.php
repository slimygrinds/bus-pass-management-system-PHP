<?php
/**
 * Footer Template
 * 
 * Contains the footer section, Bootstrap JS, Chart.js,
 * and custom JavaScript includes. Included in all pages.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */
?>

<?php if (isLoggedIn()): ?>
        </div><!-- /.main-content -->
    </div><!-- /.content-wrapper -->
<?php endif; ?>

<!-- Footer -->
<footer class="footer text-center py-3 <?php echo isLoggedIn() ? 'footer-logged-in' : ''; ?>">
    <div class="container">
        <span class="text-muted">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> | BCA Semester 5 Project</span>
    </div>
</footer>

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js (only on dashboard pages) -->
<?php if (isset($loadChartJs) && $loadChartJs): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.3.0/dist/chart.umd.min.js"></script>
<?php endif; ?>

<!-- Custom JavaScript -->
<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/validation.js"></script>

<?php if (isUser()): ?>
<!-- Load notifications via AJAX for logged in users -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load notifications when dropdown is opened
    const notifDropdown = document.getElementById('notifDropdown');
    if (notifDropdown) {
        notifDropdown.addEventListener('click', function() {
            loadNotifications();
        });
    }
});

/**
 * Fetch notifications via AJAX
 */
function loadNotifications() {
    fetch('<?php echo BASE_URL; ?>ajax/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            const list = document.getElementById('notificationList');
            list.innerHTML = '<li><h6 class="dropdown-header">Notifications</h6></li><li><hr class="dropdown-divider"></li>';
            
            if (data.length === 0) {
                list.innerHTML += '<li class="text-center p-3 text-muted">No notifications</li>';
            } else {
                data.forEach(function(notif) {
                    list.innerHTML += `
                        <li>
                            <a class="dropdown-item py-2 ${notif.is_read == 0 ? 'fw-bold' : ''}" href="#">
                                <div class="small text-primary">${notif.title}</div>
                                <div class="small text-muted">${notif.message.substring(0, 60)}...</div>
                                <div class="small text-muted"><i class="bi bi-clock"></i> ${notif.time_ago}</div>
                            </a>
                        </li>`;
                });
            }
        })
        .catch(err => {
            console.error('Failed to load notifications:', err);
        });
}
</script>
<?php endif; ?>

</body>
</html>
