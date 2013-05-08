// Created by djazz

(function () {


	var playstopbtn = document.getElementById('playstopbtn');
	var radioVolume = document.getElementById('radioVolume');
	var audioTag;
	var isPlaying = false;

	playstopbtn.addEventListener('click', togglePlay, false);
	if (radioVolume) {
		radioVolume.addEventListener('change', function () {
			if (audioTag) {
				audioTag.volume = radioVolume.value;
			}
		}, false);
	}


	function togglePlay() {
		if (isPlaying) {
			stopRadio();
		} else {
			startRadio();
		}
	}

	function startRadio() {
		stopRadio();
		isPlaying = true;

		playstopbtn.textContent = "Buffering";

		audioTag = new Audio();
		audioTag.addEventListener('error', handleStreamEnded, false);
		audioTag.addEventListener('ended', handleStreamEnded, false);
		audioTag.addEventListener('canplay', handleStreamCanPlay, false);
		audioTag.src = stationUrl;
		audioTag.volume = radioVolume? radioVolume.value : 1.0;
		audioTag.play();
	}

	function stopRadio() {
		isPlaying = false;
		if (audioTag) {
			audioTag.removeEventListener('error', handleStreamEnded);
			audioTag.removeEventListener('ended', handleStreamEnded);
			audioTag.removeEventListener('canplay', handleStreamCanPlay);
			audioTag.pause();

			audioTag.src = '';
			audioTag.load();
			audioTag = null;
		}
		playstopbtn.textContent = "Play";
	}


	function handleStreamEnded() {
		setTimeout(function () {
			if (isPlaying) {
				startRadio();
			}
		}, 1000);
	}

	function handleStreamCanPlay() {
		if (isPlaying) {
			playstopbtn.textContent = "Stop";
		}
	}

	playstopbtn.disabled = false;
	if (autoplay) {
		startRadio();
	} else {
		stopRadio();
	}
}());