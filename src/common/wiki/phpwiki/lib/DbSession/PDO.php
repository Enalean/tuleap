<?php rcs_id('$Id: PDO.php,v 1.1 2005/02/11 14:41:40 rurban Exp $');

/** 
 * Db sessions for PDO, based on pear DB Sessions.
 *
 * @author: Reini Urban
 */
class DbSession_PDO
extends DbSession
{
    var $_backend_type = "PDO";

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
        $dbh =& $this->_dbh;
        if (!$dbh or !is_object($dbh)) {
            global $DBParams;
            $db = new WikiDB_backend_PDO($DBParams);
            $this->_dbh =& $db->_dbh;
            $this->_backend =& $db;
        }
        return $dbh->_dbh;
    }
    
    function query($sql) {
        return $this->_backend->query($sql);
    }

    function quote($string) {
        return $this->_backend->quote($sql);
    }

    function _disconnect() {
        if (0 and $this->_dbh)
            unset($this->_dbh);
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
        $sth = $dbh->prepare("SELECT sess_data FROM $table WHERE sess_id=?");
        $sth->bindParam(1, $id, PDO_PARAM_STR, 32);
        if ($sth->execute()) $res = $sth->fetchSingle();
        else $res = '';
        $this->_disconnect();
        if (!empty($res) and isa($dbh, 'ADODB_postgres64'))
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
        $time = time();

        // postgres can't handle binary data in a TEXT field.
        if (isa($dbh, 'ADODB_postgres64'))
            $sess_data = base64_encode($sess_data);

        /* AffectedRows with sessions seems to be instable on certain platforms.
         * Enable the safe and slow USE_SAFE_DBSESSION then.
         */
        if (USE_SAFE_DBSESSION) {
            $this->_backend->beginTransaction();
            $rs = $this->query("DELETE FROM $table"
                               . " WHERE sess_id=$qid");
            $sth = $dbh->prepare("INSERT INTO $table"
                                . " (sess_id, sess_data, sess_date, sess_ip)"
                                 . " VALUES (?, ?, ?, ?)");
            $sth->bindParam(1, $id, PDO_PARAM_STR, 32);
            $sth->bindParam(2, $sess_data, PDO_PARAM_LOB);
            $sth->bindParam(3, $time, PDO_PARAM_INT);
            $sth->bindParam(4, $GLOBALS['request']->get('REMOTE_ADDR'), PDO_PARAM_STR, 15);
            if ($result = $sth->execute()) {
                $this->_backend->commit();
            } else {
                $this->_backend->rollBack();
            }
        } else {
            $sth = $dbh->prepare("UPDATE $table"
                                . " SET sess_data=?, sess_date=?, sess_ip=?"
                                . " WHERE sess_id=?");
            $sth->bindParam(1, $sess_data, PDO_PARAM_LOB);
            $sth->bindParam(2, $time, PDO_PARAM_INT);
            $sth->bindParam(3, $GLOBALS['request']->get('REMOTE_ADDR'), PDO_PARAM_STR, 15);
            $sth->bindParam(4, $id, PDO_PARAM_STR, 32);
            $result = $sth->execute(); // implicit affected rows
            if ( $result === false or $result < 1 ) { // false or int > 0
                $sth = $dbh->prepare("INSERT INTO $table"
                                     . " (sess_id, sess_data, sess_date, sess_ip)"
                                     . " VALUES (?, ?, ?, ?)");
                $sth->bindParam(1, $id, PDO_PARAM_STR, 32);
                $sth->bindParam(2, $sess_data, PDO_PARAM_LOB);
                $sth->bindParam(3, $time, PDO_PARAM_INT);
                $sth->bindParam(4, $GLOBALS['request']->get('REMOTE_ADDR'), PDO_PARAM_STR, 15);
                $result = $sth->execute();
            }
        }
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
        $table = $this->_table;
        $dbh = $this->_connect();
        $sth = $dbh->prepare("DELETE FROM $table WHERE sess_id=?");
        $sth->bindParam(1, $id, PDO_PARAM_STR, 32);
        $sth->execute();
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
        $table = $this->_table;
        $threshold = time() - $maxlifetime;
        $dbh = $this->_connect();
        $sth = $dbh->prepare("DELETE FROM $table WHERE sess_date < ?");
        $sth->bindParam(1, $threshold, PDO_PARAM_INT);
        $sth->execute();
        $this->_disconnect();
        return true;
    }
}

// $Log: PDO.php,v $
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