<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 *
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com> 
 *
 * IMMucSystemLog manage logs produced by the system
 * 
 * Inherited concrete classes embedded in this file:
 * - IMMucJoinTheRoomSystemLog
 * - IMMucLeftTheRoomSystemLog
 * - IMMucChangeTopicSystemLog
 * 
 */

require_once('IMMucLog.class.php');

abstract class IMMucSystemLog extends IMMucLog {
    
    protected $_nickname;

}

class IMMucJoinTheRoomSystemLog extends IMMucSystemLog {

    function __construct($date, $nickname) {
        $this->_nickname = $nickname;
        parent::__construct($date, '', 'system', $GLOBALS['Language']->getText('plugin_im', 'muc_logs_join', array($nickname)));
    }

}

class IMMucLeftTheRoomSystemLog extends IMMucSystemLog {

    function __construct($date, $nickname) {
        $this->_nickname = $nickname;
        parent::__construct($date, '', 'system', $GLOBALS['Language']->getText('plugin_im', 'muc_logs_left', array($nickname)));
    }

}

class IMMucChangeTopicSystemLog extends IMMucSystemLog {

    protected $_topic;

    function __construct($date, $nickname, $new_topic) {
        $this->_nickname = $nickname;
        $this->_topic = $new_topic;
        parent::__construct($date, '', 'system', $GLOBALS['Language']->getText('plugin_im', 'muc_logs_settopic', array($nickname, $new_topic)));
    }

}

?>