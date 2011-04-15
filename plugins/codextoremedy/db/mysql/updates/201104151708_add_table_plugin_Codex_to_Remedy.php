<?php

/**
 *
 */
class b201104151708_add_table_plugin_Codex_to_Remedy extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Add the table plugin_Codex_to_Remedy to manage the automatic ticket insertion in RIF table
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql = 'CREATE TABLE plugin_Codex_to_Remedy ('.
                    ' id INT(11) UNSIGNED NOT NULL, '.
                    ' user_id INT(11) UNSIGNED NULL,'.
                    ' summary TEXT NOT NULL,'.
                    ' create_date INT(11) UNSIGNED NULL,'.
                    ' description TEXT NULL,'.
                    ' type INT,'.
                    ' severity INT,'.
                    ' PRIMARY KEY(id))';
        $this->db->createTable('plugin_Codex_to_Remedy', $sql);
    }

    public function postUp() {
        if (!$this->db->tableNameExists('plugin_Codex_to_Remedy')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('plugin_Codex_to_Remedy table is missing');
        }
    }

}

?>
