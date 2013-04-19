<?php
/*
 * Classe CloudStorageAction
 */

require_once('common/include/HTTPRequest.class.php');
require_once('common/mvc/Actions.class.php');

class CloudStorageActions extends Actions{
	
	/**
	 * <b>Constructor </b><br>
	 * @param object $controler the controler of the action
	 * @param object $view view of the action
	 */
    function CloudStorageActions(&$controler, $view=null) {
        $this->Actions($controler);
	}
	
	function test()
	{
		$request =& HTTPRequest::instance();
	}
}
?>
