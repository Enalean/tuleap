<?php

/**
 *
 */
class b201010191436_add_table_frs_file_deleted extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Add the table frs_file_deleted to manage deleted files in order to facilitate their restore later
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql = "CREATE TABLE frs_file_deleted (
  file_id int(11) NOT NULL,
  filename text,
  release_id int(11) NOT NULL default '0',
  type_id int(11) NOT NULL default '0',
  processor_id int(11) NOT NULL default '0',
  release_time int(11) NOT NULL default '0',
  file_size bigint NOT NULL default '0',
  post_date int(11) NOT NULL default '0',
  status char(1) NOT NULL default 'A',
  delete_date INT(11) UNSIGNED NULL,
  purge_date INT(11) UNSIGNED NULL,
  PRIMARY KEY  (file_id),
  INDEX idx_delete_date (delete_date),
  INDEX idx_purge_date (purge_date)
);";
        $this->db->createTable('frs_file_deleted', $sql);
    }

    public function postUp() {
        if (!$this->db->tableNameExists('frs_file_deleted')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('frs_file_deleted table is missing');
        }
    }
    
}

?>
