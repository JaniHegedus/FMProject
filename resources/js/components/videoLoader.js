// videoLoader.js
import { player, playing, currentVideoId } from './player.js';

export async function loadNextVideo() {
  try {
    const response = await fetch('/currentVideo');
    const data = await response.json();

    if (data.error) {
      console.error("Error fetching next video:", data.error);
      return;
    }

    if (data.video_id !== currentVideoId) {
      // Update the video player with the new video.
      player.loadVideoById(data.video_id, data.progress ?? 0);
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
      const response = await fetch('/currentVideo');
      const data = await response.json();

      if (data.error) {
        console.error("Error fetching current video:", data.error);
        return;
      }

      if (data.video_id !== currentVideoId) {
        console.log("New video detected:", data.video_id);
        currentVideoId = data.video_id;
        loadNextVideo();
      }
    }
    setTimeout(() => checkForNewVideo(), 5000);
  } catch (error) {
    console.error("Error checking for new video:", error);
  }
}
