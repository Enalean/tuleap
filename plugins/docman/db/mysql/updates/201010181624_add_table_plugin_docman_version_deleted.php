<?php

/**
 *
 */
class b201010181624_add_table_plugin_docman_version_deleted extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Add the table plugin_docman_version_deleted to manage deleted version in order to facilitate their restore later
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql = 'CREATE TABLE plugin_docman_version_deleted ('.
                    ' id INT(11) UNSIGNED NOT NULL, '.
                    ' item_id INT(11) UNSIGNED NULL,'.
                    ' number INT(11) UNSIGNED NULL,'.
                    ' user_id INT(11) UNSIGNED NULL,'.
                    ' label TEXT NULL,'.
                    ' changelog TEXT NULL,'.
                    ' create_date INT(11) UNSIGNED NULL,'.
                    ' delete_date INT(11) UNSIGNED NULL,'.
                    ' purge_date INT(11) UNSIGNED NULL,'.
                    ' filename TEXT NULL,'.
                    ' filesize INT(11) UNSIGNED NULL,'.
                    ' filetype TEXT NULL,'.
                    ' path TEXT NULL,'.
                    ' PRIMARY KEY(id), '.
                    ' KEY item_id (item_id))';
        $this->db->createTable('plugin_docman_version_deleted', $sql);
    }

    public function postUp() {
        if (!$this->db->tableNameExists('plugin_docman_version_deleted')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('plugin_docman_version_deleted table is missing');
        }
    }

}

?>
