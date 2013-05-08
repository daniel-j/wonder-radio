#!/usr/bin/env node
'use strict';
var http = require('http');
var net = require('net');
var child_process = require('child_process');
var mysql = require('mysql');
var hiddenInfo = require('hidden.json');

var mysqlUser = hiddenInfo.mysqlUsername;
var mysqlPassword = hiddenInfo.mysqlPassword;
var mysqlDatabase = hiddenInfo.mysqlDatabase;
var mpdPassword = mysqlPassword;
var icecastPassword = mysqlPassword;
var ipinfoApiKey = "15ac89298a362c69b7ce1c2cae0f0631f79c514e078fa8b93cef464c7e9a5ab7";

var trackWait = 5*60; // In minutes
var minimumRating = -10;

var db = mysql.createConnection({
	user     : mysqlUser,
	password : mysqlPassword,
	port: '/var/run/mysqld/mysqld.sock'
});
db.connect();
db.query('USE `'+mysqlDatabase+'`');
console.log("Connected to database");

var icecastOptions = {
	port: 8000,
	auth: 'admin:'+icecastPassword,
	path: "/admin/listclients.xsl?mount=/stream"
};

var ipcache = {};

var addresses = [];
var clientinfo = [];

function createGeoList() {
	var list = [];



	for (var i = 0; i < clientinfo.length; i++) {
		if (clientinfo[i].ipinfo) {
			list.push([clientinfo[i].ipinfo.x, clientinfo[i].ipinfo.y]);
		}
	}



	return list;
}

function updateIpInfo(ip) {
	if (ipcache[ip]) {
		var pos = addresses.indexOf(ip);
		if (pos !== -1) {
			clientinfo[pos].ipinfo = ipcache[ip];
		}
		return;
	}
	http.get("http://api.ipinfodb.com/v3/ip-city/?key="+ipinfoApiKey+"&ip="+ip+"&format=json&timestamp="+Date.now(), function (res) {
		var buffer = "";
		res.on('data', function (chunk) {
			buffer += chunk;
		});
		res.on('end', function () {
			var pos = addresses.indexOf(ip);
			var fullinfo = JSON.parse(buffer);
			console.log(fullinfo);
			if (fullinfo.latitude == 0 && fullinfo.longitude == 0) {
				console.log("Unknown location for ip "+ip);
				ipcache[ip] = null;
				if (pos !== -1) {
					clientinfo[pos].ipinfo = null;
				}
				return;
			}
			var ipinfo = {y: Math.round( 244/2-(fullinfo.latitude)*1.22 + 35), x: Math.round(454/2 + fullinfo.longitude*1.06 - 15)};
			ipcache[ip] = ipinfo;
			
			if (pos !== -1) {
				clientinfo[pos].ipinfo = ipinfo;
			}
		});
	}).on('error', function () {
		console.log("Error getting ip info");
		var pos = addresses.indexOf(ip);
		if (pos !== -1) {
			addresses.splice(pos, 1);
			clientinfo.splice(pos, 1);
		}
	});
}

function updateListeners() {
	var req = http.request(icecastOptions, function (res) {
		if (res.statusCode !== 200) return;
		res.setEncoding('utf8');
		var buffer = "";
		res.on('data', function (chunk) {
			buffer += chunk;
		});
		res.on('end', function () {
			var mounts = JSON.parse(buffer);
			delete mounts[""];
			for (var i in mounts) {
				mounts[i].length--;
			}
			if (mounts["/stream"]) {
				var clients = mounts["/stream"];
				var newaddresses = [];
				for (var i = 0; i < clients.length; i++) {
					var ip = clients[i].ip;
					newaddresses.push(ip);
					var pos = addresses.indexOf(ip);
					if (pos === -1) {
						console.log(ip);
						addresses.push(ip);
						clientinfo.push({});
						updateIpInfo(ip);
						pos = addresses.length-1;
					}
					clientinfo[pos].time = clients[i].time;
				}
				for (var i = 0; i < addresses.length; i++) {
					if (newaddresses.indexOf(addresses[i]) === -1) {
						addresses.splice(i, 1);
						clientinfo.splice(i, 1);
						i--;
					}
				}
			}
		});
	});
	req.on('error', function(e) {
		console.log('problem with request: ' + e.message);
	});
	req.end();
}



var randomTimes = Math.floor(Math.random()*1000); // 100 different combinations...
for (var i = 0; i < randomTimes; i++) {
	db.query('SELECT RAND()');
}
console.log("Initialized");

var lastPlayedFile = "";
var oldQueueId = 0;
var currentTrackId = 0;

function mpdUpdate(cb) {
	var child = child_process.spawn("./mpd-update.sh", [mpdPassword]);
	queueNextSong();
	child.stdout.on('data', function (data) {
		cb(data.toString().trim().split("@@"));
	});
}

