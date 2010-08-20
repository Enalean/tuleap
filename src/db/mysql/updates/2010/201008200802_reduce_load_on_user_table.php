<?php

/**
 *
 */
class b201008200802_reduce_load_on_user_table extends ForgeUpgrade_Bucket {
    public function description() {
        return <<<EOT
Create a new dedicated table for user access on frequently updated fields to reduce load on user table.
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql = 'CREATE TABLE user_access (
                    user_id int(11) NOT NULL DEFAULT "0",
                    last_access_date int(11) NOT NULL DEFAULT 0,
                    prev_auth_success INT(11) NOT NULL DEFAULT 0,
                    last_auth_success INT(11) NOT NULL DEFAULT 0,
                    last_auth_failure INT(11) NOT NULL DEFAULT 0,
                    nb_auth_failure INT(11) NOT NULL DEFAULT 0,
                    PRIMARY KEY  (user_id)
                )';
        $this->db->createTable('user_access', $sql);

        $sql = 'INSERT INTO user_access
                           SELECT user_id, last_access_date, 
                                  prev_auth_success, last_auth_success, 
                                  last_auth_failure, nb_auth_failure 
                           FROM user';
        $this->db->copyTable('user', 'user_access', $sql);

        $sql = 'ALTER TABLE user DROP COLUMN last_access_date';
        $this->db->alterTable('user', 'last_access_date', 'drop_column', $sql);

        $sql = 'ALTER TABLE user DROP COLUMN prev_auth_success';
        $this->db->alterTable('user', 'prev_auth_success', 'drop_column', $sql);

        $sql = 'ALTER TABLE user DROP COLUMN last_auth_success';
        $this->db->alterTable('user', 'last_auth_success', 'drop_column', $sql);

        $sql = 'ALTER TABLE user DROP COLUMN last_auth_failure';
        $this->db->alterTable('user', 'last_auth_failure', 'drop_column', $sql);

        $sql = 'ALTER TABLE user DROP COLUMN  nb_auth_failure';
        $this->db->alterTable('user', 'nb_auth_failure', 'drop_column', $sql);
   }

    public function postUp() {
        if (!$this->db->tableNameExists('user_access')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('user_access table is missing');
        }
        if ($this->db->columnNameExists('user', 'last_access_date')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Column last_access_date is not deleted from user table');
        }
        if ($this->db->columnNameExists('user', 'prev_auth_success')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Column prev_auth_success is not deleted from user table');
        }
        if ($this->db->columnNameExists('user', 'last_auth_success')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Column last_auth_success is not deleted from user table');
        }
        if ($this->db->columnNameExists('user', 'last_auth_failure')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Column last_auth_failure is not deleted from user table');
        }
        if ($this->db->columnNameExists('user', 'nb_auth_failure')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Column nb_auth_failure is not deleted from user table');
        }
    }
}

?>