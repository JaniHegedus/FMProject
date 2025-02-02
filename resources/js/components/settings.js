// settings.js
import { player } from './player.js';

const settingsButton = document.getElementById('settings-button');
const settingsPanel = document.getElementById('settings-panel');
let panelVisible = false;

settingsButton.addEventListener('click', () => {
    panelVisible = !panelVisible;
    settingsPanel.style.display = panelVisible ? 'block' : 'none';
});

// Volume slider logic
const volumeSlider = document.getElementById('custom-volume');

// Initialize the volume slider with the saved value (or default to 25)
const storedVolume = localStorage.getItem('playerVolume');
volumeSlider.value = storedVolume !== null ? storedVolume : 25;

volumeSlider.addEventListener('input', (e) => {
    const newVolume = parseInt(e.target.value, 10);
    if (player && player.setVolume) {
        player.setVolume(newVolume);
        if (newVolume > 0) {
            player.unMute();
        }
    }
    localStorage.setItem('playerVolume', newVolume);
});

// Optionally, start playing on window load.
window.onload = () => {
    if (player && player.playVideo) {
        player.playVideo();
    }
};
