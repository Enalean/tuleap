<?php rcs_id('$Id: DB_Session.php 1422 2005-04-12 13:33:49Z guerin $');

/**
 * Store sessions data in Pear DB / ADODB ....
 *
 * History
 *
 * Originally by Stanislav Shramko <stanis@movingmail.com>
 * Minor rewrite by Reini Urban <rurban@x-ray.at> for Phpwiki.
 * Quasi-major rewrite/decruft/fix by Jeff Dairiki <dairiki@dairiki.org>.
 */
class DB_Session
{
    var $_backend;
    /**
     * Constructor
     *
     * @param mixed $dbh
     * Pear DB handle, or WikiDB object (from which the Pear DB handle will
     * be extracted.
     *
     * @param string $table
     * Name of SQL table containing session data.
     */
    function DB_Session(&$dbh, $table = 'session') {
        // Coerce WikiDB to PearDB or ADODB.
        // Todo: adodb/dba handlers
        $db_type = $GLOBALS['DBParams']['dbtype'];
        if (isa($dbh, 'WikiDB')) {
            $backend = &$dbh->_backend;
            $db_type = substr(get_class($dbh),7);
            $class = "DB_Session_".$db_type;
            
            // < 4.1.2 crash on dba sessions at session_write_close(). 
            // (Tested with 4.1.1 and 4.1.2)
            // Didn't try postgres sessions.
            if (!check_php_version(4,1,2) and $db_type=='dba')
                return false;
                
            if (class_exists($class)) {
                $this->_backend = new $class($backend->_dbh, $table);
                return $this->_backend;
            }
        }
        //Fixme: E_USER_WARNING ignored!
        trigger_error(sprintf(
_("Your WikiDB DB backend '%s' cannot be used for DB_Session. Set USE_DB_SESSION to false."),
                             $db_type), E_USER_WARNING);
        return false;
    }
    
    function currentSessions() {
        return $this->_backend->currentSessions();
    }
    function query($sql) {
        return $this->_backend->query($sql);
    }
    function quote($string) {
        return $this->_backend->quote($string);
    }

}

class DB_Session_SQL
extends DB_Session
{
    var $_backend_type = "SQL";

    function DB_Session_SQL (&$dbh, $table) {

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

    function _connect() {
        $dbh = &$this->_dbh;
        $this->_connected = is_resource($dbh->connection);
        if (!$this->_connected) {
            $res = $dbh->connect($dbh->dsn);
            if (DB::isError($res)) {
                error_log("PhpWiki::DB_Session::_connect: " . $res->getMessage());
            }
        }
        return $dbh;
    }
    
    function query($sql) {
        return $this->_dbh->query($sql);
    }

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
        $dbh = &$this->_connect();
        $table = $this->_table;
        $qid = $dbh->quote($id);
    
        $res = $dbh->getOne("SELECT sess_data FROM $table WHERE sess_id=$qid");

        $this->_disconnect();
        if (DB::isError($res) || empty($res))
            return '';
        if (isa($dbh, 'DB_pgsql'))
            //if (preg_match('|^[a-zA-Z0-9/+=]+$|', $res))
            $res = base64_decode($res);
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
        
        $dbh = &$this->_connect();
        //$dbh->unlock(false,1);
        $table = $this->_table;
        $qid = $dbh->quote($id);
        $qip = $dbh->quote($GLOBALS['HTTP_SERVER_VARS']['REMOTE_ADDR']);
        $time = time();
	if (DEBUG and $sess_data == 'wiki_user|N;') {
	    trigger_error("delete session $qid",E_USER_WARNING);
	    /*echo "<pre>";
	    print_r($GLOBALS['request']->_user);
	    echo "</pre>";
	    */
	}
        // postgres can't handle binary data in a TEXT field.
        if (isa($dbh, 'DB_pgsql'))
            $sess_data = base64_encode($sess_data);
        $qdata = $dbh->quote($sess_data);
        
        $res = $dbh->query("UPDATE $table"
                           . " SET sess_data=$qdata, sess_date=$time, sess_ip=$qip"
                           . " WHERE sess_id=$qid");
        if ( $dbh->affectedRows() < 1 ) // 0 (none) or -1 (failure) on mysql
            $res = $dbh->query("INSERT INTO $table"
                               . " (sess_id, sess_data, sess_date, sess_ip)"
                               . " VALUES ($qid, $qdata, $time, $qip)");

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
        $dbh = &$this->_connect();
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
        $dbh = &$this->_connect();
        $table = $this->_table;
        $threshold = time() - $maxlifetime;

        $dbh->query("DELETE FROM $table WHERE sess_date < $threshold");

        $this->_disconnect();
        return true;
    }

    // WhoIsOnline support
    // TODO: ip-accesstime dynamic blocking API
    function currentSessions() {
        $sessions = array();
        $dbh = &$this->_connect();
        $table = $this->_table;
        $res = $dbh->query("SELECT sess_data,sess_date,sess_ip FROM $table ORDER BY sess_date DESC");
        if (DB::isError($res) || empty($res))
            return $sessions;
        while ($row = $res->fetchRow()) {
            $data = $row['sess_data'];
            $date = $row['sess_date'];
            $ip   = $row['sess_ip'];
            if (preg_match('|^[a-zA-Z0-9/+=]+$|', $data))
                $data = base64_decode($data);
            // session_data contains the <variable name> + "|" + <packed string>
            // we need just the wiki_user object (might be array as well)
            $user = strstr($data,"wiki_user|");
            $sessions[] = array('wiki_user' => substr($user,10), // from "O:" onwards
                                'date' => $date,
                                'ip'   => $ip);
        }
        $this->_disconnect();
        return $sessions;
    }
}

