<?php rcs_id('$Id: ADODB.php,v 1.1 2005/02/11 14:41:40 rurban Exp $');
/*
 Copyright 2005 $ThePhpWikiProgrammingTeam

 This file is part of PhpWiki.

 PhpWiki is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 PhpWiki is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with PhpWiki; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/ 

/** 
 * ADODB db sessions, based on pear DB Sessions.
 *
 * @author: Reini Urban
 */
class DbSession_ADODB
extends DbSession
{
    var $_backend_type = "ADODB";

    function __construct ($dbh, $table) {

        $this->_dbh = $dbh;
        $this->_table = $table;

        ini_set('session.save_handler','user');
        session_module_name('user'); // new style
        session_set_save_handler(array(&$this, 'open'),
                                 array(&$this, 'close'),
                                 array(&$this, 'read'),
                                 array(&$this, 'write'),
                                 array(&$this, 'destroy'),
                                 array(&$this, 'gc'));
        return $this;
    }

    function & _connect() {
        global $request;
        static $parsed = false;
        $dbh = &$this->_dbh;
        if (!$dbh or !is_resource($dbh->_connectionID)) {
            if (!$parsed) $parsed = parseDSN($request->_dbi->getParam('dsn'));
            $this->_dbh =& ADONewConnection($parsed['phptype']); // Probably only MySql works just now
            $this->_dbh->Connect($parsed['hostspec'],$parsed['username'], 
                                 $parsed['password'], $parsed['database']);
            $dbh = &$this->_dbh;                             
        }
        return $dbh;
    }
    
    function query($sql) {
        return $this->_dbh->Execute($sql);
    }

    function quote($string) {
        return $this->_dbh->qstr($string);
    }

    function _disconnect() {
        if (0 and $this->_dbh)
            $this->_dbh->close();
    }

    /**
     * Opens a session.
     *
     * Actually this function is a fake for session_set_save_handle.
     * @param  string $save_path a path to stored files
     * @param  string $session_name a name of the concrete file
     * @return boolean true just a variable to notify PHP that everything 
     * is good.
     * @access private
     */
    function open ($save_path, $session_name) {
        //$this->log("_open($save_path, $session_name)");
        return true;
    }

    /**
     * Closes a session.
     *
     * This function is called just after <i>write</i> call.
     *
     * @return boolean true just a variable to notify PHP that everything 
     * is good.
     * @access private
     */
    function close() {
        //$this->log("_close()");
        return true;
    }

    /**
     * Reads the session data from DB.
     *
     * @param  string $id an id of current session
     * @return string
     * @access private
     */
    function read ($id) {
        //$this->log("_read($id)");
        $dbh = $this->_connect();
        $table = $this->_table;
        $qid = $dbh->qstr($id);
        $res = '';
        $row = $dbh->GetRow("SELECT sess_data FROM $table WHERE sess_id=$qid");
        if ($row)
            $res = $row[0];
        $this->_disconnect();
        if (!empty($res) and preg_match('|^[a-zA-Z0-9/+=]+$|', $res))
            $res = base64_decode($res);
        if (strlen($res) > 4000) {
            trigger_error("Overlarge session data! ".strlen($res).
                        " gt. 4000", E_USER_WARNING);
            $res = preg_replace('/s:6:"_cache";O:12:"WikiDB_cache".+}$/',"",$res);
            $res = preg_replace('/s:12:"_cached_html";s:.+",s:4:"hits"/','s:4:"hits"',$res);
            if (strlen($res) > 4000) $res = '';
        }
        return $res;
    }
  
    /**
     * Saves the session data into DB.
     *
     * Just  a  comment:       The  "write"  handler  is  not 
     * executed until after the output stream is closed. Thus,
     * output from debugging statements in the "write" handler
     * will  never be seen in the browser. If debugging output
     * is  necessary, it is suggested that the debug output be
     * written to a file instead.
     *
     * @param  string $id
     * @param  string $sess_data
     * @return boolean true if data saved successfully  and false
     * otherwise.
     * @access private
     */
    function write ($id, $sess_data) {
        
        $dbh = $this->_connect();
        $table = $this->_table;
        $qid = $dbh->qstr($id);
        $qip = $dbh->qstr($GLOBALS['request']->get('REMOTE_ADDR'));
        $time = $dbh->qstr(time());

        // postgres can't handle binary data in a TEXT field.
        if (isa($dbh, 'ADODB_postgres64'))
            $sess_data = base64_encode($sess_data);
        $qdata = $dbh->qstr($sess_data);

        /* AffectedRows with sessions seems to be instable on certain platforms.
         * Enable the safe and slow USE_SAFE_DBSESSION then.
         */
        if (USE_SAFE_DBSESSION) {
            $dbh->Execute("DELETE FROM $table"
                          . " WHERE sess_id=$qid");
            $rs = $dbh->Execute("INSERT INTO $table"
                                . " (sess_id, sess_data, sess_date, sess_ip)"
                                . " VALUES ($qid, $qdata, $time, $qip)");
        } else {
            $rs = $dbh->Execute("UPDATE $table"
                                . " SET sess_data=$qdata, sess_date=$time, sess_ip=$qip"
                                . " WHERE sess_id=$qid");
            $result = $dbh->Affected_Rows();
            if ( $result === false or $result < 1 ) { // false or int > 0
                $rs = $dbh->Execute("INSERT INTO $table"
                                    . " (sess_id, sess_data, sess_date, sess_ip)"
                                    . " VALUES ($qid, $qdata, $time, $qip)");
            }
        }
        $result = ! $rs->EOF;
        if ($result) $rs->free();                        
        $this->_disconnect();
        return $result;
    }

    /**
     * Destroys a session.
     *
     * Removes a session from the table.
     *
     * @param  string $id
     * @return boolean true 
     * @access private
     */
    function destroy ($id) {
        $dbh = $this->_connect();
        $table = $this->_table;
        $qid = $dbh->qstr($id);

        $dbh->Execute("DELETE FROM $table WHERE sess_id=$qid");

        $this->_disconnect();
        return true;     
    }

    /**
     * Cleans out all expired sessions.
     *
     * @param  int $maxlifetime session's time to live.
     * @return boolean true
     * @access private
     */
    function gc ($maxlifetime) {
        $dbh = $this->_connect();
        $table = $this->_table;
        $threshold = time() - $maxlifetime;

        $dbh->Execute("DELETE FROM $table WHERE sess_date < $threshold");

        $this->_disconnect();
        return true;
    }
}

// $Log: ADODB.php,v $
// Revision 1.2  2005/11/21 20:48:48  rurban
// fix ref warnings reported by schorni
//
// Revision 1.1  2005/02/11 14:41:40  rurban
// seperate DbSession classes: less memory, a bit slower
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>