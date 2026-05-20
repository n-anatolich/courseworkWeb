document.addEventListener("DOMContentLoaded", function() {
    console.log("PhysCalc: DOM fully loaded and parsed");

    const burgerBtn = document.getElementById('burger-btn');
    const mainNav = document.querySelector('.main-nav ul');
    const html = document.documentElement; // Получаем тег <html>
    const body = document.body; // Получаем тег <body>

    if (burgerBtn && mainNav) {
        // Открытие/закрытие меню
        burgerBtn.addEventListener('click', function() {
            burgerBtn.classList.toggle('open');
            mainNav.classList.toggle('active');
            
            // Блокируем скролл на обоих корневых тегах
            html.classList.toggle('no-scroll');
            body.classList.toggle('no-scroll');
        });

        // Закрытие меню при клике на любую ссылку
        const navLinks = mainNav.querySelectorAll('a');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                burgerBtn.classList.remove('open');
                mainNav.classList.remove('active');
                
                // Снимаем блокировку
                html.classList.remove('no-scroll');
                body.classList.remove('no-scroll');
            });
        });
    }
});
