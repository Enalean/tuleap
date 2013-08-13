<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

require_once('trove.php');

require_once('RegisterProjectStep.class.php');

/**
* RegisterProjectStep_Category
* 
*/
class RegisterProjectStep_Category extends RegisterProjectStep {
    function RegisterProjectStep_Category() {
        $this->RegisterProjectStep(
            $GLOBALS['Language']->getText('register_title', 'category', array($GLOBALS['sys_name'])),
            'new-project.html'
        );
    }
    function display($data) {
        $group_id = $data['project']['built_from_template'];
        include($GLOBALS['Language']->getContent('project/category'));
    }
    function onLeave($request, &$data) {
        $data['project']['trove'] = array();
        if ($request->exist('root1')) {
            $root1 = $request->get('root1');
            if (is_array($root1)) {
                foreach($root1 as $rootnode => $value) {
                    for($i = 1 ; $i <= $GLOBALS['TROVE_MAXPERROOT'] ; $i++) {
                        $trove = $request->get('root'. $i);
                        $value = $trove[$rootnode];
                        if ($value) {
                            $data['project']['trove'][$rootnode][] = $value;
                        }
                    }
                }
            }
        }
        return true;
    }
}

?>
