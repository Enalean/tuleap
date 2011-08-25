<?php

class b201108251206_add_index_on_user_history_project extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Add index on mod_by column for performance issue.
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql = 'CREATE INDEX idx_group_history_user_id'.
               ' ON group_history (mod_by)' ;
        $this->db->addIndex('group_history', 'idx_group_history_user_id', $sql);
    }

    public function postUp() {
        if (!$this->db->indexNameExists('group_history', 'idx_group_history_user_id')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('idx_group_history_user_id index is missing');
        }
    }
}
?>