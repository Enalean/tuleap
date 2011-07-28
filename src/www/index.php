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

$display_homepage_boxes = !isset($GLOBALS['sys_display_homepage_boxes']) || (isset($GLOBALS['sys_display_homepage_boxes']) && $GLOBALS['sys_display_homepage_boxes'] == 1);

echo '<div id="homepage_speech" '. ($display_homepage_boxes ? '' : 'style="width:100%;"') .'>';
include ($Language->getContent('homepage/homepage', null, null, '.php'));
echo '</div>';

if ($display_homepage_boxes) {
    echo '<div id="homepage_boxes">';
    show_features_boxes();
    echo '</div>';
}

// HTML is sad, we need to keep this div to clear the "float:right/left" that might exists before
// Yet another dead kitten somewhere :'(
echo '<div id="homepage_news">';
if (!isset($GLOBALS['sys_display_homepage_news']) || (isset($GLOBALS['sys_display_homepage_news']) && $GLOBALS['sys_display_homepage_news'] == 1)) {
    $w = new Widget_Static($Language->getText('homepage', 'news_title'));
    $w->setContent(news_show_latest($GLOBALS['sys_news_group'],5,true,false,true,5));
    $w->setRssUrl('/export/rss_sfnews.php');
    $w->display();
}
echo '</div>';

echo '</div>';

$HTML->footer(array());

?>
