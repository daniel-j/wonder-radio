
// shim layer with setTimeout fallback
window.requestAnimFrame = (function(){
  return  window.requestAnimationFrame       || 
          window.webkitRequestAnimationFrame || 
          window.mozRequestAnimationFrame    || 
          window.oRequestAnimationFrame      || 
          window.msRequestAnimationFrame     || 
          function( callback ){
            window.setTimeout(callback, 1000 / 60);
          };
})();

(function (global) {

	'use strict';

	function log10(num) {
		return Math.log(num) / Math.LN10;
	}

	var AudioContext = window.AudioContext || window.webkitAudioContext;

	if (!AudioContext) {
		alert("Your browser does not support the HTML5 Web Audio API (Google Chrome only at the moment)");
	}

	var acx = new AudioContext();

	var playstopbtn = document.getElementById('playstopbtn');
	var rangeVolume = document.getElementById('rangeVolume');

	var rangeSmoothing = global.document.getElementById('rangeSmoothing');
	var rangeWaveformHeight = global.document.getElementById('rangeWaveformHeight');
	var rangeWaveformWidth = global.document.getElementById('rangeWaveformWidth');

	var gainNode = acx.createGainNode();
	gainNode.connect(acx.destination);

	var analyzer = acx.createAnalyser();
	analyzer.connect(gainNode);
	rangeSmoothing.value = analyzer.smoothingTimeConstant = 0.6;
	
	var liveFreqData = new Float32Array(analyzer.frequencyBinCount);
	var liveWaveformData = new Uint8Array(analyzer.frequencyBinCount);
	
	var acanvas = global.document.querySelector('#analyzer');
	var ac = acanvas.getContext('2d');

	rangeWaveformHeight.value = 0.015;

	var isPlaying = false;
	var source;
	var audioTag;

	

	window.addEventListener('resize', resize, false);

	window.addEventListener('keydown', function (e) {
		if (e.keyCode === 32 && e.target === document.body) {
			togglePlay();
			e.preventDefault();
		}
	}, false);

	rangeVolume.addEventListener('change', function () {
		gainNode.gain.value = +rangeVolume.value;
	}, false);

	rangeSmoothing.addEventListener('change', function () {
		analyzer.smoothingTimeConstant = rangeSmoothing.value;
	}, false);

	function resize() {
		acanvas.width = window.innerWidth-40*2;
		acanvas.height = Math.floor((window.innerHeight-40*2)/2)*2;
	}
	setTimeout(resize, 500);

	playstopbtn.addEventListener('click', togglePlay, false);

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

		playstopbtn.textContent = "Stop";

		audioTag = new Audio();
		audioTag.addEventListener('error', handleStreamEnded, false);
		audioTag.addEventListener('ended', handleStreamEnded, false);
		audioTag.src = stationUrl;
		audioTag.play();
		
		
		setTimeout(function () {
			source = acx.createMediaElementSource(audioTag);
			source.connect(analyzer);
		}, 0);
	}

	function stopRadio() {
		isPlaying = false;
		if (source) {
			source.disconnect(0);
			source = null;
		}
		if (audioTag) {
			audioTag.removeEventListener('error', handleStreamEnded);
			audioTag.removeEventListener('ended', handleStreamEnded);
			audioTag.pause();

			audioTag.src = '';
			audioTag.load();
			audioTag = null;
		}
		playstopbtn.textContent = "Play";
	}


	function handleStreamEnded(e) {
		setTimeout(function () {
			if (isPlaying) {
				startRadio();
			}
		}, 1000);
	}

	playstopbtn.disabled = false;
	if (autoplay) {
		startRadio();
	} else {
		stopRadio();
	}

	var startTime = performance.now();
	var lastTime = startTime;

	function draw() {
		

		var now = performance.now();
		var delta = now-lastTime;
		lastTime = now;

		ac.clearRect(0, 0, acanvas.width, acanvas.height);
		
		if (!audioTag || !source) {
			requestAnimFrame(draw, acanvas);
			return;
		}
		
		
		analyzer.getFloatFrequencyData(liveFreqData);
		analyzer.getByteTimeDomainData(liveWaveformData);
		var freqData = liveFreqData;
		var waveformData = liveWaveformData;

		var movePixels = Math.max(delta*(acanvas.height/2/2000), 1)|0;
		var waveheight = acanvas.height*rangeWaveformHeight.value;

		//ac.drawImage(acanvas, 0, acanvas.height/2, acanvas.width, acanvas.height/2-movePixels, 0, acanvas.height/2+movePixels, acanvas.width, acanvas.height/2-movePixels);
		
		var weirdWidth = (acanvas.width)/2.9;

		ac.beginPath();
		ac.moveTo(0, acanvas.height);
		ac.lineTo(0, acanvas.height-waveheight/2);
		
		for (var i = 0; i < freqData.length; i++) {
			var freq = i*acx.sampleRate/analyzer.fftSize;

			var x = log10(i)*weirdWidth|0;
			var dw = Math.ceil(log10(i+1)*weirdWidth-log10(i)*weirdWidth);

			var magnitude = Math.max((freqData[i]-analyzer.minDecibels)/95, 0);

			ac.fillStyle = 'hsl('+Math.min(Math.floor((i/(freqData.length*0.7))*360), 359)+', 100%, '+Math.floor(magnitude*100-10)+'%)';
			ac.fillRect(x, acanvas.height, dw, -magnitude*(acanvas.height)|0);

			var x = i*(acanvas.width/waveformData.length);
			var wave = (waveformData[i]/128);


			if (i % Math.pow(2, 9-rangeWaveformWidth.value) === 0) {
				
				ac.lineTo(x, (acanvas.height-wave*waveheight));
			}
		}
		ac.lineTo(acanvas.width, acanvas.height-wave*waveheight);
		ac.lineTo(acanvas.width, acanvas.height);
		ac.fillStyle = 'black';
		ac.closePath();
		ac.fill();
		
		
		/*win.capturePage(function (imgurl) {
			console.log(imgurl.length);
			requestAnimFrame(draw, acanvas);
		}, 'jpeg');*/

		
		requestAnimFrame(draw, acanvas);
		
	}
	
	draw();

	/*if (navigator.webkitGetUserMedia) {
		navigator.webkitGetUserMedia({audio:true}, function (stream) {
			console.log(stream);
			var source = acx.createMediaStreamSource(stream);
			console.log(source);
			source.connect(analyzer);
			source.connect(spectrumAnalyzer);
		});
	}*/

}(window));
