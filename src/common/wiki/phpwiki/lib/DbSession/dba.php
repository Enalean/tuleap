<?php rcs_id('$Id: dba.php,v 1.3 2005/08/07 10:49:57 rurban Exp $');

/** DBA Sessions
 *  session:
 *     Index: session_id
 *    Values: date : IP : data
 * @author: Reini Urban.
 */
class DbSession_dba
extends DbSession
{
    var $_backend_type = "dba";

    function __construct (&$dbh, $table) {
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

    function & _connect() {
        global $DBParams;
        $dbh = &$this->_dbh;
        if (!$dbh) {
            $directory = '/tmp';
            $prefix = 'wiki_';
            $dba_handler = 'gdbm';
            $timeout = 20;
            extract($DBParams);

            $dbfile = "$directory/$prefix" . 'session' . '.' . $dba_handler;
            $dbh = new DbaDatabase($dbfile, 'c', $dba_handler);
            $this->_dbh = &$dbh;
        }
        return $dbh;
    }

    function _disconnect() {
        if (isset($this->_dbh))
            $this->_dbh->close();
    }

    function open ($save_path, $session_name) {
        $dbh = $this->_connect();
        $dbh->open();
    }

    function close() {
    	if ($this->_dbh)
            $this->_dbh->close();
    }

    function read ($id) {
        $dbh = $this->_connect();
        $result = $dbh->get($id);
        if (!$result) {
            return false;
        }
        list(,,$packed) = explode(':', $result, 3);
        // $this->_disconnect();
        if (strlen($packed) > 4000) {
            trigger_error("Overlarge session data!", E_USER_WARNING);
            $packed = '';
            //$res = preg_replace('/s:6:"_cache";O:12:"WikiDB_cache".+}$/',"",$res);
        }
        return $packed;
    }
  
    function write ($id, $sess_data) {
        $dbh = $this->_connect();
        $time = time();
        $ip = $GLOBALS['request']->get('REMOTE_ADDR');
        if (strlen($sess_data) > 4000) {
            trigger_error("Overlarge session data!", E_USER_WARNING);
            $sess_data = '';
        }
        $dbh->set($id, $time.':'.$ip.':'.$sess_data);
        //$this->_disconnect();
        return true;
    }

    function destroy ($id) {
        $dbh = $this->_connect();
        $dbh->delete($id);
        //$this->_disconnect();
        return true;
    }

    function gc ($maxlifetime) {
        $dbh = $this->_connect();
        $threshold = time() - $maxlifetime;
        for ($id = $dbh->firstkey(); $id !== false; $id = $dbh->nextkey()) {
            $result = $dbh->get($id);
            list($date,,) = explode(':', $result, 3);
            if ($date < $threshold)
                $dbh->delete($id);
        }
        //$this->_disconnect();
        return true;
    }
}

// $Log: dba.php,v $
// Revision 1.3  2005/08/07 10:49:57  rurban
// do not connect/disconnect each time. fix refs
//
// Revision 1.2  2005/08/07 10:07:55  rurban
// dba simplification: use default timeout
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