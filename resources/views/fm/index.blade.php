<!-- resources/views/yourview.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="/assets/radio-icon.webp" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <title>JaniHegedus FM</title>
    @vite(['resources/js/auto-import.js'])
</head>
<body>
<script>
    window.AppConfig = {
        videoId: "{{ $videoId ?? '' }}",
        progress: {{ $progress ?? 0 }},
        requester: "{{ $requester ?? '' }}"
    };
</script>

<div id="player-container">
    <div id="player"></div>
    <button id="play-button">Play</button>
    <div id="settings-wrapper">
        <button id="history-button">History</button>
        <button id="settings-button">⚙</button>
    </div>
    <div id="settings-panel">
        <label for="custom-volume">Volume:</label>
        <input id="custom-volume" type="range" min="0" max="100">
        <button id="login-button" class="login-btn">Login</button>
    </div>
</div>
<div id="requester">
    <h1 id="requester-text"><?= $requester ?? '' ?></h1>
</div>

<!-- Login Modal -->
<div id="login-modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <form method="POST" action="/login">
            @csrf
            <h2>Login</h2>
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" placeholder="you@example.com" required>
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" placeholder="Your password" required>
            <button type="submit" class="btn">Submit</button>
            <p class="register-switch">
                Don't have an account?
                <a href="#" id="switch-to-register">Register here</a>
            </p>
        </form>
    </div>
</div>

<!-- Register Modal -->
<div id="register-modal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <form method="POST" action="/register">
            @csrf
            <h2>Register</h2>
            <label for="reg-name">Name:</label>
            <input type="text" name="name" id="reg-name" placeholder="Your name" required>
            <label for="reg-email">Email:</label>
            <input type="email" name="email" id="reg-email" placeholder="you@example.com" required>
            <label for="reg-password">Password:</label>
            <input type="password" name="password" id="reg-password" placeholder="Your password" required>
            <label for="reg-password_confirmation">Confirm Password:</label>
            <input type="password" name="password_confirmation" id="reg-password_confirmation" placeholder="Confirm your password" required>
            <button type="submit" class="btn">Register</button>
            <p class="login-switch">
                Already have an account?
                <a href="#" id="switch-to-login">Login here</a>
            </p>
        </form>
    </div>
</div>

<div id="video-info">
    <h1 id="video-title">{{ $videoTitle ?? 'No video playing' }}</h1>
    <h2 id="video-start-time">{{ $startTime ?? 'N/A' }}</h2>
</div>
</body>
</html>
