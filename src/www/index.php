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
if ($speechFile != '') {
    include ($speechFile);
} else {
    echo stripcslashes($Language->getText('homepage', 'introduction',array($GLOBALS['sys_org_name'],$GLOBALS['sys_name'])));
}
echo '</div>';

if (isset($GLOBALS['sys_display_homepage_boxes']) && $GLOBALS['sys_display_homepage_boxes'] == 1) {
    echo '<div id="homepage_boxes">';
    echo show_features_boxes();
    echo '</div>';
}

echo '<div id="homepage_news">';
$HTML->box1_top($Language->getText('homepage', 'news_title')."<a href=\"/export/rss_sfnews.php\" title=\"".$Language->getText('homepage', 'news_title2').'"><img src="'.util_get_dir_image_theme().'/ic/feed.png"></a>');
echo news_show_latest($GLOBALS['sys_news_group'],5,true,false,false,5);
$HTML->box1_bottom();
echo '</div>';

echo '</div>';

$HTML->footer(array());

?>
