const images = document.querySelectorAll('.carousel img');
let current = 0;

function nextImage() {
    images[current].classList.remove('active');
    current = (current + 1) % images.length;
    images[current].classList.add('active');
}

if(images.length > 0){
    images[current].classList.add('active');
    setInterval(nextImage, 3000);
}
