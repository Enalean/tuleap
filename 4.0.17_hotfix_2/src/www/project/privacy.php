<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2010
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Displays privacy tooltip when user let it's mouse over the [Private] or
 * [Public] text in front of project summary tab
 */

require_once 'pre.php';

$projectType = $request->getValidated('project_type', new Valid_WhiteList('project_type', array('private', 'public')), 'public');

if ($projectType == 'public') {
    $privacy = 'public';
    if ($GLOBALS['sys_allow_anon']) {
        $privacy .= '_w_anon';
    } else {
        $privacy .= '_wo_anon';
    }
} else {
    $privacy = 'private';
}

if ($request->isAjax()) {
    echo $GLOBALS['Language']->getText('project_privacy', 'tooltip_'.$privacy);
}

?>
