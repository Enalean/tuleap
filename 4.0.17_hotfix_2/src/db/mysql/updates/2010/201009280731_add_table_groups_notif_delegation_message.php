<?php

/**
 *
 */
class b201009280731_add_table_groups_notif_delegation_message extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Add the table  message_notif_delegation to manage the message that should be displayed to requester.
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql = 'CREATE TABLE groups_notif_delegation_message ('.
               ' group_id int(11) NOT NULL default 0,'.
               ' msg_to_requester text NOT NULL default "",'.
               ' PRIMARY KEY (group_id))';
        $this->db->createTable('groups_notif_delegation_message', $sql);
        $sql = 'INSERT INTO groups_notif_delegation_message (group_id, msg_to_requester) VALUES '.
                       ' (100 , "member_request_delegation_msg_to_requester")';
        $this->db->dbh->query($sql);
    }

    public function postUp() {
        if (!$this->db->tableNameExists('groups_notif_delegation_message')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('groups_notif_delegation_message table is missing');
        }
    }

}

?>
