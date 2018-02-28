<?php
/**
 * Copyright (c) Enalean 2011. All rights reserved
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

// For backward compatibility: if the introduction speech was 
// customized in etc/site-content homepage.tab, we display him
// instead of following text.
if ($Language->hasText('homepage', 'introduction')) {
    echo stripcslashes($Language->getText('homepage', 'introduction', array($GLOBALS['sys_org_name'], $GLOBALS['sys_name'])));
    return;
}

$main_content_span = 'span12';
$login_input_span  = 'span6';
if ($display_homepage_news) {
    $main_content_span = 'span8';
}

?>


<div class="hero-unit">
    <?php include($Language->getContent('homepage/homepage_herounit', null, null, '.php')); ?>
</div>

<div class="row-fluid">
    <div class="<?= $main_content_span ?>">
        <div class="row-fluid">
            <div class="span6">
                <?php include($Language->getContent('homepage/homepage_about', null, null, '.php')); ?>
            </div>
            <div class="span6">
                <?php include($Language->getContent('homepage/homepage_interactions', null, null, '.php')); ?>
            </div>
        </div>
        <?php include($Language->getContent('homepage/homepage_boxes', null, null, '.php')); ?>
    </div>
    <?php include($Language->getContent('homepage/homepage_news', null, null, '.php')); ?>
</div>

<?php

//tell the upper script that it should'nt display boxes
$display_homepage_boxes = false;
$display_homepage_news  = false;

?>