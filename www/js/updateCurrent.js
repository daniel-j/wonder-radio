(function () {
	'use strict';

	function getJSON(url, cb) {
		var x = new XMLHttpRequest();
		x.open('get', url, true);
		x.onload = function () {
			cb(JSON.parse(x.response));
		}
		x.send();
	}

	var currentSong = document.getElementById('currentSong');
	var currentListeners = document.getElementById('currentListeners');
	var currentSongVote = document.getElementById('currentSongVote');
	//var scriptContainer = document.createElement('div');
	//var scriptNode;
	//var jsonpUrl = icecastpInfoUrl;
	//var mountPoint = icecastMount;

	//document.body.appendChild(scriptContainer);

	function update() {
		setTimeout(update, 15*1000);
		getJSON('ajax/radioInfo.php', function (info) {
			currentSong.innerHTML = info.title+(info.artist?' - '+info.artist:'');
			currentListeners.textContent = info.listeners;
			currentSongVote.innerHTML = "";

			var voteUpBtn = document.createElement('button');
			var voteDownBtn = document.createElement('button');

			voteUpBtn.textContent = '▲';
			voteDownBtn.textContent = '▼';

			var voteUp = 'vote up';
			var voteDown = 'vote down';

			if (info.vote === 1) {
				voteUp += ' voted';
				voteDown += ' notvoted';
				voteUpBtn.addEventListener('click', handleVote.bind(voteUpBtn, info.id, 0), false);
				voteDownBtn.addEventListener('click', handleVote.bind(voteDownBtn, info.id, -1), false);
			} else if (info.vote === -1) {
				voteUp += ' notvoted';
				voteDown += ' voted';
				voteUpBtn.addEventListener('click', handleVote.bind(voteUpBtn, info.id, 1), false);
				voteDownBtn.addEventListener('click', handleVote.bind(voteDownBtn, info.id, 0), false);
			} else {
				voteUpBtn.addEventListener('click', handleVote.bind(voteUpBtn, info.id, 1), false);
				voteDownBtn.addEventListener('click', handleVote.bind(voteDownBtn, info.id, -1), false);
			}
			voteUpBtn.className = voteUp;
			voteDownBtn.className = voteDown;

			var currentSongRating = document.createElement('div');
			currentSongRating.textContent = info.rating;

			currentSongVote.appendChild(voteUpBtn);
			currentSongVote.appendChild(currentSongRating);
			currentSongVote.appendChild(voteDownBtn);
		});

		/*if (scriptNode) {
			scriptNode.innerHTML = '';
			scriptNode.src = '';
			scriptContainer.removeChild(scriptNode);
			scriptNode = null;
		}

		scriptNode = document.createElement('script');
		scriptNode.type = "text/javascript";
		scriptNode.src = jsonpUrl+"?timestamp="+Date.now();
		scriptContainer.appendChild(scriptNode);*/
	}

	update();

	/*window.handleIcecastMounts = function (mounts) {
		var info = mounts[mountPoint];
		if (info) {
			currentSong.innerHTML = info.title+(info.artist?' - '+info.artist:'');
			currentListeners.textContent = info.listeners;
		}
	};*/

}());