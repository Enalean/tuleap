<?php

/**
 *
 */
class b201110171036_add_docman_approval_user_index extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Add index on reviewer_id and table_id in docman_approval_user
EOT;
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }
    
    public function up() {
        //Simulate indexNameExists
        $sql = 'SHOW INDEX FROM plugin_docman_approval_user WHERE Key_name LIKE "idx_reviewer"';
        
        $sth = $this->db->dbh->prepare($sql);
        $sth->execute();
        $res = $sth->fetchAll();
        
        //If index already exists, delete it.
        if (!empty($res)) {
            $sql = 'DROP INDEX idx_reviewer ON plugin_docman_approval_user';
            $res = $this->db->dbh->exec($sql);
        }
        
        $sql = 'ALTER TABLE plugin_docman_approval_user'.
               ' ADD INDEX idx_reviewer (reviewer_id, table_id)';
        $this->db->addIndex('plugin_docman_approval_user', 'idx_reviewer', $sql);
      
    }
    
    public function postUp() {
        // As of forgeupgrade 1.2 indexNameExists is buggy, so cannot rely on it for post upgrade check
        // Assume it's ok...

        /*if (!$this->db->indexNameExists('plugin_docman_approval_user', 'idx_reviewer')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Index "idx_reviewer" is missing in "plugin_docman_approval_user"');
            }*/
    }
}

?>
