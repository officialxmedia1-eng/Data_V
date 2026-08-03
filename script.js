document.getElementById('year').textContent = new Date().getFullYear();

const slides = Array.from(document.querySelectorAll('.hero-slideshow .slide'));
let currentSlide = 0;

if (slides.length) {
  setInterval(() => {
    slides[currentSlide].classList.remove('active');
    currentSlide = (currentSlide + 1) % slides.length;
    slides[currentSlide].classList.add('active');
  }, 3000);
}

const trackingForm = document.getElementById('tracking-form');
const trackingStatus = document.getElementById('tracking-status');

if (trackingForm && trackingStatus) {
  trackingForm.addEventListener('submit', function (event) {
    event.preventDefault();

    const orderId = document.getElementById('order-id').value.trim().toUpperCase();
    const orderMap = {
      'DATAV-1024': { status: 'Processing', message: 'Your bundle request is being prepared and will be confirmed shortly.' },
      'DATAV-2048': { status: 'In Transit', message: 'Your request has been approved and is on its way to delivery.' },
      'DATAV-4096': { status: 'Delivered', message: 'Your bundle request has been completed successfully.' }
    };

    const result = orderMap[orderId] || {
      status: 'Not Found',
      message: 'We could not find that order ID. Please check the code and try again.'
    };

    trackingStatus.innerHTML = `<strong>${result.status}</strong><br>${result.message}`;
  });
}

