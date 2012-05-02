<?php

require_once 'CodeXUpgrade.class.php';

class Update_014 extends CodeXUpgrade {

    function _process() {
        echo $this->getLineSeparator();
        echo "Execution of script : ".get_class($this);
        echo $this->getLineSeparator();

        // Add table plugin_docmanwatermark_item_excluded
        if (!$this->tableExists('plugin_docmanwatermark_item_excluded')) {
            $sql = 'CREATE TABLE plugin_docmanwatermark_item_excluded ('.
                   ' item_id INT(11) UNSIGNED NOT NULL,'.
                   ' PRIMARY KEY(item_id)'.
                   ');';
            $res = $this->update($sql);
            if(!$res) {
                $this->addUpgradeError("An error occured adding table plugin_docmanwatermark_item_excluded : ".$this->da->isError());
                return false;
            }
        }

        // Add table plugin_docmanwatermark_item_excluded_log
        if (!$this->tableExists('plugin_docmanwatermark_item_excluded_log')) {
            $sql = 'CREATE TABLE plugin_docmanwatermark_item_excluded_log ('.
                   ' item_id INT(11) UNSIGNED NOT NULL,'.
                   ' time INT(11) UNSIGNED NOT NULL DEFAULT 0,'.
                   ' who INT(11) UNSIGNED NOT NULL DEFAULT 0,'.
                   ' watermarked TINYINT(4) UNSIGNED NOT NULL DEFAULT 0,'.
                   ' INDEX idx_show_log(item_id, time)'.
                   ');';
            $res = $this->update($sql);
            if(!$res) {
                $this->addUpgradeError("An error occured adding table plugin_docmanwatermark_item_excluded_log : ".$this->da->isError());
                return false;
            }
        }

    }
}
?>