import './bootstrap';

document.addEventListener('DOMContentLoaded', function () {
    const errorAlert = document.getElementById('errorAlert');
    if (errorAlert) {
        setTimeout(function () {
            errorAlert.style.transition = 'opacity 0.5s ease-out';
            errorAlert.style.opacity = '0';
            setTimeout(function () {
                errorAlert.remove();
            }, 500);
        }, 5000);
    }
});

