document.addEventListener('DOMContentLoaded', function () {
    const navToggle = document.getElementById('navToggle');
    const siteNav = document.getElementById('siteNav');

    if (navToggle && siteNav) {
        navToggle.addEventListener('click', function () {
            siteNav.classList.toggle('open');
        });
    }

    const bookingDate = document.querySelector('input[name="appointment_date"]');
    const bookingTime = document.querySelector('input[name="appointment_time"]');
    if (bookingDate && bookingTime) {
        bookingDate.addEventListener('change', validateAppointmentDate);
        validateAppointmentDate();
    }

    function validateAppointmentDate() {
        if (!bookingDate) return;
        const today = new Date().toISOString().split('T')[0];
        bookingDate.setAttribute('min', today);
    }
});
