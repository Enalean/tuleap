<?php

/**
 *
 */
class b201102081526_add_table_plugin_git_post_receive_mail extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Add plugin_git_post_receive_mail table to store emails.
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql = 'CREATE TABLE IF NOT EXISTS plugin_git_post_receive_mail ('.
                    ' recipient_mail varchar(255) NOT NULL,'.
                    ' repository_id INT(10) NOT NULL,'.
                    ' KEY `repository_id` (`repository_id`)
                    );';
        $this->db->createTable('plugin_git_post_receive_mail', $sql);
    }

    public function postUp() {
        if (!$this->db->tableNameExists('plugin_git_post_receive_mail')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('plugin_git_post_receive_mail table is missing');
        }
    }

}

?>
