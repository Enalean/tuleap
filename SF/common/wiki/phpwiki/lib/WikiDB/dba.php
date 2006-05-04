<?php rcs_id('$Id: dba.php 1422 2005-04-12 13:33:49Z guerin $');

require_once('lib/WikiDB.php');
require_once('lib/WikiDB/backend/dba.php');
/**
 *
 */
class WikiDB_dba extends WikiDB
{
    function WikiDB_dba ($dbparams) {
        $backend = new WikiDB_backend_dba($dbparams);
        $this->WikiDB($backend, $dbparams);

        if (empty($dbparams['directory'])
            || preg_match('@^/tmp\b@', $dbparams['directory'])) {
            $this->_warnings
                = sprintf(_("DBA files are in the %s directory. Please read the INSTALL file and move the DB file to a permanent location or risk losing all the pages!"),'/tmp');
        }
        else
            $this->_warnings = false;
    }
    
    function genericWarnings () {
        return $this->_warnings;
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
