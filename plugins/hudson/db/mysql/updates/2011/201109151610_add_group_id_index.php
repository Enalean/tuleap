<?php

/**
 *
 */
class b201109151610_add_group_id_index extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Add index on group_id in plugin_hudson_job
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql = 'ALTER TABLE plugin_hudson_job'.
               ' ADD INDEX idx_group_id (group_id)';
        $this->db->addIndex('plugin_hudson_job', 'idx_group_id', $sql);
    }

    public function postUp() {
        // As of forgeupgrade 1.2 indexNameExists is buggy, so cannot rely on it for post upgrade check
        // Assume it's ok...

        /*if (!$this->db->indexNameExists('plugin_statistics_diskusage_group', 'idx_group_id_date')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Index "idx_group_id_date" is missing in "plugin_statistics_diskusage_group"');
            }*/
    }
}

?>
