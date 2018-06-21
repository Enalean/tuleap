<?php rcs_id('$Id: DbSession.php,v 1.35 2005/08/07 10:51:11 rurban Exp $');

/**
 * Store sessions data in Pear DB / ADODB / dba / PDO, ....
 *
 * History
 *
 * Originally by Stanislav Shramko <stanis@movingmail.com>
 * Minor rewrite by Reini Urban <rurban@x-ray.at> for Phpwiki.
 * Quasi-major rewrite/decruft/fix by Jeff Dairiki <dairiki@dairiki.org>.
 * ADODB, dba and PDO classes by Reini Urban.
 *
 * Warning: Enable USE_SAFE_DBSESSION if you get INSERT duplicate id warnings.
 */
class DbSession
{
    /**
     * Constructor
     *
     * @param mixed $dbh
     * DB handle, or WikiDB object (from which the DB handle will
     * be extracted.
     *
     * @param string $table
     * Name of SQL table containing session data.
     */
    function __construct(&$dbh, $table = 'session') {
        // Check for existing DbSession handler
        $db_type = $dbh->getParam('dbtype');
        if (isa($dbh, 'WikiDB')) {
            // will fail with php4 and case-sensitive filesystem
            //$db_type = substr(get_class($dbh),7); 
            
            // < 4.1.2 crash on dba sessions at session_write_close(). 
            // (Tested with 4.1.1 and 4.1.2)
            // Didn't try postgres sessions.
            if (!check_php_version(4,1,2) and $db_type == 'dba')
                return false;

            @include_once("lib/DbSession/".$db_type.".php");
            
            $class = "DbSession_".$db_type;
            if (class_exists($class)) {
                // dba has no ->_dbh, so this is used for the session link
                $this->_backend = new $class($dbh->_backend->_dbh, $table);
                return $this;
            }
        }
        //Fixme: E_USER_WARNING ignored!
        trigger_error(sprintf(_("Your WikiDB DB backend '%s' cannot be used for DbSession.")." ".
                              _("Set USE_DB_SESSION to false."),
                             $db_type), E_USER_WARNING);
        return false;
    }
    function query($sql) {
        return $this->_backend->query($sql);
    }
    function quote($string) { return $string; }
}

// $Log: DbSession.php,v $
// Revision 1.35  2005/08/07 10:51:11  rurban
// reformatting
//
// Revision 1.34  2005/08/07 10:08:33  rurban
// dba simplification: no _backend in the subclass
//
// Revision 1.33  2005/02/27 19:40:36  rurban
// fix for php4 and case-sensitive filesystems
//
// Revision 1.32  2005/02/11 14:41:57  rurban
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