// app.js
import './bootstrap'; // Assumes you have a bootstrap file to initialize your app.
import {checkForNewVideo} from './components/videoLoader.js';
import { checkForNewPool } from './components/pool/poolStatusChecker.js';
import {updateBadge, MessagesCount, chatPopup} from './components/chat.js';
import {checkListenerCount} from './components/listeners.js';
import { updateSkipBadge } from './components/voteToSkip.js';

export const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
export let currentUser = null;
const pageOpenTime = new Date();
let currentMessagesCount = 0;

// Check if a session start is already stored, otherwise set a new one.
export const pageOpenTimeCarbon = sessionStorage.getItem('session_start') || (() => {
    const newSession = formatToCarbon(pageOpenTime);
    sessionStorage.setItem('session_start', newSession);
    return newSession;
})();

// Kick off the periodic check for new video data.
checkForNewVideo().then();
checkForNewPool();
checkfornewMessages();
checkListenerCount();
updateSkipBadge();
export async function fetchLoggedInUser() {
    try {
        const response = await fetch('/user', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
            credentials: 'include' // ensures cookies are sent along with the request
        });

        if (!response.ok) {
            console.error('Failed to fetch user. Status:', response.status);
            return null;
        }

        return await response.json();
    } catch (error) {
        console.error('Error fetching user:', error);
        return null;
    }
}

// Call the function and update the UI if a user is logged in.
fetchLoggedInUser().then(user => {
    if (user && user.id) {
        //console.log('Logged in user:', user);
        currentUser = user;
        // For example, update the UI to show the username and a logout link.
        const settingsPanel = document.getElementById('settings-panel');
        if (settingsPanel) {
            // Create a new element for user info
            const userInfo = document.createElement('span');
            userInfo.id = 'user-info';
            userInfo.innerHTML = `Welcome, ${user.name}! <a href="#" id="logout">Logout</a>`;
            // Replace the login button with this new element.
            const loginButton = document.getElementById('login-button');
            if (loginButton) {
                settingsPanel.replaceChild(userInfo, loginButton);
            } else {
                // If the login button is already replaced, just update the text.
                settingsPanel.appendChild(userInfo);
            }

            // Attach logout event listener (assuming you have a logout endpoint)
            const logoutLink = document.getElementById('logout');
            logoutLink.addEventListener('click', async (e) => {
                e.preventDefault();
                // Send an AJAX logout request
                try {
                    const logoutResponse = await fetch('/logout', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'include'
                    });
                    if (logoutResponse.ok) {
                        // Optionally update the UI on logout
                        userInfo.remove();
                        // Create a new login button and add it back
                        const newLoginButton = document.createElement('button');
                        newLoginButton.id = 'login-button';
                        newLoginButton.className = 'login-btn';
                        newLoginButton.textContent = 'Login';
                        settingsPanel.appendChild(newLoginButton);
                        // Reattach your login modal event listener here, if needed
                        showPopup('Logout Successful!',true);
                        setTimeout(function(){window.location.reload();},1000);
                    } else {
                        showPopup('Logout failed!',false);
                    }
                } catch (err) {
                    showPopup('Error during logout:'+err,false);
                }
            });
        }
    } else {
        console.log('No user is currently logged in.');
    }
});
function showPopup(message, isSuccess = true) {
    const popup = document.createElement('div');
    popup.classList.add('popup', isSuccess ? 'success' : 'error');
    popup.textContent = message;
    document.body.appendChild(popup);
    setTimeout(() => {
        popup.style.opacity = '0';
        setTimeout(() => {
            popup.remove();
        }, 500);
    }, 3000);
}
function formatToCarbon(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}
function checkfornewMessages(){
    let difference = currentMessagesCount - MessagesCount;
    // In the loadMessages function, after updating chatMessages:
    if (chatPopup.style.display === 'none' || chatPopup.style.display === '') {
        updateBadge(difference);
    } else {
        // If the chat is open, mark messages as read.
        currentMessagesCount = MessagesCount;
        updateBadge(0);
    }
    fetch(`/messages-count/${encodeURIComponent(pageOpenTimeCarbon)}`)
        .then(response => response.json())
        .then(data => {
            currentMessagesCount = Number(data.messagesCount);
        })
        .catch(error => console.error('Error fetching messages:', error));
    setTimeout(() => checkfornewMessages(), 5000);
}
export async function getUserIP() {
    const response = await fetch('https://api.ipify.org/?format=json');
    const data = await response.json();
    return data.ip;
}