// self-written adodb-sessions
class DB_Session_ADODB
extends DB_Session
{
    var $_backend_type = "ADODB";

    function DB_Session_ADODB ($dbh, $table) {

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

    function _connect() {
        global $DBParams;
        static $parsed = false;
        $dbh = &$this->_dbh;
        if (!$dbh or !is_resource($dbh->_connectionID)) {
            if (!$parsed) $parsed = parseDSN($DBParams['dsn']);
            $this->_dbh = &ADONewConnection($parsed['phptype']); // Probably only MySql works just now
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
        $dbh = &$this->_connect();
        $table = $this->_table;
        $qid = $dbh->qstr($id);
        $res = '';
        $row = $dbh->GetRow("SELECT sess_data FROM $table WHERE sess_id=$qid");
        if ($row)
            $res = $row[0];
        $this->_disconnect();
        if (!empty($res) and preg_match('|^[a-zA-Z0-9/+=]+$|', $res))
            $res = base64_decode($res);
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
        
        $dbh = &$this->_connect();
        $table = $this->_table;
        $qid = $dbh->qstr($id);
        $qip = $dbh->qstr($GLOBALS['HTTP_SERVER_VARS']['REMOTE_ADDR']);
        $time = time();

        // postgres can't handle binary data in a TEXT field.
        if (isa($dbh, 'ADODB_postgres64'))
            $sess_data = base64_encode($sess_data);
        $qdata = $dbh->qstr($sess_data);
        $rs = $dbh->Execute("UPDATE $table"
                           . " SET sess_data=$qdata, sess_date=$time, sess_ip=$qip"
                           . " WHERE sess_id=$qid");
        if ( $dbh->Affected_Rows() < 1 ) // 0 (none) or -1 (failure) on mysql
            $rs = $dbh->Execute("INSERT INTO $table"
                               . " (sess_id, sess_data, sess_date, sess_ip)"
                               . " VALUES ($qid, $qdata, $time, $qip)");
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
        $dbh = &$this->_connect();
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
        $dbh = &$this->_connect();
        $table = $this->_table;
        $threshold = time() - $maxlifetime;

        $dbh->Execute("DELETE FROM $table WHERE sess_date < $threshold");

        $this->_disconnect();
        return true;
    }

    // WhoIsOnline support. 
    // TODO: ip-accesstime dynamic blocking API
    function currentSessions() {
        $sessions = array();
        $dbh = &$this->_connect();
        $table = $this->_table;
        $rs = $this->Execute("SELECT sess_data,sess_date,sess_ip FROM $table ORDER BY sess_date DESC");
        if ($rs->EOF) {
            $rs->free();
            return $sessions;
        }
        while (!$rs->EOF) {
            $row = $rs->fetchRow();
            $data = $row[0];
            $date = $row[1];
            $ip   = $row[2];
            if (preg_match('|^[a-zA-Z0-9/+=]+$|', $data))
                $data = base64_decode($data);
            // session_data contains the <variable name> + "|" + <packed string>
            // we need just the wiki_user object (might be array as well)
            $user = strstr($data,"wiki_user|");
            $sessions[] = array('wiki_user' => substr($user,10), // from "O:" onwards
                                'date' => $date,
                                'ip' => $ip);
            $rs->MoveNext();
        }
        $rs->free();
        $this->_disconnect();
        return $sessions;
    }
}

/** DBA Sessions
 *  session:
 *    Index: session_id
 *   Values: date : IP : data
 */
class DB_Session_dba
extends DB_Session
{
    var $_backend_type = "dba";

    function DB_Session_dba (&$dbh, $table) {
        $this->_dbh = $dbh;
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

    function quote($str) { return $str; }
    function query($sql) { return false; }

    function _connect() {
        global $DBParams;
        $dbh = &$this->_dbh;
        if (!$dbh) {
            $directory = '/tmp';
            $prefix = 'wiki_';
            $dba_handler = 'gdbm';
            $timeout = 20;
            extract($DBParams);
            $dbfile = "$directory/$prefix" . 'session' . '.' . $dba_handler;
            $dbh = new DbaDatabase($dbfile, false, $dba_handler);
            $dbh->set_timeout($timeout);
            if (!$dbh->open('c')) {
                trigger_error(sprintf(_("%s: Can't open dba database"), $dbfile), E_USER_ERROR);
                global $request;
                $request->finish(fmt("%s: Can't open dba database", $dbfile));
            }
            $this->_dbh = &$dbh;
        }
        return $dbh;
    }

    function _disconnect() {
        if (0 and isset($this->_dbh))
            $this->_dbh->close();
    }

    function open ($save_path, $session_name) {
        $dbh = &$this->_connect();
        $dbh->open();
    }

    function close() {
    	if ($this->_dbh)
            $this->_dbh->close();
    }

    function read ($id) {
        $dbh = &$this->_connect();
        $result = $dbh->get($id);
        if (!$result) {
            return false;
        }
        list(,,$packed) = explode(':', $result, 3);
        $this->_disconnect();
        return $packed;
    }
  
    function write ($id, $sess_data) {
        $dbh = &$this->_connect();
        $time = time();
        $ip = $GLOBALS['HTTP_SERVER_VARS']['REMOTE_ADDR'];
        $dbh->set($id,$time.':'.$ip.':'.$sess_data);
        $this->_disconnect();
        return true;
    }

    function destroy ($id) {
        $dbh = &$this->_connect();
        $dbh->delete($id);
        $this->_disconnect();
        return true;
    }

    function gc ($maxlifetime) {
        $dbh = &$this->_connect();
        $threshold = time() - $maxlifetime;
        for ($id = $dbh->firstkey(); $id !== false; $id = $dbh->nextkey()) {
            $result = $dbh->get($id);
            list($date,,) = explode(':', $result, 3);
            //$dbh->query("DELETE FROM $table WHERE sess_date < $threshold");
            if ($date < $threshold)
                $dbh->delete($id);
        }
        $this->_disconnect();
        return true;
    }

    // WhoIsOnline support. 
    // TODO: ip-accesstime dynamic blocking API
    function currentSessions() {
        $sessions = array();
        $dbh = &$this->_connect();
        for ($id = $dbh->firstkey(); $id !== false; $id = $dbh->nextkey()) {
            $result = $dbh->get($id);
            list($date,$ip,$packed) = explode(':', $result, 3);
            $data = unserialize($packed);
            // session_data contains the <variable name> + "|" + <packed string>
            // we need just the wiki_user object (might be array as well)
            $user = strstr($data,"wiki_user|");
            $sessions[] = array('wiki_user' => substr($user,10), // from "O:" onwards
                                'date' => $date,
                                'ip' => $ip);
        }
        $this->_disconnect();
        return $sessions;
    }
}


// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
