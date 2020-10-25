<?php
include '../../mainfile.php';
// this page uses smarty template
// this must be set before including main header.php
$xoopsOption['template_main'] = 'xplayhist_stats.html';
include '../../header.php';

$listTable = $xoopsDB->prefix('play_history');

if (array_key_exists('page', $_REQUEST)) {
    $page = $_REQUEST['page'];
} else {
    $page = 'menuFavorites';
}

if (array_key_exists('scope', $_REQUEST)) {
    $scope = $_REQUEST['scope'];
} else {
    $scope = 'all';
}

$scopeSql = '';
if ('all' != $scope) {
    if ('day' == $scope) {
        $scopeSql = ' and timeplayed >= date_sub(curdate(), INTERVAL 1 DAY) ';
    } elseif ('week' == $scope) {
        $scopeSql = ' and timeplayed >= date_sub(curdate(), INTERVAL 7 DAY) ';
    } elseif ('month' == $scope) {
        $scopeSql = ' and timeplayed >= date_sub(curdate(), INTERVAL 1 MONTH) ';
    } elseif ('year' == $scope) {
        $scopeSql = ' and timeplayed >= date_sub(curdate(), INTERVAL 12 MONTH) ';
    }
}

if ('menuFavorites' == $page) {
    // ---------------------------------------------------

    // -- FAVORITES

    // ---------------------------------------------------

    // TOP 10 artists

    $result = $xoopsDB->query('SELECT artist, count(*) as cont ' .
            "FROM $listTable " .
            "WHERE 1 $scopeSql " .
            'GROUP BY artist order by cont ' .
            'DESC, artist ' .
            'LIMIT 10;');

    $aryTopArtists = [];

    while (false !== ($row = $xoopsDB->fetchArray($result))) {
        $aryTopArtists[] = $row;
    }

    $xoopsTpl->assign('topArtists', $aryTopArtists);

    // TOP 10 songs

    $result = $xoopsDB->query('SELECT artist, title, count(*) as cont ' .
            "FROM $listTable " .
            "WHERE 1 $scopeSql " .
            'GROUP BY artist, title order by cont ' .
            'DESC, artist, title ' .
            'LIMIT 10;');

    $aryTopSongs = [];

    while (false !== ($row = $xoopsDB->fetchArray($result))) {
        $aryTopSongs[] = $row;
    }

    $xoopsTpl->assign('topSongs', $aryTopSongs);

    // TOP 10 genres

    $result = $xoopsDB->query('SELECT genre, count(*) as cont ' .
            "FROM $listTable " .
            "WHERE 1 $scopeSql " .
            'GROUP BY genre ' .
            'ORDER BY cont DESC ' .
            'LIMIT 10;');

    $aryTopGenre = [];

    while (false !== ($row = $xoopsDB->fetchArray($result))) {
        $aryTopGenre[] = $row;
    }

    $xoopsTpl->assign('topGenres', $aryTopGenre);

    // TOP 10 years

    $result = $xoopsDB->query('SELECT year, count(*) as cont ' .
            "FROM $listTable " .
            "WHERE 1 $scopeSql " .
            'GROUP BY year order by cont ' .
            'DESC, year ' .
            'LIMIT 10;');

    $aryTopYear = [];

    while (false !== ($row = $xoopsDB->fetchArray($result))) {
        $aryTopYear[] = $row;
    }

    $xoopsTpl->assign('topYears', $aryTopYear);
} elseif ('menuTime' == $page) {
    $result = $xoopsDB->query("SELECT DATE_FORMAT(timeplayed, '%k') as timeoftheday, " .
            'count(*) as cont ' .
            "FROM $listTable " .
            "WHERE 1 $scopeSql " .
            'GROUP BY timeoftheday ' .
            'ORDER BY cont DESC');

    $aryDayStats = [];

    // One element for each hour even if it's empty

    for ($i = 0; $i <= 23; $i++) {
        $aryDayStats[$i] = '0';
    }

    $totalsongs = 0;

    $maxValue = 0;

    while (false !== ($row = $xoopsDB->fetchArray($result))) {
        $aryDayStats[$row['timeoftheday']] = $row['cont'];

        $count = $row['cont'];

        $totalsongs += $count;

        if ($count > $maxValue) {
            $maxValue = $count;
        }
    }

    for ($i = 0; $i <= 23; $i++) {
        $cont = $aryDayStats[$i];

        if (0 == $maxValue) {
            $realPercent = 0;

            $size = 0;
        } else {
            $realPercent = round((($cont / $totalsongs) * 100), 2);

            $size = floor((($cont / $maxValue) * 100));
        }

        $aryDayStats[$i] = ['hour' => $i . ':00', 'count' => $cont, 'percent' => $realPercent, 'size' => $size];
    }

    $aryDayStats[$i] = ['hour' => 'Total:', 'count' => $totalsongs, 'percent' => '', 'size' => 0];

    $xoopsTpl->assign('dayStats', $aryDayStats);
} elseif ('menuDate' == $page) {
}

// Sets the highlighted menu
$xoopsTpl->assign($page, 'menuStatsHighLight');
$xoopsTpl->assign('page', $page);

// Localized constants
$xoopsTpl->assign('mlArtist', _ML_ARTIST);
$xoopsTpl->assign('mlTitle', _ML_TITLE);
$xoopsTpl->assign('mlAlbum', _ML_ALBUM);
$xoopsTpl->assign('mlCount', _ML_COUNT);
$xoopsTpl->assign('mlTimePlayed', _ML_TIME_PLAYED);
$xoopsTpl->assign('mlGenre', _ML_GENRE);
$xoopsTpl->assign('mlYear', _ML_YEAR);
$xoopsTpl->assign('mlHour', _ML_HOUR);
$xoopsTpl->assign('mlPercentage', _ML_PERCENTAGE);

$xoopsTpl->assign('mlTopArtists', _ML_TOP_ARTISTS);
$xoopsTpl->assign('mlTopSongs', _ML_TOP_SONGS);
$xoopsTpl->assign('mlTopGenres', _ML_TOP_GENRES);
$xoopsTpl->assign('mlTopYears', _ML_TOP_YEARS);

$xoopsTpl->assign($scope, 'selected');

require_once XOOPS_ROOT_PATH . '/footer.php';
