// ملف: js/media_slider.js

let currentMediaList = [];
let currentIndex = 0;

function openMediaSlider(mediaList, startIndex) {
  currentMediaList = mediaList;
  currentIndex = startIndex;
  document.getElementById('mediaSliderModal').style.display = 'flex';
  showCurrentMedia();
}

function closeMediaSlider() {
  document.getElementById('mediaSliderModal').style.display = 'none';
}

function showCurrentMedia() {
  const sliderImage = document.getElementById('sliderImage');
  const sliderVideo = document.getElementById('sliderVideo');

  sliderImage.style.display = 'none';
  sliderVideo.style.display = 'none';
  sliderVideo.pause();

  if (!currentMediaList || currentMediaList.length === 0) return;

  const basePath = "uploads/posts/";
  const item = currentMediaList[currentIndex];
  const fullPath = basePath + item.media_path;

  if (item.media_type === 'video') {
    sliderVideo.src = fullPath;
    sliderVideo.style.display = 'block';
  } else {
    sliderImage.src = fullPath;
    sliderImage.style.display = 'block';
  }
}

function prevMedia() {
  if (currentIndex > 0) {
    currentIndex--;
    showCurrentMedia();
  }
}

function nextMedia() {
  if (currentIndex < currentMediaList.length - 1) {
    currentIndex++;
    showCurrentMedia();
  }
}
