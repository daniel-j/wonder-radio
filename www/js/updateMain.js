(function () {
	'use strict';

	function getText(url, cb) {
		var x = new XMLHttpRequest();
		x.open('get', url, true);
		x.onload = function () {
			cb(x.response);
		}
		x.send();
		return x;
	}

	function getJSON(url, cb) {
		getText(url, function (text) {
			cb(JSON.parse(text));
		})
	}

	function prettyTimestamp(t) {
		var d = new Date(t*1000-(Date.now()-servertime));
		var h = d.getHours();
		var m = d.getMinutes();
		if (h < 10) h = "0"+h;
		if (m < 10) m = "0"+m;
		return h+":"+m;
	}

	function prettyTimestampSearch(t) {
		var d = new Date(t*1000-(Date.now()-servertime));
		var h = d.getHours();
		var m = d.getMinutes();
		if (h < 10) h = "0"+h;
		if (m < 10) m = "0"+m;
		return d.getDate()+"/"+(d.getMonth()+1)+"&nbsp;"+h+":"+m;
	}

	var geocanvas = document.getElementById('geocanvas');
	var geoctx = geocanvas.getContext('2d');

	var playlistBody = document.getElementById('playlistBody');
	var searchContainer = document.getElementById('searchContainer');
	var searchResult = document.getElementById('searchResult');
	var searchBody = document.getElementById('searchBody');
	var searchForm = document.getElementById('searchForm');
	var searchInput = document.getElementById('searchInput');

	var searchQuery = "";
	var servertime = 0;

	var timerPlaylist = null;
	var timerSearch = null;
	var timerMap = null;

	var playlistXhr = null;
	var searchXhr = null;
	var mapXhr = null;

	function handleVote(trackId, vote, e) {
		getText("ajax/vote.php?trackId="+trackId+"&vote="+vote, function () {
			updatePlaylist();
			updateSearch();
		});
	}

	function handleRequest(trackId, e) {
		getText("ajax/request.php?trackId="+trackId, function (text) {
			console.log(text);
			updatePlaylist(true);
			updateSearch();
		});
	}

	function updatePlaylist(userAction) {
		if (playlistXhr) playlistXhr.abort();
		clearTimeout(timerPlaylist);
		timerPlaylist = setTimeout(updatePlaylist, 20*1000);

		playlistXhr = getJSON('ajax/playlist.php', function (playlist) {
			playlistXhr = null;
			servertime = playlist.time*1000;
			var queue = playlist.queue;
			var history = playlist.history;

			while (playlistBody.rows.length > 0) {
				playlistBody.deleteRow(0);
			}

			for (var i = 0; i < queue.length; i++) {
				var track = queue[i];
				var row = playlistBody.insertRow(-1);
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
				cells[6].textContent = track.rating;
			}

			for (var i = 0; i < history.length; i++) {
				var track = history[i];
				var row = playlistBody.insertRow(-1);
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

				cells[5].appendChild(voteUpBtn);
				cells[6].textContent = track.rating;
				cells[7].appendChild(voteDownBtn);
			}

			if (userAction) {
				document.body.scrollTop = 0;
			}
		});
		
	}
	
	
	function updateSearch(userAction) {
		if (searchXhr) searchXhr.abort();
		clearTimeout(timerSearch);
		timerSearch = setTimeout(updateSearch, 20*1000);

		searchXhr = getJSON("ajax/search.php?q="+encodeURIComponent(searchQuery), function (response) {
			searchXhr = null;
			servertime = response.time*1000;

			while (searchBody.rows.length > 0) {
				searchBody.deleteRow(0);
			}

			for (var i = 0; i < response.result.length; i++) {
				var track = response.result[i];
				var row = searchBody.insertRow(-1);
				var cells = [];
				while (cells.length < 7) {
					cells.push(row.insertCell(-1));
				}


				if (track.timePlayed > 0) {
					cells[0].innerHTML = prettyTimestampSearch(track.timePlayed);
				}
				
				cells[1].textContent = track.title;
				cells[2].textContent = track.artist;
				cells[3].textContent = track.playCount;
				cells[4].textContent = track.requestCount;
				cells[5].textContent = track.rating;

				var timeLeft = (response.time-track.timePlayed)/60;

				if (track.isQueued) {
					cells[6].textContent = "Queued";
					row.className = 'queued';
				} else if (track.timePlayed !== 0 && timeLeft < response.trackWait) {
					
					cells[6].innerHTML = "<strong>"+Math.round(response.trackWait-timeLeft)+"</strong>&nbsp;min&nbsp;left";
					row.className = 'unqueueable';
				} else if (response.queueFull) {
					cells[6].innerHTML = "Queue&nbsp;full";
					row.className = 'unqueueable';
				} else if (response.remaining > 0) {
					cells[6].textContent = "";
				} else {
					var requestBtn = document.createElement('button');
					requestBtn.textContent = "►";
					cells[6].appendChild(requestBtn);
					requestBtn.addEventListener('click', handleRequest.bind(requestBtn, track.id));
				}
				
			}

			if (userAction) {
				setTimeout(function () {
					searchContainer.scrollIntoView();
				}, 100);
			}

		});
	}

	function doSearch() {
		searchQuery = searchInput.value;
		if (searchQuery === "") {
			removeSearch();
		} else {
			updateSearch(true);
			searchContainer.scrollIntoView();
			searchResult.classList.add('show');
		}
	}
	function removeSearch() {
		if (searchXhr) searchXhr.abort();
		clearTimeout(timerSearch);

		while (searchBody.rows.length > 0) {
			searchBody.deleteRow(0);
		}
		searchResult.classList.remove('show');

	}


	function updateMap() {
		if (mapXhr) mapXhr.abort();
		clearTimeout(timerMap);
		timerMap = setTimeout(updateMap, 20*1000);

		getJSON("ajax/map.php", function (coords) {
			mapXhr = null;

			geoctx.clearRect(0, 0, geocanvas.width, geocanvas.height);
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

	updatePlaylist();
	updateMap();
	doSearch();
	

	searchForm.addEventListener('submit', function (e) {
		e.preventDefault();
		doSearch();
	}, false);
}());
