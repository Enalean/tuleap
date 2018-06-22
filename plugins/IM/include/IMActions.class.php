<?php

require_once('common/include/HTTPRequest.class.php');
require_once('common/mvc/Actions.class.php');
require_once("IMDataAccess.class.php");
require_once("IMDao.class.php");

class IMActions extends Actions{
	
	/**
	 * <b>Constructor </b><br>
	 * @param object $controler the controler of the action
	 * @param object $view view of the action
	 */
    function IMAction(&$controler, $view=null) {
        parent::__construct($controler);
	}
	
	// {{{ Actions
 	function synchronize_all() {
		//shared group installation 
		$dao=new IMDao(IMDataAccess::instance($this->getControler()));
		$dao->synchronize_all_project();
	}
	
	/**
	 * synchronize_muc_only
	 */
	 
	function synchronize_muc_only() {
        $request =& HTTPRequest::instance();
		$unix_group_name = $request->get('unix_group_name');
        $group_id = $request->get('group_id');
		$group_Owner_name=$request->get('group_Owner_name');
		$group_name = $request->get('group_name');
		if ( ! (isset($group_name) && $group_name != null) ) {
			$group_name = $unix_group_name;
		}
		$group_description = $request->get('group_description');
		if( ! (isset($group_description) && $group_description != null) ) {
			$group_description = 'No description';
		}
        
        try {
            $dao = new IMDao(IMDataAccess::instance($this->getControler()));
		    $dao->synchronize_muc_only($unix_group_name, $group_name, $group_description, $group_Owner_name, $group_id);
            $GLOBALS['Response']->addFeedback('info', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_muc_msg'));
        } catch (Exception $e) {
			$GLOBALS['Response']->addFeedback('error', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_muc_error').$e->getMessage());
		}
	}
	
	/**
	 * synchronize_grp_only
	 */
	function synchronize_grp_only() {
        $request =& HTTPRequest::instance();
		$unix_group_name = $request->get('unix_group_name');
		$group_name = $request->get('group_name');
		if ( ! (isset($group_name) && $group_name != null) ) {
			$group_name = $unix_group_name;
		}
        
        try{
            $dao = new IMDao(IMDataAccess::instance($this->getControler()));
            $dao->synchronize_grp_only($unix_group_name, $group_name);
            $GLOBALS['Response']->addFeedback('info', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_grp_msg'));
        } catch (Exception $e) {
			$GLOBALS['Response']->addFeedback('error', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_grp_error').$e->getMessage());
		}
	}
	
	/**
	 * synchronize_muc_and_grp_together
	 */
	function synchronize_muc_and_grp() {
        $request =& HTTPRequest::instance();
		$unix_group_name = $request->get('unix_group_name');
        $group_id = $request->get('group_id');
        $group_Owner_name = $request->get('group_Owner_name');
		$group_name = $request->get('group_name');
		if( ! (isset($group_name) && $group_name != null) ) {
			$group_name = $unix_group_name;
		}
		$group_description = $request->get('group_description');
		if( ! (isset($group_description) && $group_description != null) ) {
			$group_description = 'No description';
		}
		
        $dao = new IMDao(IMDataAccess::instance($this->getControler()));
        try {
            $dao->synchronize_muc_only($unix_group_name, $group_name, $group_description, $group_Owner_name, $group_id);
            $GLOBALS['Response']->addFeedback('info', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_muc_msg'));
        } catch (Exception $e) {
			$GLOBALS['Response']->addFeedback('error', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_muc_error').$e->getMessage());
		}
        try{
            $dao->synchronize_grp_only($unix_group_name, $group_name);
            $GLOBALS['Response']->addFeedback('info', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_grp_msg'));
        } catch (Exception $e) {
			$GLOBALS['Response']->addFeedback('error', $group_name.' '.$GLOBALS['Language']->getText('plugin_im_admin','synchronise_grp_error').$e->getMessage());
		}
        
	}
    // }}}
}
?>