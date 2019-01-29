<?php
setlocale(LC_ALL, 'C.UTF-8');

if (!file_exists('/tmp/php-music-compress-out')) {
    mkdir('/tmp/php-music-compress-out', 0777, true);
}

if (!file_exists('/tmp/php-music-compress-out/.htaccess')) {
    file_put_contents('/tmp/php-music-compress-out/.htaccess', "Allow From All\nSatisfy Any");
}

if(isset($_GET["quality"]) && (strlen($_GET["quality"]) != 1 || !ctype_digit($_GET["quality"]))){
    echo "invalid";
    exit;
}

if(isset($_GET["start"]) && (false == is_numeric($_GET["start"]) || floatval($_GET["start"]) < 0)){
    echo "invalid";
    exit;
}

if(isset($_GET["duration"]) && (false == is_numeric($_GET["duration"]) || floatval($_GET["duration"]) < 0)){
    echo "invalid";
    exit;
}

$filename=preg_replace('#http.*?usic/#', '', $_GET["file"]);
$outname=substr(md5(dirname($filename)), 0, 8) . '_' . basename($filename);

$start_params='';
if(isset($_GET["start"])){
    $outname=$outname . '.ss' . intval($_GET["start"]);
    $start_params='-ss ' . $_GET["start"];
}

$duration_params='';
if(isset($_GET["duration"])){
    $outname=$outname . '.t' . intval($_GET["duration"]);
    $duration_params='-t ' . $_GET["duration"];
}

if(isset($_GET["format"]) && isset($_GET["quality"])){
    switch($_GET["format"]){
        case "mp3":
            $outname=$outname . '.v' . $_GET["quality"] . '.mp3';
            $quality_params='-aq ' . $_GET["quality"];
            break;
        case "ogg":
            $outname=$outname . '.v' . $_GET["quality"] . '.ogg';
            $quality_params='-aq ' . $_GET["quality"] . ' -vn';
            break;
        case "opus":
            $outname=$outname . '.v' . $_GET["quality"] . '.opus';
            $quality=intval($_GET["quality"]);
            // hack: convert quality level to vbr target bitrate
            if($quality == 0){
                $quality=1;
            }
            $quality_params='-b:a ' . strval($quality * 32) . 'k';
            break;
        default:
            echo "invalid";
            exit;
    }
}else{
    $outname=$outname . '.v2.mp3';
    $quality_params='-aq 2';
}


if($filename != '' && false == strpos($filename, '../')){
    $filepath=escapeshellarg("/home/archuser/Music/" . $filename);

    $ffmpeg_out = shell_exec('ffmpeg ' . $start_params . ' -i ' . $filepath . ' ' . $duration_params . ' ' . $quality_params . ' ' . escapeshellarg('/tmp/php-music-compress-out/' . $outname) . ' 2>&1');
}
if(isset($_GET["direct"])){
    header('Location: ' . 'compressed/' . $outname);
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
  Format: <select name="format">
    <option value="mp3" selected>mp3</option>
    <option value="ogg">ogg vorbis</option>
    <option value="opus">opus</option>
  </select><br>
  Quality: <select name="quality">
    <option value="0">V0</option>
    <option value="2" selected>V2</option>
    <option value="4">V4</option>
    <option value="6">V6</option>
    <option value="8">V8</option>
  </select><br>
  <input type="submit" value="Submit">
</form>

<?php
if($filename == ''){
    echo "no file specified<br>Usage: compress.php?file=path/to/file.flac";
}else if(strpos($filename, '../') !== false){
    echo "invalid";
}else{
    echo $filename . "<br>";
    echo $filepath . "<br>";
    echo '<a href='. escapeshellarg('compressed/' . $outname) . '>' . $outname . '</a><br>';
    echo $ffmpeg_out;
}
?>


</body>
</html>
