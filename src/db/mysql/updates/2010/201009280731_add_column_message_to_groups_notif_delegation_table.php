<?php

/**
 *
 */
class b201009280731_add_column_message_to_groups_notif_delegation_table extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Modify the table  groups_notif_delegation to manage which group will receive membership requests notifications and the default message that will be displayed to requester.
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql = 'CREATE TABLE groups_notif_delegation_tmp ('.
               ' group_id int(11) NOT NULL default 0,'.
               ' ugroup_id varchar(16) NOT NULL,'.
               ' msg_to_requester text NOT NULL default "",'.
               ' PRIMARY KEY (group_id))';
        $this->db->createTable('groups_notif_delegation_tmp', $sql);
        $message ="<In order to make administrator approve your request, .'
                    please provide him with some details: Site, Organisation, Manager, 
                    Function and Your purpose. The more precise are the information 
                    the quicker will be his answer.>";
        //Copy data
        $stm = $this->db->dbh->prepare('SELECT distinct(group_id) FROM groups_notif_delegation');
        $stm->execute();
        $groups = $stm->fetchAll();
        foreach ($groups as $groupId) {
            $stm = $this->db->dbh->prepare('SELECT ugroup_id FROM groups_notif_delegation WHERE group_id='.$groupId['group_id']);
            $stm->execute();
            $arrayUgroups = $stm->fetchAll(PDO::FETCH_COLUMN, 0);
            $insertUgroups = implode(",", $arrayUgroups);
            $sql = 'INSERT INTO groups_notif_delegation_tmp (group_id, ugroup_id, msg_to_requester) VALUES '.
                       ' ('.$groupId['group_id'].' , "'.$insertUgroups. '" , "'.$message.'")';
            $this->db->dbh->query($sql);
        }
        
        //remove the old table groups_notif_delegation
        $sql = 'DROP TABLE groups_notif_delegation';
        $this->db->dbh->query($sql);
        
        //rename the new one from groups_notif_delegation_tmp to groups_notif_delegation
        $sql = 'RENAME TABLE groups_notif_delegation_tmp TO groups_notif_delegation';
        $this->db->dbh->query($sql);
        
    }

    public function postUp() {
        if (!$this->db->tableNameExists('groups_notif_delegation')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('groups_notif_delegation table is missing');
        }
    }

}

?>
