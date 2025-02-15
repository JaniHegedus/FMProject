import {token, currentUser,pageOpenTimeCarbon} from "./app.js";

document.addEventListener('DOMContentLoaded', function() {
    let messageRefreshInterval;

    // Create the container for the site logo and CHAT button
    const chatContainer = document.createElement('div');
    chatContainer.id = 'chat-container';

    // Create the site logo element
    const siteLogo = document.createElement('img');
    siteLogo.id = 'site-logo';
    siteLogo.src = '/assets/radio-icon.webp';
    siteLogo.alt = 'JaniHegedus FM';
    chatContainer.appendChild(siteLogo);

    // Create the CHAT button element with id "chat-button"
    const chatButton = document.createElement('button');
    chatButton.id = 'chat-button';
    chatButton.textContent = 'CHAT';
    chatButton.addEventListener('click', toggleChat);
    chatContainer.appendChild(chatButton);

    // Append the chat container to the document body
    document.body.appendChild(chatContainer);

    // Create the chat popup element
    const chatPopup = document.createElement('div');
    chatPopup.id = 'chat-popup';

    // Create the chat header with title and close button
    const chatHeader = document.createElement('div');
    chatHeader.className = 'chat-header';
    const chatTitle = document.createElement('span');
    chatTitle.textContent = 'Chat';
    chatHeader.appendChild(chatTitle);
    const closeButton = document.createElement('button');
    closeButton.textContent = 'X'; // using a multiplication sign for a nicer look
    closeButton.className = 'close-button';
    closeButton.addEventListener('click', toggleChat);
    chatHeader.appendChild(closeButton);
    chatPopup.appendChild(chatHeader);


    // Create the chat messages container
    const chatMessages = document.createElement('div');
    chatMessages.className = 'chat-messages';
    chatPopup.appendChild(chatMessages);

    // Create the chat input area
    const chatInput = document.createElement('div');
    chatInput.className = 'chat-input';
    const inputField = document.createElement('input');
    inputField.type = 'text';
    inputField.placeholder = 'Type your message...';
    chatInput.appendChild(inputField);
    const sendButton = document.createElement('button');
    sendButton.textContent = 'Send';
    sendButton.addEventListener('click', function() {
        const text = inputField.value.trim();
        if (text !== '') {
            postMessage(text);
            inputField.value = '';
        }
    });
    chatInput.appendChild(sendButton);
    chatPopup.appendChild(chatInput);

    // Append the chat popup to the document body
    document.body.appendChild(chatPopup);

    // Enable sending message with the Enter key
    inputField.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            const text = inputField.value.trim();
            if (text !== '') {
                postMessage(text);
                inputField.value = '';
            }
        }
    });

    // Toggle the chat popup's visibility
    function toggleChat() {
        if (chatPopup.style.display === 'none' || chatPopup.style.display === '') {
            chatPopup.style.display = 'flex';
            loadMessages(true); // Force update when opening chat
            messageRefreshInterval = setInterval(() => loadMessages(false), 5000);
        } else {
            chatPopup.style.display = 'none';
            clearInterval(messageRefreshInterval);
        }
    }

    // Fetch messages from the server
    function loadMessages(forceUpdate = false) {
        fetch(`/messages/${encodeURIComponent(pageOpenTimeCarbon)}`)
            .then(response => response.json())
            .then(data => {
                // Check if user is scrolled to the bottom (within 30px threshold)
                const threshold = 30;
                const isAtBottom = (chatMessages.scrollTop + chatMessages.clientHeight) >= (chatMessages.scrollHeight - threshold);
                if (!forceUpdate && !isAtBottom) {
                    console.log("User is reading older messages; skipping refresh.");
                    return;
                }
                chatMessages.innerHTML = '';
                if (!data.empty) {
                    data.entries.forEach(message => {
                        const messageDiv = document.createElement('div');
                        messageDiv.className = 'chat-message';

                        // Sender (or "Anonymous")
                        const senderDiv = document.createElement('div');
                        senderDiv.className = 'message-sender';
                        senderDiv.textContent = message.userName ?? 'Anonymous';
                        messageDiv.appendChild(senderDiv);

                        // Message content
                        const contentDiv = document.createElement('div');
                        contentDiv.className = 'message-content';
                        contentDiv.textContent = message.content;
                        messageDiv.appendChild(contentDiv);

                        // Timestamp
                        const timestampDiv = document.createElement('div');
                        timestampDiv.className = 'message-timestamp';
                        const sentDate = new Date(message.created_at);
                        timestampDiv.textContent = sentDate.toLocaleTimeString();
                        messageDiv.appendChild(timestampDiv);

                        chatMessages.appendChild(messageDiv);
                    });
                } else {
                    postMessage((currentUser ? currentUser.name : 'Anonymous')+' joined the chatroom!')
                }
                chatMessages.scrollTop = chatMessages.scrollHeight;
            })
            .catch(error => console.error('Error fetching messages:', error));
    }

    // Post a new message using the /send-message endpoint
    function postMessage(text) {
        const payload = currentUser
            ? { sender: currentUser.id, content: text }
            : { content: text };

        fetch('/send-message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify(payload)
        })
            .then(response => response.json())
            .then(() => {
                loadMessages(true);
            })
            .catch(error => console.error('Error sending message:', error));
    }

    // Custom resizable behavior: add handles on all sides and corners
    function makeResizable(el) {
        const handles = ['top', 'right', 'bottom', 'left', 'top-right', 'top-left', 'bottom-right', 'bottom-left'];
        let currentHandle = null;
        let startX, startY, startWidth, startHeight, startTop, startLeft;

        handles.forEach(handle => {
            const handleEl = document.createElement('div');
            handleEl.classList.add('resize-handle', handle);
            // Set a data attribute for easier identification
            handleEl.dataset.handle = handle;
            el.appendChild(handleEl);

            handleEl.addEventListener('mousedown', function(e) {
                e.preventDefault();
                currentHandle = handle;
                startX = e.clientX;
                startY = e.clientY;
                const rect = el.getBoundingClientRect();
                startWidth = rect.width;
                startHeight = rect.height;
                startTop = rect.top;
                startLeft = rect.left;
                document.addEventListener('mousemove', resize);
                document.addEventListener('mouseup', stopResize);
            });
        });

        function resize(e) {
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;

            // Sides only: adjust one dimension
            if (currentHandle === 'right') {
                el.style.width = (startWidth + dx) + 'px';
            } else if (currentHandle === 'left') {
                el.style.width = (startWidth - dx) + 'px';
                el.style.left = (startLeft + dx) + 'px';
            } else if (currentHandle === 'bottom') {
                el.style.height = (startHeight + dy) + 'px';
            } else if (currentHandle === 'top') {
                el.style.height = (startHeight - dy) + 'px';
                el.style.top = (startTop + dy) + 'px';
            }
            // Corners: adjust both dimensions
            else if (currentHandle === 'top-right') {
                el.style.width = (startWidth + dx) + 'px';
                el.style.height = (startHeight - dy) + 'px';
                el.style.top = (startTop + dy) + 'px';
            } else if (currentHandle === 'top-left') {
                el.style.width = (startWidth - dx) + 'px';
                el.style.left = (startLeft + dx) + 'px';
                el.style.height = (startHeight - dy) + 'px';
                el.style.top = (startTop + dy) + 'px';
            } else if (currentHandle === 'bottom-right') {
                el.style.width = (startWidth + dx) + 'px';
                el.style.height = (startHeight + dy) + 'px';
            } else if (currentHandle === 'bottom-left') {
                el.style.width = (startWidth - dx) + 'px';
                el.style.left = (startLeft + dx) + 'px';
                el.style.height = (startHeight + dy) + 'px';
            }
        }

        function stopResize() {
            document.removeEventListener('mousemove', resize);
            document.removeEventListener('mouseup', stopResize);
            currentHandle = null;
        }
    }


    // Enable custom resizing on the chat popup
    makeResizable(chatPopup);

});
