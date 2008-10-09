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
        $user = UserManager::instance()->getCurrentUser();
		switch($request->get('action')) {
            case 'synchronize_all':
                if ($user->isSuperUser()) {
                    $this->action = 'synchronize_all';
                    $this->view = 'codex_im_admin';
                } else {
					$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
					$this->view = 'IM';
				}
            	break;
            case 'synchronize_muc_only':
                if ($user->isSuperUser()) {
            		$this->action = 'synchronize_muc_only';
                    $this->view = 'codex_im_admin';
                } else {
					$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
					$this->view = 'IM';
				}
            	break;
		    case 'synchronize_grp_only':
                if ($user->isSuperUser()) {
            		$this->action = 'synchronize_grp_only';
                    $this->view = 'codex_im_admin';
                } else {
					$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
					$this->view = 'IM';
				}
                break;
		    case 'synchronize_muc_and_grp':
                if ($user->isSuperUser()) {
            		$this->action = 'synchronize_muc_and_grp';
                    $this->view = 'codex_im_admin';
                } else {
					$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
					$this->view = 'IM';
				}
            	break;
            case 'get_presence':
                    $this->view = 'get_presence';
                    break;
		    case 'codex_im_admin':
                if ($user->isSuperUser()) {
					$this->view = 'codex_im_admin';
                } else {
					$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
					$this->view = 'IM';
				}
                break;
            default:
                if ($group_id) {
                    $project = project_get_object($group_id);
                    if ($project->usesService('IM')) {
                        $this->view = 'IM';
                    } else {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_im_admin','service_not_used'));
                    }
                }else{
                	$this->view = 'codex_im_admin';
                }
                break;
        }
    }
    function getPlugin() {
        return $this->plugin;
    }
}
?>