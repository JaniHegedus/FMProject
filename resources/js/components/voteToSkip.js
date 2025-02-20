import { getUserIP } from "./../app.js";

const settingsPanel = document.getElementById('settings-panel');

if (settingsPanel) {
    const voteSkipButton = document.createElement('button');
    voteSkipButton.id = 'skip-button';
    voteSkipButton.className = 'skip-btn';
    voteSkipButton.textContent = 'Vote to skip current Song!';
    // Add hover/tooltip text
    voteSkipButton.title = 'You can only skip songs if more than half of the listeners agree!';
    // Create the badge element and attach it to the button
    const skipBadge = document.createElement('span');
    skipBadge.id = 'skip-badge';
    skipBadge.className = 'skip-badge';
    skipBadge.textContent = '0'; // Initialize with 0 (or an empty string)
    voteSkipButton.appendChild(skipBadge);
    // When the button is clicked, call the skip endpoint, then update the badge
    voteSkipButton.addEventListener('click', async () => {
        try {
            const ip = await getUserIP();

            // If you have a currentUser object, adjust as needed; otherwise remove user_id
            const queryParams = new URLSearchParams({
                ip: ip
            });

            // Make the request to register the vote
            const response = await fetch(`/vote-to-skip?${queryParams.toString()}`, {
                method: 'GET'
            });

            if (!response.ok) {
                console.error('Failed to vote for skip');
            }
        } catch (error) {
            console.error('Error handling vote-to-skip:', error);
        }
    });

    // Finally, add the button to the settings panel
    settingsPanel.appendChild(voteSkipButton);
}
// skipBadgeUpdater.js (for example)

export async function updateSkipBadge() {
    try {
        const countRes = await fetch('/skippers-count');
        if (!countRes.ok) {
            console.error('Failed to fetch skippers count');
            return;
        }

        const data = await countRes.json();

        if (data.skippers != null) {
            const skipBadge = document.getElementById('skip-badge');
            if (skipBadge) {
                skipBadge.textContent = data.skippers;
            }
        }
    } catch (error) {
        console.error('Error fetching skip count:', error);
    }
    setInterval(updateSkipBadge, 5000);
}
