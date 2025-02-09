import { fetchLoggedInUser } from '../app.js';

// Global variables to track the pool icon, whether the pool has been acknowledged,
// current pool entries, the currently open pool modal overlay, and the current user ID.
let poolIconElement = null;
let poolNotified = false;
let currentPoolEntries = [];
let poolModalOverlay = null; // Reference to the open modal, if any.
let currentUserId = null;    // Will store the logged-in user's id

/**
 * Periodically checks the pool status every 10 seconds.
 * - The vote button ("Vote for a song!") is shown only for logged-in users.
 * - The pool icon (for viewing pool entries) and the "A Pool Started!" popup are shown for everyone.
 */
export function checkForNewPool() {
    setInterval(async () => {
        // Fetch the logged-in user and update currentUserId.
        const user = await fetchLoggedInUser();
        if (user && user.id) {
            currentUserId = user.id;
        } else {
            currentUserId = null;
        }
        const isUserLoggedIn = !!currentUserId;

        try {
            const response = await fetch('/pool-status', {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            // Get a reference to the vote button.
            const createPoolBtn = document.getElementById('create-pool-btn');

            if (!data.empty && data.entries && data.entries.length > 0) {
                currentPoolEntries = data.entries;
                // If user is logged in, update the button to "Vote for a song!".
                if (isUserLoggedIn && createPoolBtn) {
                    createPoolBtn.textContent = 'Vote for a song!';
                    createPoolBtn.style.display = 'block';
                } else if (createPoolBtn) {
                    // For non-logged in users, hide the vote option.
                    createPoolBtn.style.display = 'none';
                }
                // Always show (or create) the pool icon for everyone.
                if (!poolIconElement) {
                    createPoolIcon();
                }
                // If a pool modal is already open, update its content.
                if (poolModalOverlay) {
                    updatePoolModalContent(currentPoolEntries);
                }
                // Show the "A Pool Started!" popup if not yet acknowledged.
                if (!poolNotified) {
                    showPoolStartedPopup();
                }
            } else {
                // No poll exists—update the button text and make sure it's visible.
                if (createPoolBtn && isUserLoggedIn) {
                    createPoolBtn.textContent = 'Create a Pool';
                    createPoolBtn.style.display = 'block';
                }
                if (poolIconElement) {
                    poolIconElement.remove();
                    poolIconElement = null;
                }
                poolNotified = false;
            }
        } catch (error) {
            console.error('Error checking pool status:', error);
        }
    }, 10000); // Every 10 seconds
}

/**
 * Creates the pool icon and places it next to the settings button.
 * This icon is for everyone (logged in or not) to view pool entries.
 */
function createPoolIcon() {
    const settingsButton = document.getElementById('settings-button');
    if (!settingsButton) return;

    poolIconElement = document.createElement('span');
    poolIconElement.id = 'pool-icon';
    poolIconElement.textContent = '🎶';
    poolIconElement.className = 'pool-icon';
    poolIconElement.style.cursor = 'pointer';

    // Insert the pool icon immediately after the settings button.
    settingsButton.insertAdjacentElement('afterend', poolIconElement);

    // Clicking the pool icon shows the pool content modal.
    poolIconElement.addEventListener('click', () => {
        if (poolModalOverlay) return; // Modal already open.
        showPoolContent(currentPoolEntries);
        poolNotified = true; // Mark pool as acknowledged.
    });
}

/**
 * Displays a temporary popup message "A Pool Started !".
 */
function showPoolStartedPopup() {
    const popup = document.createElement('div');
    popup.className = 'pool-popup-message';
    popup.textContent = 'A Pool Started !';
    document.body.appendChild(popup);

    // Auto-dismiss the popup after 3 seconds.
    setTimeout(() => {
        popup.classList.add('hide');
        setTimeout(() => {
            if (popup.parentNode) {
                popup.parentNode.removeChild(popup);
            }
        }, 500);
    }, 3000);
}
/**
 * Displays the pool content in a modal with a countdown.
 * @param {Array} entries - The pool entries returned from the endpoint.
 */
function showPoolContent(entries) {
    poolModalOverlay = document.createElement('div');
    poolModalOverlay.id = 'pool-content-overlay';
    poolModalOverlay.className = 'pool-content-overlay';

    const modalContent = document.createElement('div');
    modalContent.className = 'pool-content-modal';

    // Create and append a header.
    const header = document.createElement('h2');
    header.textContent = 'Pool Entries';
    modalContent.appendChild(header);

    // Create the countdown element.
    const countdownEl = document.createElement('div');
    countdownEl.id = 'poll-countdown';
    countdownEl.style.marginBottom = '10px';
    modalContent.appendChild(countdownEl);

    // Determine poll start time from the earliest created_at among entries.
    if (entries.length > 0) {
        // Parse created_at strings into timestamps.
        const startTime = Math.min(...entries.map(e => new Date(e.created_at).getTime()));
        const pollEndTime = startTime + (10 * 60 * 1000); // 10 minutes later

        // Update the countdown display.
        const updateCountdown = () => {
            const now = Date.now();
            const remaining = pollEndTime - now;
            if (remaining <= 0) {
                // Determine the top voted entry.
                let topEntry = entries[0];
                entries.forEach(entry => {
                    if (entry.votes > topEntry.votes) {
                        topEntry = entry;
                    }
                });
                const songName = topEntry.video_title || topEntry.video_id;
                countdownEl.textContent = `Poll results: ${songName} is the next song!`;
                disablePoolEntries();
                clearInterval(countdownInterval);
                // Update the create pool button so that a new poll can be started.
                const createPoolBtn = document.getElementById('create-pool-btn');
                if (createPoolBtn) {
                    createPoolBtn.textContent = 'Create a Pool';
                    createPoolBtn.style.display = 'block';
                }
            } else {
                const minutes = Math.floor(remaining / (60 * 1000));
                const seconds = Math.floor((remaining % (60 * 1000)) / 1000);
                countdownEl.textContent = `Time remaining: ${minutes}m ${seconds}s`;
            }
        };
        updateCountdown();
        var countdownInterval = setInterval(updateCountdown, 1000);
    } else {
        countdownEl.textContent = '';
    }

    // Create a list container for the pool entries.
    const listContainer = document.createElement('ul');
    listContainer.id = 'pool-entries-list'; // For updating later.
    listContainer.className = 'pool-entries-list';

    buildPoolList(listContainer, entries);
    modalContent.appendChild(listContainer);

    // Create a close button.
    const closeBtn = document.createElement('span');
    closeBtn.className = 'pool-content-close';
    closeBtn.textContent = '×';
    modalContent.appendChild(closeBtn);

    poolModalOverlay.appendChild(modalContent);
    document.body.appendChild(poolModalOverlay);

    closeBtn.addEventListener('click', () => {
        clearInterval(countdownInterval);
        closePoolModal();
    });
    poolModalOverlay.addEventListener('click', (e) => {
        if (e.target === poolModalOverlay) {
            clearInterval(countdownInterval);
            closePoolModal();
        }
    });
}

/**
 * Disables all pool entry items in the modal.
 * Adds an "ended" class and removes pointer events.
 */
function disablePoolEntries() {
    const entries = document.querySelectorAll('.pool-entry-item');
    entries.forEach(entry => {
        entry.style.pointerEvents = 'none';
        entry.classList.add('ended');
    });
}


/**
 * Updates the pool modal's content with new entries.
 * @param {Array} entries - The updated pool entries.
 */
function updatePoolModalContent(entries) {
    const listContainer = document.getElementById('pool-entries-list');
    if (!listContainer) return;
    listContainer.innerHTML = '';
    buildPoolList(listContainer, entries);
}

/**
 * Builds the pool entries list.
 * For logged-in users, if an entry’s created_by matches the current user's ID,
 * or if the current user's ID appears in the entry’s voted_by array,
 * then that entry is immediately marked as "voted" (grayed out and unclickable).
 *
 * @param {HTMLElement} listContainer - The container element for the list.
 * @param {Array} entries - The pool entries (each with a voted_by field).
 */
function buildPoolList(listContainer, entries) {
    // Determine the maximum vote count to scale progress bars.
    const maxVotes = Math.max(...entries.map(entry => entry.votes));
    // Check if the vote functionality is available (i.e. if the vote button is visible).
    const isUserLoggedIn = !!(
        document.getElementById('create-pool-btn') &&
        document.getElementById('create-pool-btn').style.display === 'block'
    );

    entries.forEach(entry => {
        const listItem = document.createElement('li');
        listItem.className = 'pool-entry-item';

        // Check if this entry was submitted by the current user.
        const isSubmittedByCurrentUser = currentUserId && (entry.created_by == currentUserId);
        // Determine if the current user has already voted for this entry.
        // Assume entry.voted_by is either an array or a JSON string.
        let votedBy = entry.voted_by;
        if (typeof votedBy === 'string') {
            try {
                votedBy = JSON.parse(votedBy);
            } catch (e) {
                votedBy = [];
            }
        }
        const hasVoted = isUserLoggedIn && Array.isArray(votedBy) && votedBy.includes(currentUserId);

        // Only allow clicking if the user is logged in and neither the creator nor already voted.
        listItem.style.cursor = (isUserLoggedIn && !isSubmittedByCurrentUser && !hasVoted) ? 'pointer' : 'default';

        const titleEl = document.createElement('div');
        titleEl.className = 'pool-entry-title';
        titleEl.textContent = entry.video_title || entry.video_id;
        listItem.appendChild(titleEl);

        const barContainer = document.createElement('div');
        barContainer.className = 'pool-entry-bar-container';
        let percentage = maxVotes > 0 ? (entry.votes / maxVotes) * 100 : 0;
        const progressBar = document.createElement('div');
        progressBar.className = 'pool-entry-progress-bar';
        progressBar.style.width = percentage + '%';
        barContainer.appendChild(progressBar);
        listItem.appendChild(barContainer);

        const voteCountText = document.createElement('div');
        voteCountText.className = 'pool-entry-vote-count';
        voteCountText.textContent = `Votes: ${entry.votes}`;
        listItem.appendChild(voteCountText);

        // If the user is logged in and allowed to vote for this entry, add a click handler.
        if (isUserLoggedIn && !isSubmittedByCurrentUser && !hasVoted) {
            listItem.addEventListener('click', () => {
                voteForVideo(entry.video_id, listItem);
            });
        } else if (isUserLoggedIn && (isSubmittedByCurrentUser || hasVoted)) {
            // Mark the entry as already voted/submitted.
            listItem.classList.add('voted');
        }

        listContainer.appendChild(listItem);
    });
}

/**
 * Sends a vote for the given video by calling the /start-pool endpoint.
 * In this approach, your backend should update both the vote count and the voted_by array.
 *
 * @param {string} videoId - The ID of the video to vote for.
 * @param {HTMLElement} listItem - The list item element corresponding to the video.
 */
function voteForVideo(videoId, listItem) {
    // Prevent further clicks.
    listItem.style.pointerEvents = 'none';

    // Retrieve CSRF token (assumes a meta tag with name "csrf-token" exists).
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

    fetch('/start-pool', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        credentials: 'include',
        body: JSON.stringify({ video_id: videoId, voted_by: currentUserId })
    })
        .then(response => response.json())
        .then(result => {
            if (result.error) {
                alert('Error: ' + result.error);
                // Re-enable clicking if there was an error.
                listItem.style.pointerEvents = 'auto';
            } else {
                // Mark the item as voted.
                listItem.classList.add('voted');
                listItem.style.pointerEvents = 'none';
                // Optionally, you can refresh the pool entries here.
            }
        })
        .catch(error => {
            console.error('Error voting for video:', error);
            listItem.style.pointerEvents = 'auto';
        });
}

/**
 * Closes the pool modal and resets the global reference.
 */
function closePoolModal() {
    if (poolModalOverlay) {
        document.body.removeChild(poolModalOverlay);
        poolModalOverlay = null;
    }
}

