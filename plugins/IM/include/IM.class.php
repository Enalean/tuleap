<?php
require_once('common/mvc/Controler.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('IMViews.class.php');
require_once('IMActions.class.php');

class IM extends Controler {
    
    var $plugin;
    
    function IM(&$plugin) {
        $this->plugin =& $plugin;
    }

    function getProperty($name) {
        $info =& $this->plugin->getPluginInfo();
        return $info->getPropertyValueForName($name);
	}
	function request() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $project = project_get_object($group_id);
		switch($request->get('action')) {
            case 'synchronize_all':
                    //echo $request->get('action') ;
                    $this->action = 'synchronize_all';
                    $this->view = 'codex_im_admin';
            		break;
            case 'synchronize_muc_only':
            		$this->action = 'synchronize_muc_only';
                    $this->view = 'codex_im_admin';
            		break;
		     case 'synchronize_grp_only'://synchronize_grp
            		$this->action = 'synchronize_grp';
                    $this->view = 'codex_im_admin';
            		break;
		     case 'synchronize_muc_and_grp'://
            		$this->action = 'synchronize_muc_and_grp';
                    $this->view = 'codex_im_admin';
            		break;
		     default:
					$this->view = 'codex_im_admin';
		    	 break;
         }
    }
    function getPlugin() {
        return $this->plugin;
    }
}
?>