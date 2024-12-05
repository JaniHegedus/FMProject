<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FM Player</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
            background: black;
        }

        #player-container {
            width: 100%;
            height: 100%;
            position: relative;
        }

        #player {
            width: 100%;
            height: 100%;
        }

        #video-info {
            position: absolute;
            bottom: 10px;
            left: 10px;
            color: white;
            font-family: Arial, sans-serif;
            z-index: 11;
        }

        #play-button, #unmute-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: rgba(255, 255, 255, 0.8);
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            z-index: 11;
            display: none;
        }

        /* Settings button (gear icon) */
        #settings-button {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 12;
            background: none;
            border: none;
            cursor: pointer;
            color: white;
            font-size: 24px;
            line-height: 1;
        }

        /* Settings panel */
        #settings-panel {
            position: absolute;
            top: 50px;
            right: 10px;
            width: 200px;
            background: rgba(0,0,0,0.7);
            padding: 10px;
            border-radius: 5px;
            display: none;
            z-index: 12;
        }

        #settings-panel label {
            display: block;
            color: white;
            margin-bottom: 5px;
            font-family: Arial, sans-serif;
        }

        #settings-panel input[type=range] {
            width: 100%;
        }
    </style>
</head>
<body>
<div id="player-container">
    <div id="player"></div>
    <button id="play-button">Play</button>
    <button id="unmute-button">Unmute</button>
    <button id="settings-button">⚙</button>
    <div id="settings-panel">
        <label for="custom-volume">Volume:</label>
        <input id="custom-volume" type="range" min="0" max="100">
    </div>
</div>

<div id="video-info">
    <h1 id="video-title">{{ $videoTitle ?? 'No video playing' }}</h1>
    <p id="video-start-time">Started at: {{ $startTime ?? 'N/A' }}</p>
</div>

<script>
    let player;
    let defaultVolume = 25;
    const storedVolume = localStorage.getItem('playerVolume');
    if (storedVolume !== null) {
        defaultVolume = parseInt(storedVolume, 10);
    }

    // Load YouTube IFrame Player API
    const tag = document.createElement('script');
    tag.src = "https://www.youtube.com/iframe_api";
    const firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

    const playButton = document.getElementById('play-button');
    const unmuteButton = document.getElementById('unmute-button');

    function onYouTubeIframeAPIReady() {
        const videoId = "{{ $videoId ?? '' }}";
        const progress = {{ $progress ?? 0 }};

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
                mute: 1 // Start muted to comply with autoplay policy
            },
            events: {
                onReady: onPlayerReady,
                onStateChange: onPlayerStateChange,
            },
        });

        // If autoplay fails, user can click play
        playButton.addEventListener('click', () => {
            player.playVideo();
            // After user interaction, we can safely set volume and unmute
            player.setVolume(defaultVolume);
            player.unMute();
            playButton.style.display = 'none';
        });

        // Unmute button if automatic unmute fails
        unmuteButton.addEventListener('click', () => {
            player.unMute();
            player.setVolume(defaultVolume);
            unmuteButton.style.display = 'none';
        });
    }

    function onPlayerReady(event) {
        // Player is ready and should start playing muted
        // We will try to unmute and set volume after it starts playing (onStateChange)
    }

    function onPlayerStateChange(event) {
        if (event.data === YT.PlayerState.PLAYING) {
            // The video is now playing. Attempt to set volume and unmute automatically.
            try {
                player.setVolume(defaultVolume);
                player.unMute();
            } catch (e) {
                // If this fails due to browser policies, show the unmute button
                unmuteButton.style.display = 'block';
            }
        } else if (event.data === YT.PlayerState.ENDED) {
            console.log("Video ended, reloading in 1s.");
            setTimeout(() => location.reload(), 1000);
        } else if (event.data === YT.PlayerState.PAUSED) {
            console.log("Video paused.");
        }
    }

    // Settings panel logic
    const settingsButton = document.getElementById('settings-button');
    const settingsPanel = document.getElementById('settings-panel');
    let panelVisible = false;

    settingsButton.addEventListener('click', () => {
        panelVisible = !panelVisible;
        settingsPanel.style.display = panelVisible ? 'block' : 'none';
    });

    // Volume slider logic
    const volumeSlider = document.getElementById('custom-volume');
    volumeSlider.value = defaultVolume;

    volumeSlider.addEventListener('input', (e) => {
        const newVolume = parseInt(e.target.value, 10);
        if (player && player.setVolume) {
            player.setVolume(newVolume);
            // Unmute if volume > 0, ensuring user can hear sound
            if (newVolume > 0) {
                player.unMute();
            }
        }
        localStorage.setItem('playerVolume', newVolume);
    });

    window.onload = () => {
        if (player && player.playVideo) {
            player.playVideo();
        }
    };
</script>
</body>
</html>
