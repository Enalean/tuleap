<?php
require_once('common/mvc/Controler.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('IMViews.class.php');
require_once('IMActions.class.php');

class IM extends Controler {
    
    var $plugin;
    
    function __construct(&$plugin) {
        $this->plugin =& $plugin;
    }

	function getThemePath() {
        return $this->plugin->getThemePath();
    }
    function getIconPath() {
    	return $this->plugin->get_icon_path();
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
                    $this->view = 'codendi_im_admin';
                } else {
					$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
				}
            	break;
            case 'synchronize_muc_only':
                if ($user->isSuperUser()) {
            		$this->action = 'synchronize_muc_only';
                    $this->view = 'codendi_im_admin';
                } else {
					$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
				}
            	break;
		    case 'synchronize_grp_only':
                if ($user->isSuperUser()) {
            		$this->action = 'synchronize_grp_only';
                    $this->view = 'codendi_im_admin';
                } else {
					$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
				}
                break;
		    case 'synchronize_muc_and_grp':
                if ($user->isSuperUser()) {
            		$this->action = 'synchronize_muc_and_grp';
                    $this->view = 'codendi_im_admin';
                } else {
					$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
				}
            	break;
            case 'get_presence':
                    $this->view = 'get_presence';
                    break;
		    case 'codendi_im_admin':
                if ($user->isSuperUser()) {
					$this->view = 'codendi_im_admin';
                } else {
					$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
				}
                break;
            case 'viewchatlog':
                if ($user->isMember($group_id)) {
                    $chat_log = $request->get('chat_log');
                    if (strlen($chat_log) != 8 || ! is_numeric($chat_log)) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_im','reference_format_error', array($chat_log)));
                    }
                    $this->view = 'ref_muc_logs';
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
                }
                break;
            default:
                if ($user->isMember($group_id)) {
                	$any = $GLOBALS['Language']->getText('global', 'any');
                	$start_date = $request->get('log_start_date');
				    if ($start_date != $any && $start_date != '') {
				    	$r = new Rule_Date();
				    	if (! $r->isValid($start_date)) {
				    		$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_im','date_format_error', array($start_date)));
				    	}
				    } // else date is '' or any
				    
				    $end_date = $request->get('log_end_date');
			    	if ($end_date != $any && $end_date != '') {
				    	$r = new Rule_Date();
				    	if (! $r->isValid($end_date)) {
				    		$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_im','date_format_error', array($end_date)));
				    	}
				    } // else date is '' or any
				    
				    if ($request->get('type') == 'export') {
				        $this->view = 'export_muc_logs';
				    } else {
				        $this->view = 'muc_logs';
				    }
                } else {
					$GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
				}
                break;
        }
    }
    function getPlugin() {
        return $this->plugin;
    }
}
?>