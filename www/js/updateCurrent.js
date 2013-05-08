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
	//var scriptContainer = document.createElement('div');
	//var scriptNode;
	//var jsonpUrl = icecastpInfoUrl;
	//var mountPoint = icecastMount;

	//document.body.appendChild(scriptContainer);

	function update() {
		setTimeout(update, 20*1000);
		getJSON('ajax/radioInfo.php', function (info) {
			currentSong.innerHTML = info.title+(info.artist?' - '+info.artist:'');
			currentListeners.textContent = info.listeners;
			setTimeout(update, 20*1000);
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