<?php
require_once 'CodendiUpgrade.class.php';

/**
 * Add table to keep locked items.
 */
class Update_016 extends CodendiUpgrade {

    function _process() {
        echo "Execution of script : ".get_class($this);
        echo $this->getLineSeparator();
        
        if (!$this->tableExists('plugin_docman_item_lock')) {
            $sql = 'CREATE TABLE plugin_docman_item_lock ('.
                   ' item_id INT(11) UNSIGNED NOT NULL DEFAULT 0,'.
                   ' user_id INT(11) NOT NULL DEFAULT 0,'.
                   ' lock_date INT(11) UNSIGNED NOT NULL DEFAULT 0,'.
                   ' PRIMARY KEY (item_id)'.
                   ')';
            $res = $this->update($sql);
            if (!$res) {
                $this->addUpgradeError('An error occured while adding new table "plugin_docman_item_lock"'.$this->da->isError());
            }
        }
    }
}
?>