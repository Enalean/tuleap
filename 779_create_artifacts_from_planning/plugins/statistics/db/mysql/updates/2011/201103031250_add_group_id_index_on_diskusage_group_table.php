<?php

/**
 *
 */
class b201103031250_add_group_id_index_on_diskusage_group_table extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Add index on group_id and date on plugin_statistics_diskusage_group table in order to speed-up
Computation of statistics in project pages.
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $this->log->warn('Following operations might take a while, please be patient...');
        $sql = 'ALTER TABLE plugin_statistics_diskusage_group'.
               ' ADD INDEX idx_group_id_date (group_id, date)';
        $this->db->addIndex('plugin_statistics_diskusage_group', 'idx_group_id_date', $sql);
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
