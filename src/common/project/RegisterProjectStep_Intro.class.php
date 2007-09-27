<?php
require_once('RegisterProjectStep.class.php');

/**
* RegisterProjectStep_Intro
* 
* TODO: description
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class RegisterProjectStep_Intro extends RegisterProjectStep {
    function RegisterProjectStep_Intro() {
        $this->RegisterProjectStep(
            $GLOBALS['Language']->getText('register_title', 'intro'),
            'CreatingANewProject.html'
        );
    }
    function display($data) {
        include($GLOBALS['Language']->getContent('project/intro'));
        echo <<<EOS
        <script type="text/javascript" src="/scripts/prototype/prototype.js"></script>
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
