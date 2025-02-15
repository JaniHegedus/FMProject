import {chatContainer} from './chat.js';
import {fetchChatUsersAndShowPopup} from "./usersList.js";

const listenersCount = document.createElement('div');
export function checkListenerCount(){
    fetch(`/listeners-count`)
        .then(response => response.json())
        .then(data => {
            if(!data.empty){
                listenersCount.textContent = (data.listenerCount)? 'Users listening: '+data.listenerCount : '';
                listenersCount.style.display = 'block';
            }else{
                listenersCount.style.display = 'none';
            }
        })
    setTimeout(() => checkListenerCount(), 5000);
}
document.addEventListener('DOMContentLoaded', function() {
    listenersCount.className ='listeners-count';
    listenersCount.style.display = 'none';
    listenersCount.addEventListener('click', function(){
        fetchChatUsersAndShowPopup('listeners');
    });
    chatContainer.appendChild(listenersCount);
});
