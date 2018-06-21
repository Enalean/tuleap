<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 *
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com> 
 *
 * IMMucLog : class to manage generic MUC Logs (conversations, system logs, ...)
 */
      
abstract class IMMucLog {

    /**
     * Duration between two conversation, in minutes.
     * If there is no real activity during this amount of minutes, 
     * the log systme will consider the next activity as a new conversation.
     * isLoggedAsActivity function will determine if the activity is
     * considered as real activity or not.
     */
    const DELAY_BETWEEN_CONVERSATIONS = 10;
    
    /**
     * Date of the log (timestamp in milliseconds)
     */
    protected $_date;
    
    /**
     * Nickname of the log's author (can be different from the real name)
     */
    protected $_nickname;
    
    /**
     * Username of the log's author (real Codendi username)
     */
    protected $_username;
    
    /**
     * Log's message
     */
    protected $_message;
    
    function __construct($date, $nickname, $username, $message) {
        $this->_date = $date;
        $this->_nickname = $nickname;
        $this->_username = $username;
        $this->_message = $message;
    }

    function getDate() {
        return util_timestamp_to_userdateformat($this->_date / 1000);
    }
    
    function getDay() {
        return util_timestamp_to_userdateformat($this->_date / 1000, true);
    }
    
    function getTime() {
        return format_date("H:i", $this->_date / 1000, true);
    }
    
    function getTimestamp() {
        return floor($this->_date / 1000);
    }
        
    
    function getNickname() {
        return $this->_nickname;
    }
    
    function getUsername() {
        return $this->_username;
    }
    
    function getMessage() {
        return $this->_message;
    }

}

?>