// videoLoader.js
import {player, playing, currentVideoId, updateCurrentVideoId} from './player.js';
import {currentUser,getUserIP} from "./../app.js";

export async function loadNextVideo() {
  try {
      const ip = await getUserIP();
      const queryParams = new URLSearchParams({
          user_id: (currentUser)? currentUser.id : null,
          ip: ip
      });
      //console.info(currentUser);
      const response = await fetch(`/currentVideo?${queryParams.toString()}`);
      const data = await response.json();

    if (data.error) {
      console.error("Error fetching next video:", data.error);
      return;
    }

    if (data.video_id !== currentVideoId) {
      // Update the video player with the new video.
      player.loadVideoById(data.video_id, data.progress ?? 0);
      updateCurrentVideoId(data.video_id);
      document.getElementById("video-title").textContent = data.video_title;
      document.getElementById("video-start-time").textContent = data.start_time;
      if (data.requester !== '')
        document.getElementById("requester-text").textContent = 'Requested by: ' + data.requester;
      else
        document.getElementById("requester-text").textContent = '';
    }
  } catch (error) {
    console.error("Error fetching next video:", error);
  }
}

export async function checkForNewVideo() {
  try {
    if (playing) {
        const ip = await getUserIP();
        const queryParams = new URLSearchParams({
            user_id: (currentUser)? currentUser.id : null,
            ip: ip
        });
        const response = await fetch(`/currentVideo?${queryParams.toString()}`);
        const data = await response.json();

        if (data.error) {
            console.error("Error fetching current video:", data.error);
            return;
        }

        if (data.video_id !== currentVideoId) {
            console.log("New video detected:", data.video_id);
            await loadNextVideo();
        }
    }
    setTimeout(() => checkForNewVideo(), 5000);
  } catch (error) {
    console.error("Error checking for new video:", error);
  }
}
