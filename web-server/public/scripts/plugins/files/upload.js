const status_section = document.getElementById('status');
const upload_button = document.querySelector('[component=upload]');

upload_button.onclick = () => selectFiles().then((locations) => console.log(locations));

status_section.classList.add('none');