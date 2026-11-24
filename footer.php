<?php
/**
 * admin/inc/footer.php
 * Giao diện dùng chung phần chân trang (Footer) cho Phân hệ Quản trị
 */
?>
        </main>

        <!-- Admin Footer -->
        <footer class="admin-footer">
            <div class="d-flex flex-column flex-md-row align-items-center justify-content-between">
                <span>&copy; <?= date('Y') ?> WatchStore — Phân hệ Quản trị Bán Đồng Hồ Pin</span>
               
            </div>
        </footer>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>
