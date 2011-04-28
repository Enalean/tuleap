<?php

/**
 *
 */
class b201007291109_modify_pk_table_notification_delegation extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Modify the primary key to ensure the tuple unicity.
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql = 'ALTER TABLE groups_notif_delegation '.
               ' ADD PRIMARY KEY (group_id, ugroup_id)';
        $this->db->addPrimaryKey('groups_notif_delegation','(group_id, ugroup_id)', $sql);
    }

    public function postUp() {
        if (!$this->db->primaryKeyExists('groups_notif_delegation')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Primary key on groups_notif_delegation table is missing');
        }
    }

}
?>
