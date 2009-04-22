<?php
//
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
// $Id: /mirror/st/codex/integration/CodeX-3.4-ST_20080204/trunk/src/updates/scripts/002_docman_itembo_cleanup.class.php 11236 2007-08-22T12:24:47.496682Z vacelet  $


require_once('CodeXUpgrade.class.php');

class Update_007 extends CodeXUpgrade {

    function _process() {
        echo $this->getLineSeparator();
        echo "Execution of script : ".get_class($this);
        echo $this->getLineSeparator();

        //
        // First add new primary key for `approval`
        //
        if(!$this->fieldExists('plugin_docman_approval', 'table_id')) {
            echo "Add new field table_id to plugin_docman_approval";
            echo $this->getLineSeparator();

            $sql = 'ALTER TABLE plugin_docman_approval ADD COLUMN table_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY(table_id)';
            $res = $this->update($sql);
            if(!$res) {
                $this->addUpgradeError("An error occured while adding 'table_id' field in 'plugin_docman_approval': ".$this->da->isError());
            }
        }

        //
        // Approval `user` is now linked to `approval` with table id
        //
        if(!$this->fieldExists('plugin_docman_approval_user', 'table_id')) {
            echo "Add new field table_id to plugin_docman_approval_user";
            echo $this->getLineSeparator();

            $sql = 'ALTER TABLE plugin_docman_approval_user ADD COLUMN table_id INT(11) UNSIGNED NOT NULL FIRST';
            $res = $this->update($sql);
            if(!$res) {
                $this->addUpgradeError("An error occured while adding 'table_id' field in 'plugin_docman_approval_user': ".$this->da->isError());
            }

            $sql = 'UPDATE plugin_docman_approval_user u, plugin_docman_approval t SET u.table_id = t.table_id WHERE u.item_id = t.item_id';
            $res = $this->update($sql);
            if(!$res) {
                $this->addUpgradeError("An error occured while updating 'table_id' field in 'plugin_docman_approval_user': ".$this->da->isError());
            }

            $sql = 'ALTER TABLE plugin_docman_approval_user DROP COLUMN item_id, DROP PRIMARY KEY, ADD PRIMARY KEY(table_id, reviewer_id)';
            $res = $this->update($sql);
            if(!$res) {
                $this->addUpgradeError("An error occured while removing 'item_id' field in 'plugin_docman_approval_user': ".$this->da->isError());
            }
        }

        //
        // Add new fields to link with different item type
        //
        echo "Add new fields to plugin_docman_approval to manage multiple table type";
        echo $this->getLineSeparator();

        if(!$this->fieldExists('plugin_docman_approval', 'version_id')) {
            $sql = 'ALTER TABLE plugin_docman_approval ADD COLUMN version_id INT(11) UNSIGNED NULL DEFAULT NULL AFTER item_id';
            $res = $this->update($sql);
            if(!$res) {
                $this->addUpgradeError("An error occured while adding 'version_id' field in 'plugin_docman_approval': ".$this->da->isError());
            }
        }

        $sql = 'ALTER TABLE plugin_docman_approval CHANGE COLUMN item_id item_id INT(11) UNSIGNED NULL DEFAULT NULL';
        $res = $this->update($sql);
        if(!$res) {
            $this->addUpgradeError("An error occured while updating 'item_id' field in 'plugin_docman_approval': ".$this->da->isError());
        }

        if(!$this->fieldExists('plugin_docman_approval', 'wiki_version_id')) {
            $sql = 'ALTER TABLE plugin_docman_approval ADD COLUMN wiki_version_id INT(11) UNSIGNED NULL DEFAULT NULL AFTER version_id';
            $res = $this->update($sql);
            if(!$res) {
                $this->addUpgradeError("An error occured while adding 'wiki_version_id' field in 'plugin_docman_approval': ".$this->da->isError());
            }
        }
        if(!$this->fieldExists('plugin_docman_approval', 'auto_status')) {
            $sql = 'ALTER TABLE plugin_docman_approval ADD COLUMN auto_status TINYINT(4) DEFAULT 0 NOT NULL AFTER notification';
            $res = $this->update($sql);
            if(!$res) {
                $this->addUpgradeError("An error occured while adding 'auto_status' field in 'plugin_docman_approval': ".$this->da->isError());
            }
        }

        //
        // Update index
        //
        echo "Update plugin_docman_approval index";
        echo $this->getLineSeparator();

        $sql = 'ALTER TABLE plugin_docman_approval DROP INDEX item_id';
        $res = $this->update($sql);
        if(!$res) {
            $this->addUpgradeError("An error occured while removing 'item_id' index from 'plugin_docman_approval': ".$this->da->isError());
        }

        $sql = 'ALTER TABLE plugin_docman_approval DROP INDEX item_id_2';
        $res = $this->update($sql);
        if(!$res) {
            $this->addUpgradeError("An error occured while removing 'item_id_2' index from 'plugin_docman_approval': ".$this->da->isError());
        }

        $sql = 'ALTER TABLE plugin_docman_approval ADD UNIQUE(version_id)';
        $res = $this->update($sql);
        if(!$res) {
            $this->addUpgradeError("An error occured while adding index on 'version_id' on 'plugin_docman_approval': ".$this->da->isError());
        }

        $sql = 'ALTER TABLE plugin_docman_approval ADD UNIQUE(item_id, wiki_version_id)';
        $res = $this->update($sql);
        if(!$res) {
            $this->addUpgradeError("An error occured while adding index on '(item_id, wiki_version_id)' on 'plugin_docman_approval': ".$this->da->isError());
        }

        //
        // Drop approval table for empty documents:
        //
        echo "Remove approval table for empty documents";
        echo $this->getLineSeparator();

        $sql = 'DELETE FROM plugin_docman_approval_user WHERE table_id IN (SELECT table_id FROM plugin_docman_approval a JOIN plugin_docman_item i USING(item_id) WHERE item_type = 6)';
        $res = $this->update($sql);
        if(!$res) {
            $this->addUpgradeError("An error occured while removing users that belong to an approval table linked to an empty document: ".$this->da->isError());
        }

        $sql = 'DELETE FROM plugin_docman_approval USING plugin_docman_approval JOIN plugin_docman_item USING (item_id) WHERE item_type = 6';
        $res = $this->update($sql);
        if(!$res) {
            $this->addUpgradeError("An error occured while removing the approval table linked to an empty document: ".$this->da->isError());
        }

        //
        // Update table for files & embedded files
        //
        echo "Link file and embedded files approval table to their most up to date version";
        echo $this->getLineSeparator();
        $nb = 0;

        $sql = 'SELECT a.table_id, i.item_id'.
            ' FROM plugin_docman_approval a'.
            '   JOIN plugin_docman_item i '.
            '     ON (i.item_id = a.item_id)'.
            ' WHERE i.item_type IN (2, 4)';
        $dar = $this->retrieve($sql);
        if($dar && !$dar->isError()) {
            while($row = $dar->getRow()) {
                // Get the latest version for the item
                $sql = 'SELECT id'.
                    ' FROM plugin_docman_version'.
                    ' WHERE item_id = '.$row['item_id'].
                    ' ORDER BY number DESC'.
                    ' LIMIT 1';
                $darVer = $this->retrieve($sql);
                if($darVer && !$darVer->isError()) {
                    $rowVer = $darVer->getRow();
                    $sql = 'UPDATE plugin_docman_approval'.
                        ' SET item_id = NULL,'.
                        '   version_id = '.$rowVer['id'].
                        ' WHERE table_id = '.$row['table_id'];
                    $this->update($sql);
                    $nb += $this->da->affectedRows();

                }
            }
        }
        echo "... $nb entries affected";
        echo $this->getLineSeparator();

        //
        //  Update table for wiki pages
        //
        echo "Link wiki pages approval table to their most up to date version";
        echo $this->getLineSeparator();
        $nb = 0;

        $sql = 'SELECT a.table_id, i.item_id'.
            ' FROM plugin_docman_approval a'.
            '   JOIN plugin_docman_item i '.
            '     ON (i.item_id = a.item_id)'.
            ' WHERE i.item_type = 5';
        $dar = $this->retrieve($sql);
        if($dar && !$dar->isError()) {
            while($row = $dar->getRow()) {
                // Get the latest version for the item
                $sql = 'SELECT wv.version'.
                    ' FROM wiki_version wv'.
                    '   JOIN wiki_page wp'.
                    '     ON (wp.id = wv.id)'.
                    '   JOIN plugin_docman_item i'.
                    '     ON (i.wiki_page = wp.pagename'.
                    '         AND i.group_id = wp.group_id)'.
                    ' WHERE item_id = '.$row['item_id'].
                    ' ORDER BY wv.version DESC'.
                    ' LIMIT 1';
                $darVer = $this->retrieve($sql);
                if($darVer && !$darVer->isError() && $darVer->rowCount() == 1) {
                    $rowVer = $darVer->getRow();
                    $wikiVer = $rowVer['version'];
                } else {
                    $wikiVer = 0;
                }
                $sql = 'UPDATE plugin_docman_approval'.
                    ' SET wiki_version_id = '.$wikiVer.
                    ' WHERE table_id = '.$row['table_id'];
                $this->update($sql);
                $nb += $this->da->affectedRows();
            }
        }
        echo "... $nb entries affected";
        echo $this->getLineSeparator();
    }
}

?>
