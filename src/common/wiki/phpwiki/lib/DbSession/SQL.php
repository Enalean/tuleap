<?php rcs_id('$Id: SQL.php,v 1.1 2005/02/11 14:41:40 rurban Exp $');

/**
 * DB sessions for pear DB
 *
 * History
 *
 * Originally by Stanislav Shramko <stanis@movingmail.com>
 * Minor rewrite by Reini Urban <rurban@x-ray.at> for Phpwiki.
 * Quasi-major rewrite/decruft/fix by Jeff Dairiki <dairiki@dairiki.org>.
 */

class DbSession_SQL
extends DbSession
{
    var $_backend_type = "SQL";

    function __construct (&$dbh, $table) {

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
        $dbh = &$this->_dbh;
        $this->_connected = is_resource($dbh->connection);
        if (!$this->_connected) {
            $res = $dbh->connect($dbh->dsn);
            if (DB::isError($res)) {
                error_log("PhpWiki::DbSession::_connect: " . $res->getMessage());
            }
        }
        return $dbh;
    }
    
    function query($sql) {
        return $this->_dbh->query($sql);
    }
    // adds surrounding quotes
    function quote($string) {
        return $this->_dbh->quote($string);
    }

    function _disconnect() {
        if (0 and $this->_connected)
            $this->_dbh->disconnect();
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
        $qid = $dbh->quote($id);
    
        $res = $dbh->getOne("SELECT sess_data FROM $table WHERE sess_id=$qid");

        $this->_disconnect();
        if (DB::isError($res) || empty($res))
            return '';
        if (isa($dbh, 'DB_pgsql'))
            //if (preg_match('|^[a-zA-Z0-9/+=]+$|', $res))
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
        //$dbh->unlock(false,1);
        $table = $this->_table;
        $qid = $dbh->quote($id);
        $qip = $dbh->quote($GLOBALS['request']->get('REMOTE_ADDR'));
        $time = $dbh->quote(time());
	if (DEBUG and $sess_data == 'wiki_user|N;') {
	    trigger_error("delete empty session $qid", E_USER_WARNING);
	}
        // postgres can't handle binary data in a TEXT field.
        if (isa($dbh, 'DB_pgsql'))
            $sess_data = base64_encode($sess_data);
        $qdata = $dbh->quote($sess_data);

        /* AffectedRows with sessions seems to be instable on certain platforms.
         * Enable the safe and slow USE_SAFE_DBSESSION then.
         */
        if (USE_SAFE_DBSESSION) {
            $dbh->query("DELETE FROM $table"
                        . " WHERE sess_id=$qid");
            $res = $dbh->query("INSERT INTO $table"
                               . " (sess_id, sess_data, sess_date, sess_ip)"
                               . " VALUES ($qid, $qdata, $time, $qip)");
        } else {
            $res = $dbh->query("UPDATE $table"
                               . " SET sess_data=$qdata, sess_date=$time, sess_ip=$qip"
                               . " WHERE sess_id=$qid");
            $result = $dbh->AffectedRows();
            if ( $result === false or $result < 1 ) { // 0 cannot happen: time, -1 (failure) on mysql
                $res = $dbh->query("INSERT INTO $table"
                                   . " (sess_id, sess_data, sess_date, sess_ip)"
                                   . " VALUES ($qid, $qdata, $time, $qip)");
            }
        }
        $this->_disconnect();
        return ! DB::isError($res);
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
        $qid = $dbh->quote($id);

        $dbh->query("DELETE FROM $table WHERE sess_id=$qid");

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

        $dbh->query("DELETE FROM $table WHERE sess_date < $threshold");

        $this->_disconnect();
        return true;
    }
}


// $Log: SQL.php,v $
// Revision 1.2  2005/11/21 20:57:58  rurban
// fix ref warnings, analog to ADODB
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