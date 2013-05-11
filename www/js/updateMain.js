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
		return getText(url, function (text) {
			cb(JSON.parse(text));
		});
	}

	function postForm(url, form, cb) {
		var x = new XMLHttpRequest();
		x.open('post', url, true);
		x.onload = function () {
			cb(x.response);
		}
		x.send(new FormData(form));
		return x;
	}

	function postFormJSON(url, form, cb) {
		return postForm(url, form, function (text) {
			cb(JSON.parse(text));
		});
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

	var playlistContainer = document.getElementById('playlistContainer');
	var playlistBody = document.getElementById('playlistBody');

	var searchContainer = document.getElementById('searchContainer');
	var toggleSearch = document.getElementById('toggleSearch');
	var searchResult = document.getElementById('searchResult');
	var searchBody = document.getElementById('searchBody');
	var searchForm = document.getElementById('searchForm');
	var searchInput = document.getElementById('searchInput');
	var queueWait = document.getElementById('queueWait');
	var searchPagination = document.getElementById('searchPagination');

	var suggestContainer = document.getElementById('suggestContainer');
	var toggleSuggestions = document.getElementById('toggleSuggestions');
	var suggestForm = document.getElementById('suggestForm');
	var suggestWait = document.getElementById('suggestWait');
	var suggestTable = document.getElementById('suggestTable');
	var suggestBody = document.getElementById('suggestBody');
	var suggestPagination = document.getElementById('suggestPagination');

	var searchQuery = "";

	var searchPage = 1;
	var suggestPage = 1;

	var servertime = 0;

	var timerPlaylist = null;
	var timerSearch = null;
	var timerSearchInput = null;
	var timerMap = null;
	var timerSearchInput = null;
	var timerSuggest = null;

	var playlistXhr = null;
	var searchXhr = null;
	var suggestXhr = null;
	var mapXhr = null;

	var currentTrackId = 0;

	function handleVote(trackId, vote, e) {
		getText("ajax/vote.php?trackId="+trackId+"&vote="+vote, function () {
			updatePlaylist();
			updateSearch();
			if (typeof updateNowPlaying === 'function') {
				updateNowPlaying();
			}
		});
	}

	function handleRequest(trackId, e) {
		getText("ajax/request.php?trackId="+trackId, function (text) {
			updatePlaylist(true);
			searchContainer.classList.remove('show');
			removeSearch();
		});
	}

	function searchChangePage(pageId) {
		searchPage = pageId;
		updateSearch();
	}

	function toggleReject(suggestId, rejected) {
		getText("ajax/suggest.php?id="+suggestId+"&reject="+(rejected?1:0), function (text) {
			updateSuggestions();
		});
	}

	function suggestionAccepted(suggestId) {
		if (confirm("Make sure you have added this suggestion to the music database before continuing.\nYou can not undo this.")) {
			getText("ajax/suggest.php?id="+suggestId+"&accept", function (text) {
				updateSuggestions();
			});
		}
	}

	function suggestChangePage(pageId) {
		suggestPage = pageId;
		updateSuggestions();
	}

	function updatePlaylist(userAction) {
		if (playlistXhr) {
			playlistXhr.abort();
			playlistXhr = null;
		}
		clearTimeout(timerPlaylist);
		timerPlaylist = setTimeout(updatePlaylist, 10*1000);

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

					if (currentTrackId !== 0 && currentTrackId !== track.id && typeof updateNowPlaying === 'function') {
						updateNowPlaying(track.id);
					}
					currentTrackId = track.id;

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
				playlistContainer.scrollIntoView();
			}
		});
		
	}
	
	
	function updateSearch(userAction) {
		if (searchXhr) {
			searchXhr.abort();
			searchXhr = null;
		}
		clearTimeout(timerSearch);
		if (!searchContainer.classList.contains('show')) return;
		timerSearch = setTimeout(updateSearch, 20*1000);

		searchXhr = getJSON("ajax/search.php?q="+encodeURIComponent(searchQuery)+"&page="+searchPage, function (response) {
			searchXhr = null;
			servertime = response.time*1000;
			var remaining = response.remaining;
			if (remaining > 0) {
				var s = remaining === 1 ? "" : "s";
				queueWait.innerHTML = "<br>You must wait <strong>"+remaining+"</strong> more minute"+s+" before you can queue another track, or until the last track that you enqueued has been played.<br>";
			} else {
				queueWait.innerHTML = "";
			}

			if (!response.result) {return;}

			while (searchBody.rows.length > 0) {
				searchBody.deleteRow(0);
			}
			while (searchPagination.firstChild) {
				searchPagination.removeChild(searchPagination.firstChild);
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

			searchPage = response.page;
			
			if (response.lastPage > 1) {
				for (var i = 0; i < response.lastPage; i++) {
					var btn = document.createElement('button');
					btn.textContent = i+1;
					if (i+1 === response.page) {
						btn.classList.add('current');
					}
					btn.addEventListener('click', searchChangePage.bind(btn, i+1), false);
					searchPagination.appendChild(btn);
				}
			}

			if (userAction) {
				setTimeout(function () {
					searchContainer.scrollIntoView();
				}, 0);
			}

		});
	}

	function doSearch() {
		clearTimeout(timerSearchInput);
		searchQuery = searchInput.value;
		if (searchQuery === "") {
			removeSearch();
			updateSearch();
		} else {
			updateSearch(true);
			//searchContainer.scrollIntoView();
			searchResult.classList.add('show');
		}
	}
	function removeSearch() {
		if (searchXhr) searchXhr.abort();
		clearTimeout(timerSearch);

		while (searchBody.rows.length > 0) {
			searchBody.deleteRow(0);
		}
		while (searchPagination.firstChild) {
			searchPagination.removeChild(searchPagination.firstChild);
		}
		
		searchResult.classList.remove('show');

	}

	function updateSuggestions() {
		if (suggestXhr) {
			suggestXhr.abort();
			suggestXhr = null;
		}
		clearTimeout(timerSuggest);
		if (!suggestContainer.classList.contains('show')) return;
		timerSuggest = setTimeout(updateSuggestions, 2*60*1000);

		suggestXhr = getJSON('ajax/suggest.php?page='+suggestPage, function (response) {
			suggestXhr = null;
			servertime = response.time*1000;

			var remaining = response.remaining/60;
			if (remaining > 0) {
				var unit = "minute";
				if (remaining > 60) {
					unit = "hour";
					remaining = Math.ceil(remaining/60);
				} else {
					remaining = Math.ceil(remaining);
				}
				
				var s = remaining === 1 ? "" : "s";
				suggestWait.innerHTML = "You must wait <strong>"+remaining+"</strong> more "+unit+s+" before you can suggest another track, or until the last track that you suggested has been accepted.";
				suggestForm.classList.remove('show');
			} else {
				suggestForm.classList.add('show');
				suggestWait.innerHTML = "";
			}

			while (suggestBody.rows.length > 0) {
				suggestBody.deleteRow(0);
			}
			while (suggestPagination.firstChild) {
				suggestPagination.removeChild(suggestPagination.firstChild);
			}

			if (response.list.length === 0) {
				suggestTable.classList.remove('show');
			} else {
				suggestTable.classList.add('show');
			}

			for (var i = 0; i < response.list.length; i++) {
				var suggestion = response.list[i];
				var row = suggestBody.insertRow(-1);
				if (suggestion.rejected) {
					row.classList.add('rejected');
				}
				if (suggestion.accepted) {
					row.classList.add('accepted');
				}
				var cells = [];
				while (cells.length < 6) {
					cells.push(row.insertCell(-1));
				}
				cells[0].innerHTML = prettyTimestampSearch(suggestion.time);
				cells[1].textContent = suggestion.username;
				cells[2].textContent = suggestion.suggestion;
				cells[3].textContent = suggestion.reason;

				if (isAdmin) {
					if (!suggestion.accepted) {
						var rejectButton = document.createElement('button');
						rejectButton.textContent = '×';
						rejectButton.className = 'rejectbtn';
						if (suggestion.rejected) {
							rejectButton.title = "Undo the reject";
						} else {
							rejectButton.title = "Reject this suggestion";
						}
						rejectButton.addEventListener('click', toggleReject.bind(rejectButton, suggestion.id, !suggestion.rejected), false);
						cells[4].appendChild(rejectButton);

						if (!suggestion.rejected) {
							var acceptButton = document.createElement('button');
							acceptButton.textContent = '✔';
							acceptButton.className = 'acceptbtn';
							acceptButton.title = "Accept this suggestion";
							acceptButton.addEventListener('click', suggestionAccepted.bind(acceptButton, suggestion.id), false);
							cells[5].appendChild(acceptButton);
						}
					}
					
				} else {
					if (suggestion.rejected) {
						cells[5].textContent = "Rejected";
					} else if (suggestion.accepted) {
						cells[5].textContent = "✔";
					}
				}
			}

			suggestPage = response.page;
			
			if (response.lastPage > 1) {
				for (var i = 0; i < response.lastPage; i++) {
					var btn = document.createElement('button');
					btn.textContent = i+1;
					if (i+1 === response.page) {
						btn.classList.add('current');
					}
					btn.addEventListener('click', suggestChangePage.bind(btn, i+1), false);
					suggestPagination.appendChild(btn);
				}
			}

		});
	}


	function updateMap() {
		if (mapXhr) {
			mapXhr.abort();
			mapXhr = null;
		}
		clearTimeout(timerMap);
		timerMap = setTimeout(updateMap, 30*1000);

		mapXhr = getJSON("ajax/map.php", function (coords) {
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
	updateSuggestions();
	
	searchInput.addEventListener('input', function () {
		clearTimeout(timerSearchInput);

		if (searchInput.value === '') {
			removeSearch();
		} else {
			timerSearchInput = setTimeout(function () {
				searchPage = 1;
				doSearch();
			}, 1000);
		}
	});

	searchForm.addEventListener('submit', function (e) {
		e.preventDefault();
		searchPage = 1;
		doSearch();
	}, false);

	toggleSearch.addEventListener('click', function () {
		if (searchContainer.classList.contains('show')) {
			searchContainer.classList.remove('show');
			removeSearch();
		} else {
			searchContainer.classList.add('show');
			searchInput.focus();
			doSearch();
		}
	});

	toggleSuggestions.addEventListener('click', function () {
		if (suggestContainer.classList.contains('show')) {
			suggestContainer.classList.remove('show');
		} else {
			suggestContainer.classList.add('show');
		}
		updateSuggestions();
	});

	suggestForm.addEventListener('submit', function (e) {
		e.preventDefault();
		if (suggestForm.classList.contains('show')) {
			postFormJSON('ajax/suggest.php', suggestForm, function (response) {
				if (response.tooFast) {
					alert("You tried to suggest a track too fast");
				} else {
					suggestPage = 1;
				}
				updateSuggestions();
			});
		} else {
			alert("You can't suggest when the form is disabled ;)");
		}
	}, false);

	window.updateMain = function (trackId) {
		if (trackId === undefined || currentTrackId !== trackId) {
			updatePlaylist();
			updateSearch();
		}
	}
}());
