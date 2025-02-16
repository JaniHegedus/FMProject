import {chatContainer} from './chat.js';
import {fetchUsersAndShowPopup} from "./usersList.js";

const listenersCount = document.createElement('div');
export function checkListenerCount(){
    fetch(`/listeners-count`)
        .then(response => response.json())
        .then(data => {
            if(!data.empty){
                listenersCount.textContent = (data.listenerCount)? 'Listeners: '+data.listenerCount : '';
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
        fetchUsersAndShowPopup('listeners');
    });
    chatContainer.appendChild(listenersCount);
});
