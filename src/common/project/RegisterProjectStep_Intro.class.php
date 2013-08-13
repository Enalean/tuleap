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

require_once('RegisterProjectStep.class.php');

/**
* RegisterProjectStep_Intro
*/
class RegisterProjectStep_Intro extends RegisterProjectStep {
    function RegisterProjectStep_Intro() {
        $this->RegisterProjectStep(
            $GLOBALS['Language']->getText('register_title', 'intro'),
            'new-project.html'
        );
    }
    function display($data) {
        include($GLOBALS['Language']->getContent('project/intro'));
        echo <<<EOS
        <script type="text/javascript">
        Event.observe(window, 'load', function() {
                if (!\$F('register_tos_i_agree')) {
                    $('project_register_next').disabled = true;
                }
                Event.observe($('register_tos_i_agree'), 'click', function() {
                        $('project_register_next').disabled = !$('project_register_next').disabled;
                });
        });
        </script>
EOS;
    }
    function onLeave($request, &$data) {
        $data['i_agree'] = $request->get('i_agree');
        return $this->validate($data);
    }
    function validate($data) {
        if (!$data['i_agree']) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('register_form', 'must_agree', array($GLOBALS['sys_name'])));
            return false;
        }
        return true;
    }
}

?>
