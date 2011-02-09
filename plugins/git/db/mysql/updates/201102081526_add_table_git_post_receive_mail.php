<?php

/**
 *
 */
class b201102081526_add_table_git_post_receive_mail extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Add git_post_receive_mail table to store emails.
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql = 'CREATE TABLE IF NOT EXISTS git_post_receive_mail ('.
                    ' id INT(10) UNSIGNED NOT NULL auto_increment, '.
                    ' recipient_mail varchar(255) NOT NULL,'.
                    ' repository_id INT(10) NOT NULL,'.
                    ' PRIMARY KEY(id),'.
                    ' KEY `repository_id` (`repository_id`)
                    );';
        $this->db->createTable('git_post_receive_mail', $sql);
    }

    public function postUp() {
        if (!$this->db->tableNameExists('git_post_receive_mail')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('git_post_receive_mail table is missing');
        }
    }

}

?>
