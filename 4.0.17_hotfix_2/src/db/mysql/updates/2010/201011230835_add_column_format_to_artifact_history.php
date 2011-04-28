<?php

/**
 *
 */
class b201011230835_add_column_format_to_artifact_history extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Add the column format to artifact_history to distinguish text comments from html ones or other potential formats.
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        // The default format is text corresponding to 0
        $sql = 'ALTER TABLE artifact_history '.
               ' ADD format tinyint NOT NULL default 0';
        $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding the column format to the table artifact_history');
            }
    }

    public function postUp() {
        if (!$this->db->columnNameExists('artifact_history', 'format')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Column format in table artifact_history is missing');
        }
    }

}
?>