<?php

/**
 *
 */
class b201009280731_add_table_message_notif_delegation extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Add the table  message_notif_delegation to manage the message that should be displayed to requester.
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql = 'CREATE TABLE message_notif_delegation ('.
               ' group_id int(11) NOT NULL default 0,'.
               ' msg_to_requester text NOT NULL default "",'.
               ' PRIMARY KEY (group_id))';
        $this->db->createTable('message_notif_delegation', $sql);
        $message ="<In order to make administrator approve your request, .'
                    please provide him with some details: Site, Organisation, Manager, 
                    Function and Your purpose. The more precise are the information 
                    the quicker will be his answer.>";
        $sql = 'INSERT INTO message_notif_delegation (group_id, msg_to_requester) VALUES '.
                       ' (100 , "'.$message.'")';
        $this->db->dbh->query($sql);
    }

    public function postUp() {
        if (!$this->db->tableNameExists('message_notif_delegation')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('message_notif_delegation table is missing');
        }
    }

}

?>
