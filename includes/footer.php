    </main> <!-- Закрытие main.container -->
    <footer class="main-footer">
        <div class="container">
            <p>&copy; <?= date('Y'); ?> <strong>PhysCalc</strong>. Физический калькулятор: законы Ньютона и механика. Курсовой проект.</p>
        </div>
    </footer>
    <script src="/js/main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const burgerBtn = document.getElementById('burger-btn');
        const navMenu = document.querySelector('.main-nav ul');
    
        if (burgerBtn && navMenu) {
            burgerBtn.addEventListener('click', function() {
                // Переключаем видимость меню
                navMenu.classList.toggle('active');
                // Переключаем анимацию самой кнопки (крестик)
                burgerBtn.classList.toggle('open');
            });
        }
    });
    </script>

</body>
</html>
