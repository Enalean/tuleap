<?php

require_once('common/dao/include/DataAccessObject.class.php');

class CloudStorageDao extends DataAccessObject {

    var $codendi_db_name;
    
    /**
    * Constructs the CloudStorageDao
    * @param $da instance of the DataAccess class
    */
    function __construct($da) {
        parent::__construct($da);
        $this->codendi_db_name = $GLOBALS['sys_dbname'];
    }
    
	function update_default_cloudstorage_id($dropbox, $drive) {	
		$sql = "
			UPDATE plugin_cloudstorage
			SET 
				default_dropbox_id = ".$this->da->quoteSmart($dropbox).",
				default_drive_id = ".$this->da->quoteSmart($drive)."
			WHERE
				id = 0
		";
		$updated = $this->update($sql);
	}
	
	function select_default_cloudstorage_id($serviceName) {
		$sql = "
			SELECT default_".$serviceName."_id AS csid
			FROM plugin_cloudstorage
			WHERE id = 0
		";
		$cs_id = $this->retrieve($sql);
		$row = $cs_id->getRow();
		return $row['csid'];		
	}
}
?>
