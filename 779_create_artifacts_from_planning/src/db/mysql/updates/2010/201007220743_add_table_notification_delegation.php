<?php

/**
 *
 */
class b201007220743_add_table_notification_delegation extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Add table to manage which group will receive membership requests notifications.
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql = 'CREATE TABLE groups_notif_delegation ('.
               ' group_id int(11) NOT NULL default 0,'.
               ' ugroup_id int(11) NOT NULL,'.
               ' KEY (group_id, ugroup_id))';
        $this->db->createTable('groups_notif_delegation', $sql);
    }

    public function postUp() {
        if (!$this->db->tableNameExists('groups_notif_delegation')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('groups_notif_delegation table is missing');
        }
    }

}

?>
