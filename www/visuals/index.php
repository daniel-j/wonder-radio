<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Radio Stream Visualizer</title>
  <style>
    
    html, body {
      padding: 0px;
      margin: 0px;
    }

    body {
      color: white;
      background: black;
      font-family:  monospace;
    }

    a {
      color: #F99;
    }

    h1 {
      margin: 0px;
    }

    #overlaywrapper {
      position: absolute;
      left: 0;
      top: 0;
      height: 200px;
      width: 640px;
    }
    #overlaywrapper:hover #overlay {
      top: 0px;
      opacity: 1;
    }

    #overlay {
      position: absolute;
      background-color: black;
      border: 10px solid #933;
      padding: 15px;
      margin: 15px;
      display: block;
      top: -100%;
      opacity: 0;
      -webkit-transition: top .4s ease-in-out, opacity .4s ease-in-out;
    }

    #analyzer {
      display: block;
      background-color: black;
      /*box-shadow: 0px 0px 15px black;*/
      /*z-index: 1;
      position: relative;*/
      padding: 40px;
      padding-bottom: 0px;
    }
    
    #spectrum {
      background-color: black;
      display: block;
      /*box-shadow: 0px 0px 15px black;*/
      padding: 40px;
      padding-top: 0px;
    }
    
    #controller audio {
      margin-top: 15px;
      max-height: 310px;
      display: block;
      /*box-shadow: 0px 0px 15px black;*/
      width: 550px;
    }
  </style>
</head>
<body>

<div id="overlaywrapper">
  <div id="overlay">
    <h1>Radio stream visualizer</h1>

    <button type="button" id="playstopbtn" disabled></button><br>
    Volume: <input type="range" min="0" max="1" value="1" step="0.05" id="rangeVolume">

    <h3>View settings</h3>

    Smoothing time constant: <input type="range" min="0.025" max="0.975" step="0.025" value="0" id="rangeSmoothing"><br>
    Waveform height: <input type="range" min="0" max="0.25" step="0.001" value="0" id="rangeWaveformHeight"><br>
    Waveform segments: <input type="range" min="0" max="10" step="1" value="10" id="rangeWaveformWidth">

    <br>
    <br>
    Technologies used: 
    <a href="http://www.html5rocks.com/en/tutorials/webaudio/intro/" target="_blank">Web Audio API</a> -
    <a href="https://developer.mozilla.org/en/Using_audio_and_video_in_Firefox" target="_blank">Audio/video API</a> -
    <a href="https://developer.mozilla.org/en/Drawing_Graphics_with_Canvas" target="_blank">2D Canvas API</a>
  </div>
</div>

<canvas id="analyzer"></canvas>

<script>
var stationUrl = <?php echo json_encode(isset($_GET['url'])?$_GET['url']:"http://djazz.mine.nu:1338/stream");?>;
var autoplay = <?php echo isset($_GET['stopped'])?"false":"true";?>;
</script>
<script src="audio.js"></script>
</body>
</html>
