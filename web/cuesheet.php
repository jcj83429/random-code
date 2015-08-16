<?php
function parseCue($cuefp){
    $cueInfo = array();
    $currentTrackInfo = array();
    $lastTrackInfo = null;
    $albumPerformer = '';
    $lastFile = null;
    $tracksStarted = false;
    $trackForCurrentWav = 0;
    $firstTrack = true;
    $indexTime = 0.0;
    while(($line = fgets($cuefp, 4096)) !== false){
        $line = trim($line);
        $matches = array();
        if(preg_match('/PERFORMER.*"([^"]*)"/i', $line, $matches)){
            if($tracksStarted){
                $currentTrackInfo['PERFORMER'] = $matches[1];
            }else{
                $albumPerformer = $matches[1];
            }
        }else if(preg_match('/TITLE.*"([^"]*)"/i', $line, $matches)){
            if($tracksStarted){
                $currentTrackInfo['TITLE'] = $matches[1];
            } // ignore album title
        }else if(preg_match('/FILE.*"([^"]*)"/i', $line, $matches)){
            $lastFile = $matches[1];
            $trackForCurrentWav = 0;
        }else if(preg_match('/INDEX 01.*(\d\d):(\d\d):(\d\d)/i', $line, $matches)){
            $indexTime = intval($matches[1])*60 + intval($matches[2]) + intval($matches[3])/75;
            $currentTrackInfo['start'] = $indexTime;
            $currentTrackInfo['FILE'] = $lastFile;
            if($trackForCurrentWav > 1){
                $lastTrackInfo['duration'] = $indexTime - $lastTrackInfo['start'];
            }
            if(!$firstTrack){
                $cueInfo[] = $lastTrackInfo;
                //var_dump($lastTrackInfo);echo '<br>';
            }
            $firstTrack = false;
        }else if(preg_match('/TRACK (\d\d) AUDIO/i', $line, $matches)){
            if(!firstTrack && !array_key_exists('PERFORMER', $currentTrackInfo)){
                $currentTrackInfo['PERFORMER'] = $albumPerformer;
            }
            $lastTrackInfo = $currentTrackInfo;
            $currentTrackInfo = array();
            $trackForCurrentWav += 1;
            $currentTrackInfo['TRACK'] = $matches[1];
            $tracksStarted = true;
        }else{
            //echo $line.' UNKNOWN<br>';
        }
    }
    if(!array_key_exists('PERFORMER', $currentTrackInfo)){
        $currentTrackInfo['PERFORMER'] = $albumPerformer;
    }
    $cueInfo[] = $currentTrackInfo;
    return $cueInfo;
}

setlocale(LC_ALL, 'C.UTF-8');

$filename=preg_replace('#http.*usic/#', '', $_GET["file"]);

if($filename != '' && false == strpos($filename, '../') && strcasecmp(substr($filename, strlen($filename) - strlen('.cue')),'.cue') == 0){
    $filepath="/home/livingroom/Music/" . $filename;
    $invalid=false;
}else{
    $invalid=true;
}

$passFormatQuality = true;
if(!isset($_GET["format"]) || ($_GET["format"] != 'mp3' && $_GET["format"] != 'mp3-fast' && $_GET["format"] != 'ogg' && $_GET["format"] != 'opus' && $_GET["format"] != 'opus-fast')){
    $passFormatQuality = false;
}
if(!isset($_GET["quality"]) || (strlen($_GET["quality"]) != 1 || !ctype_digit($_GET["quality"]))){
    $passFormatQuality = false;
}

?>

<html>
<head>
<title>cuesheet</title>
</head>
<body>

<?php
if($filename == ''){
    echo "no file specified<br>Usage: cuesheet.php?file=path/to/file.cue";
}else if($invalid){
    echo "invalid";
}else{
    echo $filename . "<br>";
    echo '<table border=1>';
    echo '<tr><th>#</th><th>PERFORMER</th><th>TITLE</th><th>duration</th><th>link</th></tr>';
    $filehandle=fopen($filepath, 'r') or die('file open failed');
    $cueInfo = parseCue($filehandle);
    foreach($cueInfo as $track){
        echo '<tr>';
        echo '<td>'.$track['TRACK'].'</td>';
        echo '<td>'.$track['PERFORMER'].'</td>';
        echo '<td>'.$track['TITLE'].'</td>';
        echo '<td>'. sprintf('%02d', $track['duration']/60) .':'. sprintf('%02d', $track['duration']%60) .'</td>';

        echo '<td><a href="'.'/compress.php?file='.rawurlencode(dirname($_GET["file"]).'/'.$track['FILE']).'&direct=1&start='.$track['start'];
        if(array_key_exists('duration', $track)){
            echo '&duration='.$track['duration'];
        }
        if($passFormatQuality){
            echo '&format=' . $_GET["format"] . '&quality=' . $_GET["quality"];
        }
        echo '">get segment</a></td>';
        echo '</tr>';
    }
    echo '</table>';
    if(!$passFormatQuality){
        echo 'format/quality not specified or invalid, using default format/quality<br>';
    }
}
?>


</body>
</html>
