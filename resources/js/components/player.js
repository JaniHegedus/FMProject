// player.js
import { config } from './config.js';
import { showPausePopup } from './popup.js';
import { loadNextVideo } from './videoLoader.js';

export let player;
export let playing = true;
// These values are initially taken from the config.
export let currentVideoId = config.videoId || "";
export let currentProgress = config.progress || 0;

let defaultVolume = 10;
let stopAttempts = 0;
let currentPopup = null;

// Check for a saved volume from localStorage.
const storedVolume = localStorage.getItem('playerVolume');
if (storedVolume !== null) {
    defaultVolume = parseInt(storedVolume, 10);
}

const playButton = document.getElementById('play-button');
const unmuteButton = document.getElementById('unmute-button');

// Load the YouTube IFrame Player API.
const tag = document.createElement('script');
tag.src = "https://www.youtube.com/iframe_api";
const firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

// This function is called by the YouTube API.
window.onYouTubeIframeAPIReady = function() {
    const videoId = config.videoId || "";
    const progress = config.progress || 0;

    if (!videoId) {
        console.error("No video ID provided!");
        return;
    }

    player = new YT.Player('player', {
        height: '100%',
        width: '100%',
        videoId: videoId,
        playerVars: {
            autoplay: 1,
            controls: 0,
            modestbranding: 1,
            rel: 0,
            start: progress > 0 ? progress : 0,
            mute: 1, // Start muted to comply with autoplay policies
            iv_load_policy: 3,
            fs: 0,
        },
        events: {
            onReady: onPlayerReady,
            onStateChange: onPlayerStateChange,
        },
    });

    // Hook up the play and unmute buttons.
    playButton.addEventListener('click', () => {
        player.playVideo();
        player.setVolume(defaultVolume);
        player.unMute();
        playButton.style.display = 'none';
    });

    unmuteButton.addEventListener('click', () => {
        player.unMute();
        player.setVolume(defaultVolume);
        unmuteButton.style.display = 'none';
    });
};

function onPlayerReady(event) {
    // The player is ready. Additional initialization can go here.
}

async function onPlayerStateChange(event) {
    if (event.data === YT.PlayerState.PLAYING) {
        try {
            player.setVolume(parseInt(storedVolume, 10));
            player.unMute();
            const response = await fetch('/currentVideo');
            const data = await response.json();
            if (data.progress > 0 && playing === false) {
                player.seekTo(data.progress, true);
            }
        } catch (e) {
            unmuteButton.style.display = 'block';
        }
        playing = true;
    } else if (event.data === YT.PlayerState.ENDED) {
        console.log("Video ended, loading next video in 1s.");
        setTimeout(() => loadNextVideo(), 1000);
    } else if (event.data === YT.PlayerState.PAUSED) {
        //console.log(playing);
        stopAttempts++;
        if (stopAttempts === 2) {
            stopAttempts = 0;
            playing = false;
            if (currentPopup) {
                currentPopup.remove();
                currentPopup = null;
            }
        } else {
            player.playVideo();
            showPausePopup();
        }
    }
}

export function setPlayerVolume(volume) {
    defaultVolume = volume;
    if (player && player.setVolume) {
        player.setVolume(volume);
    }
}
export function updateCurrentVideoId(newId) {
    currentVideoId = newId;
}
