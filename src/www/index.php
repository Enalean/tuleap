<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('www/forum/forum_utils.php');
require_once('features_boxes.php');

$HTML->header(array('title'=>$Language->getText('homepage', 'title')));

echo '<div id="homepage">';

echo '<div id="homepage_speech">';
$speechFile = $Language->getContent('homepage/homepage', null, null, '.php');
if (strpos($speechFile, 'homepage') !== false) {
    include ($speechFile);
} else {
    echo stripcslashes($Language->getText('homepage', 'introduction',array($GLOBALS['sys_org_name'],$GLOBALS['sys_name'])));
}
echo '</div>';

if (isset($GLOBALS['sys_display_homepage_boxes']) && $GLOBALS['sys_display_homepage_boxes'] == 1) {
    echo '<div id="homepage_boxes">';
    show_features_boxes();
    echo '</div>';
}

echo '<div id="homepage_news">';

$w = new StaticWidget($Language->getText('homepage', 'news_title'));
$w->setContent(news_show_latest($GLOBALS['sys_news_group'],5,true,false,true,5));
$w->setRss('/export/rss_sfnews.php');
$w->display();

echo '</div>';

echo '</div>';

$HTML->footer(array());

?>
