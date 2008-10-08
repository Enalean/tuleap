<?php

require_once('common/mvc/Actions.class.php');
//require_once('common/include/HTTPRequest.class.php');
require_once(dirname(__FILE__)."/install/IMDataIntall.class.php");
require_once("IMDao.class.php");

class IMActions extends Actions{
	
	var $installer;
	/**
	 * <b>Constructor </b><br>
	 * @param object $controler the controler of the action
	 * @param object $view view of the action
	 */
    function IMAction(&$controler, $view=null) {
        $this->Actions($controler);
        //$this->installer=new IMDataIntall();
	}
	
	function &_get_installer_object () {
		if(isset($this->installer)&&$this->installer){
        	//class déjà instanciée
        	return $this->installer;
        }else {
        	$this->installer=new IMDataIntall();
        	return $this->installer;
        }
	}
	// {{{ Actions
 	function synchronize_all() {
		
		//shared group installation 
		require_once('common/dao/CodexDataAccess.class.php');
		$dao=new IMDao(IMDataAccess::instance());
		$dao->synchronize_all_project();
	}
	
	/**
	 * synchronize_muc_only
	 */
	 
	 function synchronize_muc_only () {
		$this->_get_installer_object ()->synchronize_muc_only();
	}
	
	/**
	 * synchronize_grp_only
	 */
	function synchronize_grp () {
		$this->_get_installer_object ()->synchronize_grp_only();
	}
	
	/**
	 * synchronize_muc_and_grp_together
	 */
	 function synchronize_muc_and_grp () {
		$this->_get_installer_object ()->synchronize_muc_and_grp_together();
	}
    // }}}
}
?>