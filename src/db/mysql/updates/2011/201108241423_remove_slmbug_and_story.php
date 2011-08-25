<?php

/**
 *
 */
class b201108241423_remove_slmbug_and_story extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Remove slmbug and story references from reference table.
In order to avoid issue at project creation (tracker references should be created at tracker level, not during service creation.
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql = 'DELETE FROM reference WHERE id IN (20, 21)';
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while dropping references slmbug and story: '.implode(', ', $this->db->dbh->errorInfo()));
        }
    }
}

?>
