document.addEventListener('DOMContentLoaded', () => {
    // --- Modal Switching Code ---
    // Login modal elements
    const loginButton = document.getElementById('login-button');
    const loginModal = document.getElementById('login-modal');
    const loginCloseButton = document.querySelector('#login-modal .close');

    // Register modal elements
    const registerModal = document.getElementById('register-modal');
    const registerCloseButton = document.querySelector('#register-modal .close');

    // Show login modal when login button is clicked
    if (loginButton) {
        loginButton.addEventListener('click', () => {
            loginModal.style.display = 'block';
        });
    }

    // Hide login modal when its close button is clicked
    if (loginCloseButton) {
        loginCloseButton.addEventListener('click', () => {
            loginModal.style.display = 'none';
        });
    }

    // Hide register modal when its close button is clicked
    if (registerCloseButton) {
        registerCloseButton.addEventListener('click', () => {
            registerModal.style.display = 'none';
        });
    }

    // Hide modals if user clicks outside the modal content
    window.addEventListener('click', (event) => {
        if (event.target === loginModal) {
            loginModal.style.display = 'none';
        }
        if (event.target === registerModal) {
            registerModal.style.display = 'none';
        }
    });

    // Switch to Register modal from Login modal
    const switchToRegisterLink = document.getElementById('switch-to-register');
    if (switchToRegisterLink && registerModal) {
        switchToRegisterLink.addEventListener('click', (event) => {
            event.preventDefault();
            loginModal.style.display = 'none';
            registerModal.style.display = 'block';
        });
    }

    // Switch to Login modal from Register modal
    const switchToLoginLink = document.getElementById('switch-to-login');
    if (switchToLoginLink && loginModal) {
        switchToLoginLink.addEventListener('click', (event) => {
            event.preventDefault();
            registerModal.style.display = 'none';
            loginModal.style.display = 'block';
        });
    }

    // --- Popup Utility ---
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

    // --- Helper to Convert FormData to a Plain Object ---
    function formDataToObject(formData) {
        const data = {};
        formData.forEach((value, key) => { data[key] = value; });
        return data;
    }

    // --- Update UI on Successful Login ---
    function updateUserUI(username) {
        const settingsPanel = document.getElementById('settings-panel');
        const loginButton = document.getElementById('login-button');
        if (loginButton) {
            // Create a new element to display username and logout link
            const userInfo = document.createElement('span');
            userInfo.id = 'user-info';
            userInfo.innerHTML = `Welcome, ${username}! <a href="#" id="logout">Logout</a>`;
            // Replace the login button with the userInfo element
            settingsPanel.replaceChild(userInfo, loginButton);
        }
    }

    // --- AJAX Login Form Submission ---
    const loginForm = document.querySelector('#login-modal form');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault(); // prevent normal form submission
            const formData = new FormData(loginForm);
            const data = formDataToObject(formData);
            try {
                const response = await fetch(loginForm.action, {
                    method: loginForm.method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': data._token // using the token from the form
                    },
                    body: JSON.stringify(data)
                });
                if (response.ok) {
                    const result = await response.json();
                    loginModal.style.display = 'none'; // hide the login modal
                    showPopup(`Welcome, ${result.user.name}!`, true);
                    document.querySelector('meta[name="csrf-token"]').setAttribute('content', result.csrf_token);
                    updateUserUI(result.user.name); // update the UI with username and logout link
                    setTimeout(function(){window.location.reload();},1000);
                } else {
                    const errResult = await response.json();
                    showPopup(`Error: ${errResult.message || 'Login failed'}`, false);
                }
            } catch (error) {
                showPopup(`Error: ${error.message}`, false);
            }
        });
    }

    // --- AJAX Registration Form Submission ---
    const registerForm = document.querySelector('#register-modal form');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault(); // prevent normal form submission
            const formData = new FormData(registerForm);
            const data = formDataToObject(formData);
            try {
                const response = await fetch(registerForm.action, {
                    method: registerForm.method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': data._token
                    },
                    body: JSON.stringify(data)
                });
                if (response.ok) {
                    const result = await response.json();
                    registerModal.style.display = 'none'; // hide the register modal
                    showPopup(`Welcome, ${result.user.name}!`, true);
                    updateUserUI(result.user.name);
                    setTimeout(function(){window.location.reload();},1000);
                } else {
                    const errResult = await response.json();
                    showPopup(`Error: ${errResult.message || 'Registration failed'}`, false);
                }
            } catch (error) {
                showPopup(`Error: ${error.message}`, false);
            }
        });
    }
});
