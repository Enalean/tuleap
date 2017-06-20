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

if ($display_homepage_boxes) { 
?>
    <hr />
    
    <div class="row-fluid">
        <div class="span4">
            <?php include($Language->
getContent('homepage/homepage_boxes_statistics', null, null, '.php')); ?>
        </div>
        <div class="span4">
            <?php include($Language->getContent('homepage/homepage_boxes_latestprojects', null, null, '.php')); ?>
        </div>
        <div class="span4">
            <?php include($Language->
getContent('homepage/homepage_boxes_latestreleases', null, null, '.php')); ?>
        </div>
    </div>
<?php 
}
?>