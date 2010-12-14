<?php

/**
 *
 */
class b201012140821_improve_frs_file extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Add fields to the table frs_file for verification of upload/download integrity and to store releaser.
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql = 'ALTER TABLE frs_file ADD computed_hash VARCHAR(32)';
        if ($this->db->tableNameExists('frs_file')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column computed_hash to table frs_file');
            }
        }

        $sql = 'ALTER TABLE frs_file_deleted ADD computed_hash VARCHAR(32) AFTER status';
        if ($this->db->tableNameExists('frs_file')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column computed_hash to table frs_file');
            }
        }

        $sql = 'ALTER TABLE frs_file ADD reference_hash VARCHAR(32)';
        if ($this->db->tableNameExists('frs_file')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column reference_hash to table frs_file');
            }
        }

        $sql = 'ALTER TABLE frs_file_deleted ADD reference_hash VARCHAR(32) AFTER computed_hash';
        if ($this->db->tableNameExists('frs_file')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column reference_hash to table frs_file');
            }
        }

        $sql = 'ALTER TABLE frs_file ADD user_id INT(11)';
        if ($this->db->tableNameExists('frs_file')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column user_id to table frs_file');
            }
        }

        $sql = 'ALTER TABLE frs_file_deleted ADD user_id INT(11) AFTER reference_hash';
        if ($this->db->tableNameExists('frs_file')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column user_id to table frs_file');
            }
        }
    }

    public function postUp() {
        if (!$this->db->columnNameExists('frs_file', 'computed_hash')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('computed_hash not created');
        }

        if (!$this->db->columnNameExists('frs_file_deleted', 'computed_hash')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('computed_hash not created');
        }

        if (!$this->db->columnNameExists('frs_file', 'reference_hash')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('reference_hash not created');
        }

        if (!$this->db->columnNameExists('frs_file_deleted', 'reference_hash')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('reference_hash not created');
        }

        if (!$this->db->columnNameExists('frs_file', 'user_id')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('user_id not created');
        }

        if (!$this->db->columnNameExists('frs_file_deleted', 'user_id')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('user_id not created');
        }
    }

}

?>