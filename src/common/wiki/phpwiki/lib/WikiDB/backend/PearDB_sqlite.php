<?php // -*-php-*-
/** 
 * SQLite PearDB backend by Matthew Palmer
 * The SQLite DB will gain popularity with the current MySQL vs PHP license drama.
 * It's in core since PHP-5.0, MySQL not anymore.
 *
 * Initial setup:
 * sqlite -init /tmp/phpwiki-sqlite.db 
 * sqlite /tmp/phpwiki-sqlite.db < schemas/sqlite.sql
 */
rcs_id('$Id: PearDB_sqlite.php,v 1.3 2004/07/08 15:35:17 rurban Exp $');

require_once('lib/WikiDB/backend/PearDB.php');

//TODO: create tables on virgin wiki
/*
    $db = &new DB_sqlite();
    $db->connect($DBParams['dsn'], array('persistent'=> true) );
    $result = $db->query("CREATE TABLE $table (comment varchar(50), 
      datetime varchar(50));");
*/

class WikiDB_backend_PearDB_sqlite
extends WikiDB_backend_PearDB
{
    /**
     * Pack tables.
     */
    function optimize() {
    // NOP
    }

    /**
     * Lock tables.
     */
    function _lock_tables($write_lock = true) {
    // NOP - SQLite does all locking automatically
    }

    /**
     * Release all locks.
     */
    function _unlock_tables() {
    // NOP
    }

    /**
     * Serialize data
     */
    function _serialize($data) {
        if (empty($data))
            return '';
        assert(is_array($data));
        return base64_encode(serialize($data));
    }

    /**
     * Unserialize data
     */
    function _unserialize($data) {
        if (empty($data))
            return array();
        // Base64 encoded data does not contain colons.
        //  (only alphanumerics and '+' and '/'.)
        if (substr($data,0,2) == 'a:')
            return unserialize($data);
        return unserialize(base64_decode($data));
    }

    // same as DB::getSpecialQuery('tables')
    /*
    function sqlite_list_tables (&$dblink) {
       $tables = array ();
       $sql = "SELECT name FROM sqlite_master WHERE (type = 'table')";
       if ($res = sqlite_query ($dblink, $sql)) {
           while (sqlite_has_more($res)) {
               $tables[] = sqlite_fetch_single($res);
           }
       }
       return $tables;
    }
    */

   function _table_exists (&$dblink, $table) {
       $sql = "SELECT count(name) FROM sqlite_master WHERE ((type = 'table') and (name = '$table'))";
       if ($res = sqlite_query ($dblink, $sql)) {
           return sqlite_fetch_single($res) > 0;
       } else {
           return false; // or throw exception
       }
   }

};
    
// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   
?>