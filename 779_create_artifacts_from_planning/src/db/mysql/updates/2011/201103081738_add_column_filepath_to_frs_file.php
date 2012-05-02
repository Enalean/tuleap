<?php

/**
 *
 */
class b201103081738_add_column_filepath_to_frs_file extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Add file_path to the table frs_file to dissociate name of the file stored in the filesystem from the one displayed & used in the download.
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql = 'ALTER TABLE frs_file ADD filepath VARCHAR(255) AFTER filename';
        if ($this->db->tableNameExists('frs_file')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column filepath to table frs_file');
            }
        }

        $sql = 'ALTER TABLE frs_file_deleted ADD filepath VARCHAR(255) AFTER filename';
        if ($this->db->tableNameExists('frs_file_deleted')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column filepath to table frs_file_delete');
            }
        }
    }

    public function postUp() {
        if (!$this->db->columnNameExists('frs_file', 'filepath')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('filepath not created in frs_file');
        }

        if (!$this->db->columnNameExists('frs_file_deleted', 'filepath')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('filepath not created in frs_file_deleted');
        }
    }

}

?>