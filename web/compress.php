<!-- audio converter -->
<!-- requires ffmpeg -->

<?php
setlocale(LC_ALL, 'C.UTF-8');

if (!file_exists('/tmp/php-music-compress-out')) {
    mkdir('/tmp/php-music-compress-out', 0777, true);
}

$filename=preg_replace('#http.*usic/#', '', $_GET["file"]); //depending on symlinks, the folder name may be "music" or "Music"

if($filename != '' && false == strpos($filename, '..')){
    $filepath=escapeshellarg("/home/livingroom/Music/" . $filename);

    $ffmpeg_out = shell_exec('ffmpeg -i ' . $filepath . ' -aq ' . escapeshellarg($_GET["quality"]) . ' ' . escapeshellarg('/tmp/php-music-compress-out/' . basename($filename) . $_GET["quality"] . '.mp3') . ' 2>&1');
}
if(isset($_GET["direct"])){
    header('Location: ' . 'compressed/' . basename($filename) . $_GET["quality"] . '.mp3');
    exit;
}
?>

<html>
<head>
<title>compress</title>
</head>
<body>
<form action="compress.php" method="get">
  Input File: <input type="text" name="file"><br>
  Quality: <select name="quality">
    <option value="0">V0</option>
    <option value="2" selected>V2</option>
    <option value="4">V4</option>
    <option value="6">V6</option>
  </select><br>
  <input type="submit" value="Submit">
</form>

<?php
if($filename == ''){
    echo "no file specified<br>Usage: compress.php?file=path/to/file.flac&quality=2";
}else if(strpos($filename, '..') !== false){
    echo "invalid";
}else{
    echo $filename . "<br>";
    echo $filepath . "<br>";
    echo '<a href='. escapeshellarg('compressed/' . basename($filename) . $_GET["quality"] . '.mp3') . '>' . basename($filename) . $_GET["quality"] . '.mp3</a><br>';
    echo $ffmpeg_out;
}
?>


</body>
</html>
