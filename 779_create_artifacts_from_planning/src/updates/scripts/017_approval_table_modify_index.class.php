<?php
//
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
//
// 

require_once('CodendiUpgrade.class.php');

/**
 * Restore right column default values for approval table.
 */
class Update_017 extends CodendiUpgrade {

    function _process() {
        echo $this->getLineSeparator();
        echo "Execution of script : ".get_class($this);
        echo $this->getLineSeparator();
        $table = 'plugin_docman_approval';
        
        if($this->indexNameExists($table, 'version_id')){
            $sql = ' ALTER TABLE  plugin_docman_approval DROP INDEX version_id';
            if(!$this->update($sql)) {
                $this->addUpgradeError("An error occured while dropping index 'version_id' in 'plugin_docman_approval': ".$this->da->isError());
            }
        }
        
        if($this->indexNameExists($table, 'item_wiki')){
            $sql = ' ALTER TABLE  plugin_docman_approval DROP INDEX item_wiki';
            if(!$this->update($sql)) {
                $this->addUpgradeError("An error occured while dropping index 'item_wiki' in 'plugin_docman_approval': ".$this->da->isError());
            }
        }
        
         if($this->indexNameExists($table, 'item_id')){
            $sql = ' ALTER TABLE  plugin_docman_approval DROP INDEX item_id';
            if(!$this->update($sql)) {
                $this->addUpgradeError("An error occured while dropping index 'item_id' in 'plugin_docman_approval': ".$this->da->isError());
            }
        }
        
        $sql = ' ALTER TABLE  plugin_docman_approval  ADD UNIQUE version_id (version_id)';
        if(!$this->update($sql)) {
            $this->addUpgradeError("An error occured while updating index 'version_id' in 'plugin_docman_approval': ".$this->da->isError());
        }
        
        $sql = ' ALTER TABLE  plugin_docman_approval  ADD UNIQUE item_id (item_id, wiki_version_id)';
        if(!$this->update($sql)) {
            $this->addUpgradeError("An error occured while updating 'item_id' in 'plugin_docman_approval': ".$this->da->isError());
        }
        echo $this->getLineSeparator();

     }
}

?>
