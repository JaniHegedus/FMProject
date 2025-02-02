<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/assets/radio-icon.webp" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <title>JaniHegedus FM</title>
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
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            flex-direction: column; /* Stack the content vertically */
            justify-content: center;
            align-items: center;
            color: white;
            font-family: 'Roboto', sans-serif; /* Apply the font */
            z-index: 11;
            width: 100%;
            opacity: 0; /* Start with hidden */
            animation: fadeIn 1.5s ease-in-out forwards; /* Animation for fade-in */
        }

        #video-title {
            font-size: 2.5em;
            font-weight: 700; /* Bold for title */
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6); /* Add slight shadow */
            animation: bounce 1s ease-in-out infinite; /* Animation for bounce effect */
        }

        #video-start-time {
            font-size: 1.5em;
            font-weight: 400; /* Lighter for subtitle */
            margin: 2px 0 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6); /* Add slight shadow */
            opacity: 0; /* Start with hidden */
            animation: fadeIn 1.5s ease-in-out 0.5s forwards; /* Delay fade-in for subtitle */
        }

        /* Fade-in animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        /* Bounce animation for title */
        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px); /* Slight bounce up */
            }
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
        #site-logo {
            position: fixed; /* Fixed position so it stays in place while scrolling */
            bottom: 10px;    /* 10px from the bottom */
            left: 10px;      /* 10px from the left */
            width: 50px;     /* Set a size for the logo */
            height: auto;    /* Keep the aspect ratio */
            z-index: 999;    /* Ensure it’s on top of other content */
        }
        #requester {
            z-index: 13;
            position: fixed; /* Fixed position so it stays in place while scrolling */
            top: 2%;    /* 10px from the bottom */
            left: 1%;      /* 10px from the left */
            width: auto;     /* Set a size for the logo */
            height: auto;    /* Keep the aspect ratio */
            color: white;
            margin-bottom: 5px;
            font-family: Arial, sans-serif;
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
<div id="requester"><h1 id="requester-text"><?= $requester ?? ''?></h1></div>

<div id="video-info">
    <h1 id="video-title">{{ $videoTitle ?? 'No video playing' }}</h1>
    <h2 id="video-start-time">{{ $startTime ?? 'N/A' }}</h2>
</div>
<img id="site-logo" src="/assets/radio-icon.webp" alt="JaniHegedus FM"/>

<script>
    let player;
    let defaultVolume = 25;
    let stopAttempts = 0;
    let currentPopup = null; // Variable to hold the reference to the popup
    let currentVideoId = "{{ $videoId ?? '' }}";
    let currentProgress = {{ $progress ?? 0 }};
    let requester = "{{ $requester ?? '' }}";
    let playing = true;


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
                mute: 1, // Start muted to comply with autoplay policy
                iv_load_policy: 3, // Disable annotations
                fs: 0,   // Disable fullscreen
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

    async function onPlayerStateChange(event) {
        if (event.data === YT.PlayerState.PLAYING) {
            // The video is now playing. Attempt to set volume and unmute automatically.
            try {
                player.setVolume(defaultVolume);
                player.unMute();
                const response = await fetch('/currentVideo');
                const data = await response.json();
                try {
                    if (data.progress > 0 && playing === false) {
                        player.seekTo(data.progress, true); // Seek to the current progress point
                    }
                } catch (e) {
                    console.error("Error seeking to progress:", e);
                }
            } catch (e) {
                // If this fails due to browser policies, show the unmute button
                unmuteButton.style.display = 'block';
            }
            playing = true;
        } else if (event.data === YT.PlayerState.ENDED) {
            console.log("Video ended, reloading in 1s.");
            //setTimeout(() => location.reload(), 1000);
            setTimeout(() => loadNextVideo(), 1000); // Fetch next video instead of reloading page
        } else if (event.data === YT.PlayerState.PAUSED) {
            stopAttempts++;
            if (stopAttempts === 2) {
                stopAttempts = 0;  // Reset attempts if it's the second stop
                playing = false;
                //console.log(currentPopup);
                if (currentPopup) {
                    currentPopup.remove();  // Remove the popup from the DOM
                    currentPopup = null; // Reset reference
                }
            } else {
                player.playVideo();  // Play the video again
                showPausePopup();
            }
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
    async function loadNextVideo() {
        try {
            const response = await fetch('/currentVideo');
            const data = await response.json();
            //console.log(data);

            if (data.error) {
                console.error("Error fetching next video:", data.error);
                return;
            }

            if (data.video_id !== currentVideoId) {
                //console.log("Loading new video:", data.video_id);
                currentVideoId = data.video_id;
                currentProgress = data.progress ?? 0;
                player.loadVideoById(currentVideoId, currentProgress);
                document.getElementById("video-title").textContent = data.video_title;
                document.getElementById("video-start-time").textContent = data.start_time;
                if(data.requester !== '')document.getElementById("requester-text").textContent = 'Requested by: '+data.requester;
                else document.getElementById("requester-text").textContent = '';
                //console.log('Should ve loaded... this:'+ currentVideoId+" With progress: "+currentProgress);
            }
        } catch (error) {
            console.error("Error fetching next video:", error);
        }
    }
    async function checkForNewVideo() {
        try {
            if(playing){
                const response = await fetch('/currentVideo');
                const data = await response.json();
                currentProgress = data.progress;

                if (data.error) {
                    console.error("Error fetching current video:", data.error);
                    return;
                }

                // If the video has changed, load it
                if (data.video_id !== currentVideoId) {
                    console.log("New video detected:", data.video_id);
                    //console.log(playing);
                    if(playing) loadNextVideo();
                }
            }
            setTimeout(() => checkForNewVideo(), 5000);
            //console.log('hi');
        } catch (error) {
            console.error("Error checking for new video:", error);
        }
    }
    // Function to show the popup message
    function showPausePopup() {
        // Create a new div element for the popup
        let popup = document.createElement('div');
        popup.textContent = 'If you wish to pause, please pause the video again in ';
        popup.style.position = 'fixed';
        popup.style.top = '10px';  // Move to the top of the screen
        popup.style.left = '50%';
        popup.style.transform = 'translateX(-50%)';
        popup.style.backgroundColor = '#333';
        popup.style.color = 'white';
        popup.style.padding = '10px 20px';
        popup.style.borderRadius = '5px';
        popup.style.fontSize = '16px';
        popup.style.zIndex = '9999';
        popup.style.display = 'none';

        // Create countdown timer inside the popup
        let countdownElement = document.createElement('span');
        countdownElement.textContent = '5s';  // Start countdown at 5
        popup.appendChild(countdownElement);

        // Store the popup reference in currentPopup
        currentPopup = popup;
        // Append the popup to the body
        document.body.appendChild(popup);

        // Fade in effect for the popup
        popup.style.display = 'block';
        setTimeout(() => {
            popup.style.opacity = '1';
        }, 10);

        // Countdown logic
        let countdown = 5; // Starting countdown from 5 seconds
        let countdownInterval = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;  // Update countdown text
            if (countdown <= 0) {
                clearInterval(countdownInterval);  // Stop countdown when it reaches 0
            }
        }, 1000); // Update every 1 second

        // Hide the popup after 5 seconds
        setTimeout(() => {
            popup.style.opacity = '0';
            setTimeout(() => {
                popup.remove();  // Remove the popup from the DOM after fading out
            }, 500);
            stopAttempts = 0;
        }, 5000);  // Show for 5 seconds before fading out
    }
    checkForNewVideo();
</script>
</body>
</html>
