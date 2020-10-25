<?php
include '../../mainfile.php';

require_once __DIR__ . '/classes/Log.php';

$conf = ['mode' => 0600, 'timeFormat' => '%X %x'];
//$logger = &Log::singleton('file', 'out.log', 'ident', $conf);

$Artist = getFromRequest('Artist');
$Title = getFromRequest('Title');
$Album = getFromRequest('Album');
$Genre = getFromRequest('Genre');
$Year = getFromRequest('Year');

if ('' != $Artist || '' != $Title) {
    // Checking the shame list

    $shameString = $xoopsModuleConfig['shameList'];

    $shameList = preg_split("\r\n", $shameString);

    (!in_array($Artist, $shameList, true)) || die();

    if ('' == trim($Album)) {
        $Album = 'N/A';
    }

    if ('' == trim($Year)) {
        $Year = 'null';
    }

    $db = XoopsDatabaseFactory::getDatabaseConnection();

    $tablename = $xoopsDB->prefix('play_history');

    // Avoid duplicated consecutive entries

    $result = $db->query("select max(id) as maxid from $tablename");

    $row = $xoopsDB->fetchArray($result);

    $id = $row['maxid'];

    // Must be 4 digits log and numeric

    if (is_nan($Year + 0) || (4 != mb_strlen($Year))) {
        $Year = 'null';
    }

    $result = $db->query("SELECT 1 FROM $tablename WHERE artist = '$Artist' and title = '$Title' and album = '$Album' and id = $id");

    if (!($row = $xoopsDB->fetchArray($result)) || $xoopsModuleConfig['consecutiveDuplicates']) {
        // Check the minimum timeframe between two songs

        $minimumTimeSpacing = $xoopsModuleConfig['minimumTimeSpacing'];

        if ($minimumTimeSpacing > 0) {
            $sql = "DELETE FROM $tablename WHERE timeplayed > DATE_SUB(now(),INTERVAL $minimumTimeSpacing SECOND)";

            if (!$db->queryF($sql)) {
                //$logger->log("Error deleting previous song: " . $db->error());
            }
        }

        // Genre

        $sql = "INSERT INTO $tablename (artist, title, album, year, genre) values('$Artist', '$Title' ,'$Album', $Year, '$Genre') ";

        if (!$db->queryF($sql)) {
            //$logger->log("Error inserting song: " . $db->error());
        }
    }  

    //$logger->log("Decided not to insert.");
}  
    //$logger->log("Decided not to insert. Minimum requirements not met: Artist=($Artist), Title=($Title)");

/**
 * Gets a parameter from $_REQUEST without displaying any warnings in
 * case it does not exists.
 * Also adds support to other less configurable clients.
 * @param mixed $parameter
 * @return string|null
 * @return string|null
 */
function getFromRequest($parameter)
{
    $result = null;

    if (array_key_exists($parameter, $_REQUEST)) {
        $result = trim($_REQUEST[$parameter]);
    } else {
        $nowPlayingParameter = $parameter . '1'; // This adds support for the Now Playing plugin

        if (array_key_exists($nowPlayingParameter, $_REQUEST)) {
            $result = trim($_REQUEST[$nowPlayingParameter]);
        }
    }

    if (!get_magic_quotes_gpc()) {
        $result = addslashes($result);
    }

    return $result;
}
