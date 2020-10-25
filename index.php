<?php
include '../../mainfile.php';
// this page uses smarty template
// this must be set before including main header.php
$xoopsOption['template_main'] = 'xplayhist_index.html';
include '../../header.php';

$listTable = $xoopsDB->prefix('play_history');

// The more recently played list
$result = $xoopsDB->query("SELECT artist, title, album, date_format(timeplayed, '%d/%m/%Y at %H:%i') as timePlayed FROM $listTable ORDER BY id DESC LIMIT " . $xoopsModuleConfig['numberOfSongs']);
$aryRecentList = [];

while (false !== ($row = $xoopsDB->fetchArray($result))) {
    $aryRecentList[] = $row;
}

$xoopsTpl->assign('playhist', $aryRecentList);

// Localized constants
$xoopsTpl->assign('mlRecentSongs', _ML_RECENT_SONGS);
$xoopsTpl->assign('mlArtist', _ML_ARTIST);
$xoopsTpl->assign('mlTitle', _ML_TITLE);
$xoopsTpl->assign('mlAlbum', _ML_ALBUM);
$xoopsTpl->assign('mlTimePlayed', _ML_TIME_PLAYED);

require_once XOOPS_ROOT_PATH . '/footer.php';
