<?php

/**
 *
 */
class b201106080744_add_table_mass_mail extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Add mass mail table to manage stored mails to use in mass mail engine.
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql = "CREATE TABLE mass_mail (
                  mail_id int(11) NOT NULL auto_increment,
                  filename VARCHAR(255) NOT NULL,
                  status char(1) NOT NULL default 'N',
                  creation_date INT(11) UNSIGNED NULL,
                  use_date INT(11) UNSIGNED NULL,
                  PRIMARY KEY (mail_id)
                );";
        $this->db->createTable('mass_mail', $sql);
    }

    public function postUp() {
    if (!$this->db->tableNameExists('mass_mail')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('mass_mail table is missing');
        }
    }

}

?>