<?php
/*
 * Classe CloudStorageAction
 */

require_once('common/include/HTTPRequest.class.php');
require_once('common/mvc/Actions.class.php');

require_once("CloudStorageDao.class.php");

class CloudStorageActions extends Actions{
	
	/**
	 * <b>Constructor </b><br>
	 * @param object $controler the controler of the action
	 * @param object $view view of the action
	 */
    function CloudStorageAction(&$controler, $view=null) {
        $this->Actions($controler);
	}
	
	function test()
	{
		$request =& HTTPRequest::instance();
	}
	
	function update_default_cloudstorage_id()
	{
		$request =& HTTPRequest::instance();
		$dropbox = $request->get('default_dropbox_id');
		$drive = $request->get('default_drive_id');
        try {
            $dao = new CloudStorageDao(CodendiDataAccess::instance());
		    $dao->update_default_cloudstorage_id($dropbox, $drive);
            $GLOBALS['Response']->addFeedback('info', $group_name.' '.$GLOBALS['Language']->getText('plugin_cloudstorage','update_default_cloudstorage_id_msg_info'));
        } catch (Exception $e) {
			$GLOBALS['Response']->addFeedback('error', $group_name.' '.$GLOBALS['Language']->getText('plugin_cloudstorage','update_default_cloudstorage_id_msg_error').$e->getMessage());
		}		
	}
}
?>
