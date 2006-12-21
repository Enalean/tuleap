<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// $Id$


require_once('CodeXUpgrade.class.php');

class Update_002 extends CodeXUpgrade {

    function _process() {
        echo $this->getLineSeparator();
        $sqls = array("ALTER TABLE plugin_docman_metadata_value DROP INDEX idx_field_id;",
                     "ALTER TABLE plugin_docman_metadata_value DROP INDEX idx_artifact_id;",
                     "ALTER TABLE plugin_docman_metadata_value ADD INDEX idx_field_item_id (field_id, item_id);",
                     "ALTER TABLE plugin_docman_item ADD INDEX parent_id (parent_id);",
                     "ALTER TABLE plugin_docman_item ADD INDEX rank (rank);",
                     "ALTER TABLE plugin_docman_metadata DROP INDEX idx_name;",
                     "ALTER TABLE plugin_docman_metadata ADD INDEX idx_name (name (10));",
                     "ALTER TABLE plugin_docman_metadata_love DROP INDEX idx_fv_value_id;",
                     "ALTER TABLE plugin_docman_metadata_love ADD INDEX rank (rank);",
                     "ALTER TABLE plugin_docman_metadata_love ADD INDEX name (name (10));");
        echo "Updating indexes...";
        foreach($sqls as $sql) {
            if (!db_query($sql)) {
                $this->addUpgradeError(db_error());
            }
        }
        echo $this->getLineSeparator();
        echo "Analyzing and optimizing MySQL databases (this might take a few minutes)";
        echo $this->getLineSeparator();
        if ($this->getEnvironment() == WEB_ENVIRONMENT) {
            echo '<pre>';
        }
        passthru('mysqlcheck -Aao -u '. $GLOBALS['sys_dbuser'] .' --password='. $GLOBALS['sys_dbpasswd']);
        if ($this->getEnvironment() == WEB_ENVIRONMENT) {
            echo '</pre>';
        }
        echo $this->getLineSeparator();
    }

}
?>
