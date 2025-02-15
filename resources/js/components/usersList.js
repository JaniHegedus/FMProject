// Function to fetch chat users and show the popup
export function fetchChatUsersAndShowPopup(type) {
    switch (type){
        case 'chat':
            fetch('/chatusers')
                .then(response => response.json())
                .then(data => {
                    let content = '';
                    if (data.empty) {
                        content = '<p>No chat users available.</p>';
                    } else {
                        // Assuming each entry contains a "username" field; adjust if needed.
                        content = '<ul style="list-style: none; padding: 0; margin: 0;">';
                        data.entries.forEach(user => {
                            // Replace "username" with the correct property if different.
                            content += `<li style="padding: 5px 0; border-bottom: 1px solid #444;">${user.userName || 'Anonymous'}</li>`;
                        });
                        content += '</ul>';
                    }
                    showPopup(content);
                })
                .catch(err => {
                    console.error('Error fetching chat users:', err);
                    showPopup('<p>Error loading chat users.</p>');
                });
            break;
        case 'listeners':
            fetch('/chatusers')
                .then(response => response.json())
                .then(data => {
                    let content = '';
                    if (data.empty) {
                        content = '<p>No listeners available.</p>';
                    } else {
                        // Assuming each entry contains a "username" field; adjust if needed.
                        content = '<ul style="list-style: none; padding: 0; margin: 0;">';
                        data.entries.forEach(user => {
                            console.log(user);
                            // Replace "username" with the correct property if different.
                            content += `<li style="padding: 5px 0; border-bottom: 1px solid #444;">${user.userName || 'Anonymous'}</li>`;
                        });
                        content += '</ul>';
                    }
                    showPopup(content);
                })
                .catch(err => {
                    console.error('Error fetching chat users:', err);
                    showPopup('<p>Error loading chat users.</p>');
                });
            break;
        default:
            break;
    }

}

// Function to create and display a dark-themed popup overlay
function showPopup(htmlContent) {
    // Create overlay
    const overlay = document.createElement('div');
    overlay.style.position = 'fixed';
    overlay.style.top = '0';
    overlay.style.left = '0';
    overlay.style.width = '100vw';
    overlay.style.height = '100vh';
    overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
    overlay.style.display = 'flex';
    overlay.style.alignItems = 'center';
    overlay.style.justifyContent = 'center';
    overlay.style.zIndex = '1000';

    // Create popup container
    const popup = document.createElement('div');
    popup.style.backgroundColor = '#333';
    popup.style.color = '#fff';
    popup.style.padding = '20px';
    popup.style.borderRadius = '8px';
    popup.style.maxWidth = '400px';
    popup.style.width = '80%';
    popup.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.5)';
    popup.innerHTML = htmlContent;

    // Create close button
    const closeBtn = document.createElement('button');
    closeBtn.innerText = 'Close';
    closeBtn.style.marginTop = '15px';
    closeBtn.style.padding = '10px 15px';
    closeBtn.style.backgroundColor = '#555';
    closeBtn.style.color = '#fff';
    closeBtn.style.border = 'none';
    closeBtn.style.borderRadius = '4px';
    closeBtn.style.cursor = 'pointer';

    closeBtn.addEventListener('click', function() {
        document.body.removeChild(overlay);
    });

    popup.appendChild(closeBtn);
    overlay.appendChild(popup);
    document.body.appendChild(overlay);
}
