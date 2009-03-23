<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// $Id: /mirror/st/codex/integration/CodeX-3.4-ST_20080204/trunk/src/updates/scripts/002_docman_itembo_cleanup.class.php 11236 2007-08-22T12:24:47.496682Z vacelet  $


require_once('CodeXUpgrade.class.php');

/**
 * Restore right column default values for approval table.
 */
class Update_012 extends CodeXUpgrade {

    function _process() {
        echo $this->getLineSeparator();
        echo "Execution of script : ".get_class($this);
        echo $this->getLineSeparator();

        $sql = 'ALTER TABLE plugin_docman_approval CHANGE COLUMN version_id version_id INT(11) UNSIGNED UNSIGNED NULL DEFAULT NULL';
        if(!$this->update($sql)) {
            $this->addUpgradeError("An error occured while updating 'version_id' field in 'plugin_docman_approval': ".$this->da->isError());
        }

        $sql = 'ALTER TABLE plugin_docman_approval CHANGE COLUMN wiki_version_id wiki_version_id INT(11) UNSIGNED UNSIGNED NULL DEFAULT NULL';
        if(!$this->update($sql)) {
            $this->addUpgradeError("An error occured while updating 'wiki_version_id' field in 'plugin_docman_approval': ".$this->da->isError());
        }

        echo $this->getLineSeparator();
    }
}

?>
