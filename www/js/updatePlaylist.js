(function () {
	'use strict';

	function getText(url, cb) {
		var x = new XMLHttpRequest();
		x.open('get', url, true);
		x.onload = function () {
			cb(x.response);
		}
		x.send();
	}

	function prettyTimestamp(t) {
		var d = new Date(t*1000-(Date.now()-servertime));
		var h = d.getHours();
		var m = d.getMinutes();
		if (h < 10) h = "0"+h;
		if (m < 10) m = "0"+m;
		return h+":"+m;
	}

	var geocanvas = document.getElementById('geocanvas');
	var geoctx = geocanvas.getContext('2d');

	var container = document.getElementById('playlistBody');
	var servertime = 0;
	var timer = null;

	function handleVote(trackId, vote, e) {
		getText("vote.php?trackId="+trackId+"&vote="+vote, function () {
			clearTimeout(timer);
			update();
		});
	}

	function update() {
		timer = setTimeout(update, 20*1000);
		getText('ajax/playlist.php', function (json) {
			var playlist = JSON.parse(json);
			servertime = playlist.time*1000;
			var queue = playlist.queue;
			var history = playlist.history;

			while (container.rows.length > 0) {
				container.deleteRow(0);
			}

			for (var i = 0; i < queue.length; i++) {
				var track = queue[i];
				var row = container.insertRow(-1);
				var cells = [];
				while (cells.length < 8) {
					cells.push(row.insertCell(-1));
				}
				row.classList.add('queued');
				cells[0].textContent = prettyTimestamp(track.timeAdded);
				cells[1].textContent = track.title;
				cells[2].textContent = track.artist;
				cells[3].textContent = track.playCount;
				cells[4].textContent = track.requestCount;
				cells[5].textContent = track.rating;
			}
			for (var i = 0; i < history.length; i++) {
				var track = history[i];
				var row = container.insertRow(-1);
				var cells = [];
				while (cells.length < 8) {
					cells.push(row.insertCell(-1));
				}
				if (i === 0) {
					row.classList.add('current');
				} else {
					cells[0].textContent = prettyTimestamp(track.timePlayed);
				}
				cells[1].textContent = track.title;
				cells[2].textContent = track.artist;
				cells[3].textContent = track.playCount;
				cells[4].textContent = track.requestCount;
				cells[5].textContent = track.rating;

				var voteUpBtn = document.createElement('button');
				var voteDownBtn = document.createElement('button');

				voteUpBtn.textContent = '▲';
				voteDownBtn.textContent = '▼';

				var voteUp = 'vote up';
				var voteDown = 'vote down';

				if (track.vote === 1) {
					voteUp += ' voted';
					voteDown += ' notvoted';
					voteUpBtn.addEventListener('click', handleVote.bind(voteUpBtn, track.id, 0), false);
					voteDownBtn.addEventListener('click', handleVote.bind(voteDownBtn, track.id, -1), false);
				} else if (track.vote === -1) {
					voteUp += ' notvoted';
					voteDown += ' voted';
					voteUpBtn.addEventListener('click', handleVote.bind(voteUpBtn, track.id, 1), false);
					voteDownBtn.addEventListener('click', handleVote.bind(voteDownBtn, track.id, 0), false);
				} else {
					voteUpBtn.addEventListener('click', handleVote.bind(voteUpBtn, track.id, 1), false);
					voteDownBtn.addEventListener('click', handleVote.bind(voteDownBtn, track.id, -1), false);
				}
				voteUpBtn.className = voteUp;
				voteDownBtn.className = voteDown;

				cells[6].appendChild(voteUpBtn);
				cells[7].appendChild(voteDownBtn);
			}
		});
		
	}
	update();


	function updateMap() {
		setTimeout(updateMap, 20*1000);
		getText("ajax/map.php", function (json) {
			geoctx.clearRect(0, 0, geocanvas.width, geocanvas.height);
			var coords = JSON.parse(json);
			for (var i = 0; i < coords.length; i++) {
				var x = coords[i][0];
				var y = coords[i][1];
				geoctx.fillStyle = "white";
				geoctx.fillRect(x-4, y-4, 8, 8);
				geoctx.fillStyle = "#ff6982";
				geoctx.fillRect(x-3, y-3, 6, 6);
			}
		});
	}
	updateMap();
}());