function queueTrack(file, cb) {
	child_process.execFile('./mpd-queue.sh', [mpdPassword, file], function (err, stdout, stderr) {
		console.log("Queued "+file);
		if (cb) cb();
	});
}

function queueRandom(cb) {

	// Only tracks played more than trackWait minutes ago and got a rating above minimumRating
	db.query('SELECT file, IFNULL((SELECT SUM(vote) FROM votes WHERE tracks.id=votes.trackId), 0) as rating, lastplayed FROM tracks WHERE (SELECT id FROM queue WHERE tracks.id=queue.trackId) IS NULL AND (lastplayed < date_sub(now(), interval '+trackWait+' minute) OR lastplayed IS NULL) HAVING rating > '+minimumRating+' ORDER BY rating*RAND()*5-plays*RAND()+RAND()*10 DESC LIMIT 1', function(err, rows, fields) {
		if (err) console.log(err);
		console.log(rows[0]);
		if (rows && rows[0]) {
			var entry = rows[0];

			console.log("Next song hasn't been played since "+(entry.lastplayed||'NEVER'), entry.file);
			queueTrack(entry.file, function () {
				if (cb) cb(entry);
			});
		} else {
			// Only play those with rating above minimumRating, no matter when they were last played
			db.query('SELECT file, IFNULL((SELECT SUM(vote) FROM votes WHERE tracks.id=votes.trackId), 0) as rating, lastplayed FROM tracks WHERE (SELECT id FROM queue WHERE tracks.id=queue.trackId) IS NULL HAVING rating > '+minimumRating+' ORDER BY rating*RAND()*5-plays*RAND()+RAND()*10 DESC LIMIT 1', function(err, rows, fields) {
				if (err) console.log(err);
				console.log(rows[0]);
				if (rows && rows[0]) {
					var entry = rows[0];
					console.log("Next song has the rating "+entry.rating+" and was played pretty recently: "+entry.lastplayed, entry.file);
					queueTrack(entry.file, function () {
						if (cb) cb(entry);
					});
				} else {
					// I give up, play a random song
					db.query('SELECT file, IFNULL((SELECT SUM(vote) FROM votes WHERE tracks.id=votes.trackId), 0) as rating, lastplayed FROM tracks ORDER BY rating*RAND()*5-plays*RAND()+RAND()*10 DESC LIMIT 1', function(err, rows, fields) {
						if (err) console.log(err);
						console.log(rows[0]);
						if (rows && rows.length > 0) {
							var entry = rows[0];
							console.log("I give up. Next song is random!", entry.file);
							queueTrack(entry.file, function () {
								if (cb) cb(entry);
							});
						} else {
							console.log("Unable to queue music.. Database empty?");
						}
					});
				}
			});
		}
	});
}

function queueNextSong() {
	db.query('SELECT queue.id, trackId, file, RAND() as randomNr FROM queue LEFT JOIN tracks ON tracks.id=queue.trackId WHERE (NOW()-queue.added)/60 > 30 ORDER BY queue.added DESC LIMIT 1', function (err, rows, fields) {
		if (err) console.log(err);
		console.log("\nqueueNextSong()", rows);
		var random = Math.random();
		if ((!rows || rows.length === 0) && random > 0.40) {
			console.log("Random track...", random);
			//console.log(rows);
			queueRandom();
		} else if (rows.length > 0) {
			console.log("Will play an old queued track");
			playFromQueue(rows[0]);
		} else {
			db.query('SELECT queue.id, trackId, file FROM queue LEFT JOIN tracks ON tracks.id=queue.trackId ORDER BY queue.added ASC LIMIT 1', function (err, rows, fields) {
				if(err) console.error(err);
				if (!rows || rows.length === 0) {
					console.log("Queue is empty...");
					queueRandom();
				} else {
					console.log("Will play a queued track", rows[0].file);
					playFromQueue(rows[0]);
				}
			});
		}
	});
}

function playFromQueue(item) {
	oldQueueId = item.id;
	queueTrack(item.file, function () {
		
	});
}

mpdUpdate(function (track) {
	var file = track[0];
	var title = track[1];
	var artist = track[2];
	lastPlayedFile = file;
	console.log(">> Now playing "+(title || file));
	process.title = (title || file).replace(/\ /g, '_');

	db.query("DELETE FROM queue WHERE trackId IN (SELECT id FROM tracks WHERE file = ?)", [file]);
	db.query("UPDATE tracks SET plays = plays+1, lastplayed = NOW() WHERE file = "+mysql.escape(file), function () {
		
		queueNextSong();
	});
});

setInterval(updateListeners, 2*1000);

var geoserver = net.createServer(function (c) {
	c.on('error', function () {

	});
	var list = createGeoList();
	c.end(JSON.stringify(list));
});
geoserver.listen(8001);
