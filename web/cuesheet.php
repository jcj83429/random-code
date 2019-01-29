<?php
function parseCue($cuefp){
    // per-cuesheet variables
    $tracksInfo = array();
    $indexesInfo = array();
    $albumPerformer = '';
    $tracksStarted = false;

    $currentFile = null;

    // track variables
    $firstTrack = true;
    $currentTrackInfo = array();
    $lastTrackInfo = null;
    $trackForCurrentWav = 0;
    $currentTrack = 1;

    // index variables
    $firstIndex = true;
    $indexForCurrentWav = 0;
    $currentIndexInfo = array();
    $lastIndexInfo = null;

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
            $currentFile = $matches[1];
            $trackForCurrentWav = 0;
            $indexForCurrentWav = 0;
        }else if(preg_match('/INDEX (\d\d) (\d\d):(\d\d):(\d\d)/i', $line, $matches)){
            $index = intval($matches[1]);
            $indexTime = intval($matches[2])*60 + intval($matches[3]) + intval($matches[4])/75;

            // handle track start
            if($index == 1){
                $currentTrackInfo['start'] = $indexTime;
                $currentTrackInfo['FILE'] = $currentFile;
                if($trackForCurrentWav > 1){
                    $lastTrackInfo['duration'] = $indexTime - $lastTrackInfo['start'];
                }
                if(!$firstTrack){
                    $tracksInfo[] = $lastTrackInfo;
                    //var_dump($lastTrackInfo);echo '<br>';
                }
                $firstTrack = false;
            }

            // handle new index
            $lastIndexInfo = $currentIndexInfo;
            $currentIndexInfo = array();
            $indexForCurrentWav += 1;
            if($indexForCurrentWav > 1){
                $lastIndexInfo['duration'] = $indexTime - $lastIndexInfo['start'];
            }
            if(!$firstIndex){
                $indexesInfo[] = $lastIndexInfo;
                //var_dump($lastIndexInfo);echo '<br>';
            }
            $currentIndexInfo['TRACK'] = $currentTrack;
            $currentIndexInfo['INDEX'] = $index;
            $currentIndexInfo['FILE'] = $currentFile;
            $currentIndexInfo['start'] = $indexTime;
            $firstIndex = false;
        }else if(preg_match('/TRACK (\d\d) AUDIO/i', $line, $matches)){
            if(!firstTrack && !array_key_exists('PERFORMER', $currentTrackInfo)){
                $currentTrackInfo['PERFORMER'] = $albumPerformer;
            }
            $currentTrack = intval($matches[1]);
            $lastTrackInfo = $currentTrackInfo;
            $currentTrackInfo = array();
            $trackForCurrentWav += 1;
            $currentTrackInfo['TRACK'] = $currentTrack;
            $tracksStarted = true;
        }else{
            //echo $line.' UNKNOWN<br>';
        }
    }
    if(!array_key_exists('PERFORMER', $currentTrackInfo)){
        $currentTrackInfo['PERFORMER'] = $albumPerformer;
    }
    $tracksInfo[] = $currentTrackInfo;
    $indexesInfo[] = $currentIndexInfo;
    return array($tracksInfo, $indexesInfo);
}

setlocale(LC_ALL, 'C.UTF-8');

$filename=preg_replace('#http.*?usic/#', '', $_GET["file"]);

if($filename != '' && false == strpos($filename, '../') && strcasecmp(substr($filename, strlen($filename) - strlen('.cue')),'.cue') == 0){
    $filepath="/home/archuser/Music/" . $filename;
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
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
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
    $filehandle=fopen($filepath, 'r') or die('file open failed');
    list($tracksInfo, $indexesInfo) = parseCue($filehandle);
    echo 'TRACKS<br>';
    echo '<table border=1>';
    echo '<tr><th>#</th><th>PERFORMER</th><th>TITLE</th><th>duration</th><th>link</th></tr>';
    foreach($tracksInfo as $track){
        echo '<tr>';
        echo '<td>'.$track['TRACK'].'</td>';
        echo '<td>'.$track['PERFORMER'].'</td>';
        echo '<td>'.$track['TITLE'].'</td>';

        echo '<td>';
        if(array_key_exists('duration', $track)){
            echo sprintf('%02d:%02d', $track['duration']/60, $track['duration']%60);
        }else{
            echo '?';
        }
        echo '</td>';

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
    echo '</table><br>';

    echo 'INDEXES<br>';
    echo '<table border=1>';
    echo '<tr><th>TRACK</th><th>INDEX</th><th>duration</th><th>link</th></tr>';
    foreach($indexesInfo as $index){
        echo '<tr>';
        echo '<td>'.$index['TRACK'].'</td>';
        echo '<td>'.$index['INDEX'].'</td>';

        echo '<td>';
        if(array_key_exists('duration', $index)){
            echo sprintf('%02d:%02d', $index['duration']/60, $index['duration']%60);
        }else{
            echo '?';
        }
        echo '</td>';

        echo '<td><a href="'.'/compress.php?file='.rawurlencode(dirname($_GET["file"]).'/'.$index['FILE']).'&direct=1&start='.$index['start'];
        if(array_key_exists('duration', $index)){
            echo '&duration='.$index['duration'];
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
