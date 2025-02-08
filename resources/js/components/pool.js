import { fetchLoggedInUser } from '../app.js';

// Define a global variable to store current pool entries.
let currentPoolEntries = [];

/**
 * Helper function to update currentPoolEntries by calling the pool status endpoint.
 */
function updateCurrentPoolEntries() {
    fetch('/pool-status', {
        headers: { 'Accept': 'application/json' }
    })
        .then(response => response.json())
        .then(data => {
            if (!data.empty && data.entries && data.entries.length > 0) {
                currentPoolEntries = data.entries;
            } else {
                currentPoolEntries = [];
            }
        })
        .catch(error => console.error('Error updating pool entries:', error));
}

// Dynamically create the "Create a Pool" button and attach the popup logic
document.addEventListener('DOMContentLoaded', function () {
    // Assuming you have a container for user settings; if not, use document.body
    const settingsPanel = document.getElementById('settings-panel') || document.body;

    // Create the Create a Pool button dynamically
    const createPoolBtn = document.createElement('button');
    createPoolBtn.id = 'create-pool-btn';
    createPoolBtn.textContent = 'Create a Pool';
    createPoolBtn.style.display = 'none'; // Hide it by default
    settingsPanel.appendChild(createPoolBtn);

    // Check if a user is logged in; if so, show the button
    fetchLoggedInUser().then(user => {
        if (user && user.id) {
            createPoolBtn.style.display = 'block';
        }
    });

    // When the button is clicked, show the pool popup
    createPoolBtn.addEventListener('click', showPoolPopup);
});

function showPoolPopup() {
    // Create modal overlay (the semi-transparent background)
    const modalOverlay = document.createElement('div');
    modalOverlay.id = 'pool-popup-overlay';
    // (Assume your CSS handles styling for the overlay)

    // Create modal content container
    const modalContent = document.createElement('div');
    modalContent.className = 'modal-content';

    // Create a close button (×)
    const closeButton = document.createElement('span');
    closeButton.innerHTML = '&times;';
    closeButton.className = 'close-btn';
    modalContent.appendChild(closeButton);

    // Create a header for the popup
    const header = document.createElement('h2');
    header.textContent = 'Create a Pool';
    modalContent.appendChild(header);

    // Create a container for the search bar elements
    const searchContainer = document.createElement('div');
    searchContainer.className = 'search-container';

    // Create the search input element
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Search for a song...';
    searchInput.className = 'search-input';
    searchContainer.appendChild(searchInput);

    // (Optional) You can remove the search button if auto-search is enabled.
    // const searchBtn = document.createElement('button');
    // searchBtn.textContent = 'Search';
    // searchBtn.className = 'search-btn';
    // searchContainer.appendChild(searchBtn);

    modalContent.appendChild(searchContainer);

    // Create container for search results
    const resultsContainer = document.createElement('div');
    resultsContainer.id = 'dynamic-search-results';
    modalContent.appendChild(resultsContainer);

    // Create the submit button
    const submitBtn = document.createElement('button');
    submitBtn.textContent = 'Submit Selected Song';
    submitBtn.className = 'submit-btn';
    modalContent.appendChild(submitBtn);

    // Append modal content to overlay and overlay to body
    modalOverlay.appendChild(modalContent);
    document.body.appendChild(modalOverlay);

    // Function to remove the popup
    function closePopup() {
        document.body.removeChild(modalOverlay);
    }

    // Attach event listeners to close the popup
    closeButton.addEventListener('click', closePopup);
    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) {
            closePopup();
        }
    });

    // Initialize variable to hold the selected video ID
    let selectedVideoId = null;

    // Debounce function to avoid excessive calls.
    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    // Auto search: listen for input events on the search field.
    searchInput.addEventListener('input', debounce(function () {
        updateCurrentPoolEntries();
        const query = searchInput.value.trim();
        if (!query) {
            resultsContainer.innerHTML = '';
            return;
        }
        // Fetch search results from your endpoint.
        fetch(`/search-song?query=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                // Clear previous search results.
                resultsContainer.innerHTML = '';
                if (data.results && data.results.length > 0) {
                    data.results.forEach(result => {
                        const resultDiv = document.createElement('div');
                        resultDiv.textContent = result.title;
                        resultDiv.dataset.videoId = result.video_id;
                        resultDiv.className = 'search-result';
                        resultDiv.style.padding = '5px';

                        // Check if this video_id is already in the current pool.
                        const alreadyInPool = currentPoolEntries.some(entry => entry.video_id === result.video_id);

                        if (alreadyInPool) {
                            // Gray out and disable clicking if already submitted.
                            resultDiv.classList.add('submitted');
                            resultDiv.style.cursor = 'not-allowed';
                            resultDiv.title = 'Already submitted to the pool';
                        } else {
                            resultDiv.style.cursor = 'pointer';
                            resultDiv.addEventListener('click', function () {
                                // Unselect any previously selected result.
                                const prevSelected = resultsContainer.querySelector('.selected');
                                if (prevSelected) {
                                    prevSelected.classList.remove('selected');
                                    prevSelected.style.backgroundColor = '';
                                }
                                resultDiv.classList.add('selected');
                                resultDiv.style.backgroundColor = '#cce5ff';
                                selectedVideoId = result.video_id;
                            });
                        }
                        resultsContainer.appendChild(resultDiv);
                    });
                } else {
                    resultsContainer.textContent = 'No results found.';
                }
            })
            .catch(error => {
                console.error('Error during song search:', error);
                resultsContainer.textContent = 'An error occurred while searching.';
            });
    }, 300)); // 300ms debounce delay

    // Event: Submit button click posts the selected song to the /start-pool endpoint
    submitBtn.addEventListener('click', function () {
        if (!selectedVideoId) {
            alert('Please select a song from the search results.');
            return;
        }
        const data = { video_id: selectedVideoId};
        // Retrieve CSRF token from meta tag.
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
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.error) {
                alert('Error: ' + result.error);
            } else {
                alert('Song submitted successfully: ' + result.video_title);
                closePopup();
                // After a successful submission, update the pool entries.
                updateCurrentPoolEntries();
            }
        })
        .catch(error => {
            console.error('Error submitting song:', error);
            alert('An error occurred while submitting the song.');
        });
    });
}
