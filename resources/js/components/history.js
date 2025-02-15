document.addEventListener('DOMContentLoaded', function() {
    const historyButton = document.getElementById('history-button');

    historyButton.addEventListener('click', function() {
        // Fetch history data from the backend endpoint
        fetch(`/history/${getYesterdayFormatted()}`)
            .then(response => response.json())
            .then(data => {
                // Create the modal if it doesn't exist yet
                let modal = document.getElementById('history-modal');
                if (!modal) {
                    modal = document.createElement('div');
                    modal.id = 'history-modal';

                    const modalContent = document.createElement('div');
                    modalContent.id = 'history-modal-content';

                    // Create a close button for the modal
                    const closeButton = document.createElement('span');
                    closeButton.id = 'history-modal-close';
                    closeButton.innerHTML = '&times;';
                    closeButton.addEventListener('click', function() {
                        modal.style.display = 'none';
                    });

                    modalContent.appendChild(closeButton);

                    // Create a container for history entries
                    const entriesContainer = document.createElement('div');
                    entriesContainer.id = 'history-entries';
                    modalContent.appendChild(entriesContainer);

                    modal.appendChild(modalContent);
                    document.body.appendChild(modal);

                    // Close the modal if clicking outside the modal content
                    modal.addEventListener('click', function(event) {
                        if (event.target === modal) {
                            modal.style.display = 'none';
                        }
                    });
                }

                // Populate the entries container with the history data
                const entriesContainer = document.getElementById('history-entries');
                entriesContainer.innerHTML = ''; // Clear previous content

                if (data.error) {
                    entriesContainer.innerHTML = '<p>Error: ' + data.error + '</p>';
                } else if (!data.empty) {
                    data.entries.forEach(entry => {
                        const entryDiv = document.createElement('div');
                        entryDiv.classList.add('history-entry');

                        // Set the title attribute so that on hover, the description is shown
                        entryDiv.title = entry.video_description || 'No Description';

                        // Format the played_at value using our helper
                        const formattedPlayedAt = formatPlayedAt(entry.played_at);

                        entryDiv.innerHTML = `
                                                <span class="entry-title">${entry.video_title || 'No Title'}</span>
                                                <span class="entry-played">${formattedPlayedAt}</span>
                                              `;
                        entriesContainer.appendChild(entryDiv);
                    });
                } else {
                    entriesContainer.innerHTML = '<p>No history entries found.</p>';
                }

                // Display the modal
                modal.style.display = 'block';
            })
            .catch(error => {
                console.error('Error fetching history:', error);
            });
    });
});
function getYesterdayFormatted() {
    const now = new Date();
    // Subtract one day (24 * 60 * 60 * 1000 milliseconds)
    const yesterday = new Date(now.getTime() - 86400000);
    const year = yesterday.getFullYear();
    const month = String(yesterday.getMonth() + 1).padStart(2, '0');
    const day = String(yesterday.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}
function formatPlayedAt(dateString) {
    const playedAtDate = new Date(dateString);
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const playedDate = new Date(playedAtDate.getFullYear(), playedAtDate.getMonth(), playedAtDate.getDate());

    const hours = String(playedAtDate.getHours()).padStart(2, '0');
    const minutes = String(playedAtDate.getMinutes()).padStart(2, '0');

    // If played_at is today, return HH:MM
    if (playedDate.getTime() === today.getTime()) {
        return `${hours}:${minutes}`;
    }

    // Check for yesterday
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    if (playedDate.getTime() === yesterday.getTime()) {
        return `Yesterday: ${hours}:${minutes}`;
    }

    // Otherwise, return a compact MM-DD HH:MM format
    const month = String(playedAtDate.getMonth() + 1).padStart(2, '0');
    const day = String(playedAtDate.getDate()).padStart(2, '0');
    return `${month}-${day} ${hours}:${minutes}`;
}
