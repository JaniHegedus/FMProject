// popup.js
export function showPausePopup() {
    let popup = document.createElement('div');
    popup.textContent = 'If you wish to pause, please pause the video again in ';
    popup.style.position = 'fixed';
    popup.style.top = '10px';
    popup.style.left = '50%';
    popup.style.transform = 'translateX(-50%)';
    popup.style.backgroundColor = '#333';
    popup.style.color = 'white';
    popup.style.padding = '10px 20px';
    popup.style.borderRadius = '5px';
    popup.style.fontSize = '16px';
    popup.style.zIndex = '9999';
    popup.style.display = 'none';

    let countdownElement = document.createElement('span');
    countdownElement.textContent = '5s';
    popup.appendChild(countdownElement);

    document.body.appendChild(popup);
    popup.style.display = 'block';
    setTimeout(() => {
        popup.style.opacity = '1';
    }, 10);

    let countdown = 5;
    let countdownInterval = setInterval(() => {
        countdown--;
        countdownElement.textContent = countdown;
        if (countdown <= 0) {
            clearInterval(countdownInterval);
        }
    }, 1000);

    setTimeout(() => {
        popup.style.opacity = '0';
        setTimeout(() => {
            popup.remove();
        }, 500);
        // Optionally, you could reset related state here.
    }, 5000);
}
