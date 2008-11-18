<?php
require_once('pre.php');
require_once('common/mvc/Views.class.php');
require_once('common/include/HTTPRequest.class.php');

require_once('IMDao.class.php');
require_once('IMDataAccess.class.php');
require_once('JabbexFactory.class.php');

require_once('IMMucConversationLogManager.class.php');

class IMViews extends Views {
	
	protected $iconsPath;
	
    function IMViews(&$controler, $view=null) {
        $this->View($controler, $view);
        $this->iconsPath = $controler->getIconPath();
    }
    
	function getIconsPath() {
        return $this->iconsPath;
    }
    
    function display($view='') {
        if ($view == 'get_presence') {
            $this->$view();
        } else {
            parent::display($view);
        }
    }
    
    function header() {
        $request = HTTPRequest::instance();
        $group_id = $request->get('group_id');

        if ($this->getControler()->view == 'codex_im_admin') {
            $GLOBALS['HTML']->header(array('title'=>$this->_getTitle(),'selected_top_tab' => 'admin'));
        } else {
            $GLOBALS['HTML']->header(array('title'=>$this->_getTitle(),'group' => $group_id,'toptab' => 'IM'));
        	if (user_ismember($request->get('group_id'))) {
            	echo '<b><a href="/plugins/IM/?group_id='. $request->get('group_id') .'&amp;action=muc_logs">'. $GLOBALS['Language']->getText('plugin_im', 'toolbar_muc_logs') .'</a> | </b>';
        	}
            echo $this->_getHelp();
        }
    }
    
    function _getHelp($section = '') {
        if (trim($section) !== '' && $section{0} !== '#') {
            $section = '#'.$section;
        }
        return '<b><a href="javascript:help_window(\''.get_server_url().'/plugins/IM/documentation/'.UserManager::instance()->getCurrentUser()->getLocale().'/'.$section.'\');">'.$GLOBALS['Language']->getText('global', 'help').'</a></b>';
    }
    
    function _getTitle() {
        return $GLOBALS['Language']->getText('plugin_im','title');
    }
    
    function footer() {
        $GLOBALS['HTML']->footer(array());
    }
    
    // {{{ Views
    function codex_im_admin() {
		echo '<h2><b>'.$GLOBALS['Language']->getText('plugin_im_admin','im_admin_title').'</b></h2>';
		echo '<h3><b>'.$GLOBALS['Language']->getText('plugin_im_admin','im_admin_warning').'</b></h3>';
		$this->_admin_synchronize_muc_and_grp();
	}
    
    function get_presence() {
        $request = HTTPRequest::instance();
        if ($request->exist('jid')) {
            $presence = $this->getControler()->getPlugin()->getPresence($request->get('jid'));
            echo '({"icon":"'. $presence['icon'] .'","status":"'.$presence['status'].'"})';
        } else if (is_array($request->get('jids'))) {
            $presences = array();
            foreach($request->get('jids') as $jid) {
                $presence = $this->getControler()->getPlugin()->getPresence($jid);
                $presences[] = '{"id":"'.md5($jid).'","icon":"'. $presence['icon'] .'","status":"'.$presence['status'].'"}';
            }
            echo '(['. implode(',', $presences) .'])';
        }
    }
    // }}}
    
    /**
	 * Display chat room of project $group_id
	 */
    function chat_room() {
        $request = HTTPRequest::instance();

		$group_id = $request->get('group_id'); 
    	$project = project_get_object($group_id);
    	$um = UserManager::instance();
	    $user = $um->getCurrentUser();
    	
        $plugin = $this->getControler()->getPlugin();
        $plugin_path = $plugin->getPluginPath();
        $im_object = JabbexFactory::getJabbexInstance();
    	
        $jabberConf = $im_object->get_server_conf();
    	
    	$sessionId = session_hash();
		$server_dns = $jabberConf['server_dns'];
		$conference_service = $jabberConf['conference_service'];
		
    	$room_name = $project->getUnixName();
    	$user_unix_name = $user->getName();
        echo '<div id="chatroom">';
        echo '<h2 id="mucroom_title">'.$GLOBALS['Language']->getText('plugin_im', 'chatroom_title') .'</h2>';
        
        echo '<p id="mucroom_summary">'.$GLOBALS['Language']->getText('plugin_im', 'chatroom_summary') .'</p>';
        
        $user_projects = $user->getProjects();
        if (in_array($group_id, $user_projects)) {
        	
        	echo '<div id="mucroom_timer">';
        	echo $GLOBALS['Language']->getText('plugin_im','wait_loading');
        	echo $GLOBALS['HTML']->getImage('ic/spinner.gif');
        	echo '</div>';
        
			$url = $plugin_path . '/webmuc/muckl.php?username=' . $user_unix_name . '&sessid=' . $sessionId . '&host=' . $server_dns . '&cs=' . $conference_service . '&room=' . $room_name . '&group_id=' . $group_id;
        	echo '<iframe id="mucroom" src="'.$url.'" width="800" height="600" frameborder="0"></iframe>';
        	
        	echo '<script type="text/javascript" src="mucroom.js"></script>';
        	echo '</div>';
        } else {
        	echo '<p class="feedback_error">'.$GLOBALS['Language']->getText('plugin_im', 'chatroom_onlymembers').'</p>';
        }
    }
    
    /**
	 * Display muc logs of project $group_id
	 */
    function muc_logs() {
        $request = HTTPRequest::instance();
    	$group_id = $request->get('group_id');
        $project = project_get_object($group_id);
    	
        $any = $GLOBALS['Language']->getText('global', 'any');
        
        if ($request->exist('log_start_date')) {
        	$start_date = $request->get('log_start_date');
        	if ($start_date == '') {
        		$start_date = $any;
        	}	
        } else {
        	$week_ago = mktime( 0, 0, 0, date("m"), date("d") - 7, date("Y") );
 			$start_date = date("Y-m-d", $week_ago);
        }
        
        $end_date = $request->get('log_end_date');
    	if ($end_date == '') {
        	$end_date = $any;
        }
        
        echo '<h2>' . $GLOBALS['Language']->getText('plugin_im', 'muc_logs_title') . '</h2>';
	    	    
	    echo '<form name="muclog_search" id="muclog_search" action="">';
	    echo ' <fieldset>';
	    echo '  <legend>' . $GLOBALS['Language']->getText('plugin_im', 'muc_logs_search') . ' <img src="'.$this->iconsPath.'help.png" alt="' . $GLOBALS['Language']->getText('plugin_im', 'muc_logs_helpsearch') . '" title="' . $GLOBALS['Language']->getText('plugin_im', 'muc_logs_helpsearch') . '" /> </legend>';
	    echo '  <p>';
	    echo '   <label for="log_start_date">' . $GLOBALS['Language']->getText('plugin_im', 'muc_logs_start_date') . '</label>';
	    echo $GLOBALS['HTML']->getDatePicker('log_start_date', 'log_start_date', $start_date);
	    echo '  </p>';
	    echo '  <p>';
	    echo '   <label for="log_end_date">' . $GLOBALS['Language']->getText('plugin_im', 'muc_logs_end_date') . '</label>';
	    echo $GLOBALS['HTML']->getDatePicker('log_end_date', 'log_end_date', $end_date);
	    echo '  </p>';
	    echo '  <p>';
	    echo '   <label for="search_button">&nbsp;</label>';
	    echo '  <input id="search_button" type="submit" value="' . $GLOBALS['Language']->getText('plugin_im', 'search') . '">';
	    echo '  </p>';
	    echo ' </fieldset>';
	    echo ' <input type="hidden" name="action" value="muc_logs" />';
	    echo ' <input type="hidden" name="group_id" value="'.$group_id.'" />';
	    echo '</form>';
	    
    	$mclm = IMMucConversationLogManager::getMucConversationLogManagerInstance();
	    $conversations = null;
    	try {
	    	if ($start_date == $any && $end_date == $any) {
	    		$conversations = $mclm->getConversationLogsByGroupName($project->getUnixName(true));	// MUC room names are lower cases TODO : check it	
	    	} elseif ($start_date == $any && $end_date != $any) {
	    		$conversations = $mclm->getConversationLogsByGroupNameBeforeDate($project->getUnixName(true), $end_date);	// MUC room names are lower cases TODO : check it
	    	} elseif ($start_date != $any && $end_date == $any) {
	    		$conversations = $mclm->getConversationLogsByGroupNameAfterDate($project->getUnixName(true), $start_date);	// MUC room names are lower cases TODO : check it
	    	} else {
	    		$conversations = $mclm->getConversationLogsByGroupNameBetweenDates($project->getUnixName(true), $start_date, $end_date);	// MUC room names are lower cases TODO : check it
	    	}
    	} catch (Exception $e) {
	    	echo $e->getMessage();
	    }
	    	
    	
	    if (! $conversations || sizeof($conversations) == 0) {
	    	echo $GLOBALS['Language']->getText('plugin_im', 'no_muc_logs');
	    } else {
	    	
	    	$purifier = CodeX_HTMLPurifier::instance();
	    	$uh = new UserHelper();
	    	
	    	$nick_color_arr = array();	// association array nickname => color
	    	$available_colors = $GLOBALS['HTML']->getTextColors();
	    	
	    	echo '<table class="logs">';
	    	echo ' <tr>';
	    	echo '  <th>' . $GLOBALS['Language']->getText('plugin_im', 'muc_logs_time') . '</th>';
	    	echo '  <th>' . $GLOBALS['Language']->getText('plugin_im', 'muc_logs_user') . '</th>';
	    	echo '  <th>' . $GLOBALS['Language']->getText('plugin_im', 'muc_logs_message') . '</th>';
	    	echo ' </tr>';
	    	$current_day = null;
	    	$current_time_minute = null;
	    	foreach ($conversations as $conv) {
	    		if ($conv->getDay() != $current_day) {
	    			$current_day = $conv->getDay(); 
	    			echo ' <tr class="boxtitle">';
	    			echo '  <td colspan="3">'.$conv->getDay().'</td>';
	    			echo ' </tr>';
	    		}
	    		
	    		// if nickname hasn't its color yet, we give it a new one 
	    		if ( ! array_key_exists($conv->getNickname(), $nick_color_arr)) { 
	    			// if all the colors have been used, we start again with the same colors
	    			if (sizeof($available_colors) == 0) {
	    				$available_colors = $GLOBALS['HTML']->getChartColors();
	    			}
	    			$current_color = array_pop($available_colors);	// remove a color from the array, and set it to current color
	    			$nick_color_arr[$conv->getNickname()] = $GLOBALS['HTML']->getColorCodeFromColorName($current_color);
	    		}
	    		
	    		echo ' <tr>';
	    		if ($conv->getTime() != $current_time_minute) {
	    		    $current_time_minute = $conv->getTime();
	    		    echo '  <td class="log_time">'.$current_time_minute.'</td>'; 
	    		} else {
	    		    echo '  <td class="log_time">&nbsp;</td>';
	    		}
	    		echo '  <td class="log_nickname"><span title="'.$uh->getDisplayNameFromUserName($conv->getUsername()).'" style="color: '. $nick_color_arr[$conv->getNickname()] . ';">&lt;'.$purifier->purify($conv->getNickname(), CODEX_PURIFIER_CONVERT_HTML).'&gt;</span></td>';
	    		echo '  <td class="log_message">'.$purifier->purify($conv->getMessage(), CODEX_PURIFIER_BASIC, $group_id).'</td>';
	    		echo ' </tr>';
	    		
	    	}
	    	echo '</table>';
	    }

    }
    
    /**
	 * Display forms to synchronize projects (site admin view)
	 */
	private function _admin_synchronize_muc_and_grp() {
		$action = '';
		$nb_grp = 0 ;
		$nb_muc = 0;
        
        $im_dao = new IMDao(IMDataAccess::instance($this->getControler()));
        
		$res_grp = $im_dao->search_group_without_shared_group();
		$res_grp = $res_grp->query;
		$res_muc = $im_dao->search_group_without_muc();
		$res_muc = $res_muc->query;
		
		// number of shared group to synchronize
		$nb_grp = db_numrows($res_grp);
        
		// number of muc room to synchronize
		$nb_muc = db_numrows($res_muc);
        
		$array_grp = array();
		if ($nb_grp > 0) {
			$array_grp=result_column_to_array($res_grp,0);
		}
		
		$array_muc = array();
		if ($nb_muc > 0) {
			$array_muc=result_column_to_array($res_muc,0);
		}
		
		$array_muc_and_grp = array_intersect($array_grp, $array_muc);
		
		if (sizeof($array_muc_and_grp)) {
			$array_muc_only = array_diff($array_muc, $array_muc_and_grp);
			$array_grp_only = array_diff($array_grp, $array_muc_and_grp);
		} else {
			$array_muc_only = $array_muc;
			$array_grp_only = $array_grp;
		}
		
        echo'<fieldset>';
		echo'<legend class="im_synchronize">'.$GLOBALS["Language"]->getText('plugin_im_admin','projects_to_sync').'</legend>';
		if ( $nb_grp != 0 || $nb_muc ) {
			//************form
			global $PHP_SELF;
			if (sizeof($array_muc_and_grp)) {
				foreach ($array_muc_and_grp as $key => $val) {
					$project = project_get_object($val);
			        $unix_group_name = strtolower($project->getUnixName());
			        $group_name=$project->getPublicName();
			        $group_description = $project->getDescription();
			        $grp = project_get_object($val); // $val = group_id;
			        $group_id = $grp->getID();
			        $project_members_ids = $grp->getMembersId();
			        foreach ($project_members_ids as $key => $id) {
			        	$group_Owner_object = UserManager::instance()->getUserById($id);
			        	if ($group_Owner_object->isMember($val,'A')) {
			        		$group_Owner_name =trim($group_Owner_object->getName());
			        	}
			        }
			        
			        //field label
			        $unix_group_name_label = $GLOBALS["Language"]->getText('plugin_im_admin','unix_group_name_label');
			        $group_description_label = $GLOBALS["Language"]->getText('plugin_im_admin','group_description_label');
			        $group_Owner_name_label = $GLOBALS["Language"]->getText('plugin_im_admin','group_Owner_name_label');
			        $action_label = $GLOBALS["Language"]->getText('plugin_im_admin','action_label');//plugin_im_admin - unix_group_name_label
			        $action_on = $GLOBALS["Language"]->getText('plugin_im_admin','action_on_muc_and_grp');
			        echo'<fieldset>';
			            echo'<legend class="project_sync">'.$group_name.'</legend>';
			            echo $unix_group_name_label.$unix_group_name.'<br>';
			            echo $group_description_label.$group_description.'<br>';
			            echo $group_Owner_name_label.$group_Owner_name.'<br>';
			            echo $action_label.$action_on.'<br>';
			            echo '
					        <FORM class="project_sync" action="/plugins/IM/?action=codex_im_admin" method="POST">
					         <INPUT TYPE="HIDDEN" NAME="action" VALUE="synchronize_muc_and_grp">
                             <INPUT TYPE="HIDDEN" NAME="unix_group_name" VALUE="'.$unix_group_name.'">
					         <INPUT TYPE="HIDDEN" NAME="group_name" VALUE="'.$group_name.'">
					         <INPUT TYPE="HIDDEN" NAME="group_id" VALUE='.$group_id.'>
					         <INPUT TYPE="HIDDEN" NAME="group_description" VALUE="'.$group_description.'">
					       	 <INPUT TYPE="HIDDEN" NAME="group_Owner_name" VALUE="'.$group_Owner_name.'">
					       	 <INPUT type="submit" name="submit" value="'.$GLOBALS["Language"]->getText('plugin_im_admin','im_admin_synchro_muc').'">
					        </FORM>
					        ';
			        echo'</fieldset>';
				}	
			}
			
			if (sizeof($array_grp_only)) {
				foreach ($array_grp_only as $key => $val) {
					$project = project_get_object($val);
			        $unix_group_name = strtolower($project->getUnixName());
			        $group_name = $project->getPublicName();
			        $group_description = $project->getDescription();
			        $grp = project_get_object($val); // $val = group_id;
			        $group_id = $grp->getID();
			        $project_members_ids = $grp->getMembersId();
			        foreach ($project_members_ids as $key => $id) {
			        	$group_Owner_object = UserManager::instance()->getUserById($id);
			        	if ($group_Owner_object->isMember($val,'A')) {
			        		$group_Owner_name =$group_Owner_object->getName();
			        	}
			        }
			        
			        //field label
			        $unix_group_name_label = $GLOBALS["Language"]->getText('plugin_im_admin','unix_group_name_label');
			        $group_description_label = $GLOBALS["Language"]->getText('plugin_im_admin','group_description_label');
			        $group_Owner_name_label = $GLOBALS["Language"]->getText('plugin_im_admin','group_Owner_name_label');
			        $action_label = $GLOBALS["Language"]->getText('plugin_im_admin','action_label');
			        $action_on = $GLOBALS["Language"]->getText('plugin_im_admin','action_on_grp');
			        echo'<fieldset>';
			            echo'<legend class="project_sync">'.$group_name.'</legend>';
			            echo $unix_group_name_label.$unix_group_name.'<br>';
			            echo $group_description_label.$group_description.'<br>';
			            echo $group_Owner_name_label.$group_Owner_name.'<br>';
			            echo $action_label.$action_on.'<br>';
			            echo '
					        <FORM class="project_sync" action="/plugins/IM/?action=codex_im_admin" method="POST">
					         <INPUT TYPE="HIDDEN" NAME="action" VALUE="synchronize_grp_only">
                             <INPUT TYPE="HIDDEN" NAME="unix_group_name" VALUE="'.$unix_group_name.'">
					         <INPUT TYPE="HIDDEN" NAME="group_name" VALUE="'.$group_name.'">
					         <INPUT TYPE="HIDDEN" NAME="group_id" VALUE='.$group_id.'>
					         <INPUT TYPE="HIDDEN" NAME="group_description" VALUE="'.$group_description.'">
					      	 <INPUT TYPE="HIDDEN" NAME="group_Owner_name" VALUE="'.$group_Owner_name.'">
					     	 <INPUT type="submit" name="submit" value="'.$GLOBALS["Language"]->getText('plugin_im_admin','im_admin_synchro_muc').'">
					        </FORM>
					        ';
			        echo'</fieldset>';
				}
			}
			
			if (sizeof($array_muc_only)) {
				foreach ($array_muc_only as $key => $val) {
					$project = project_get_object($val);
			        $unix_group_name = strtolower($project->getUnixName());
			        $group_name = $project->getPublicName();
			        $group_description = $project->getDescription();
			        $grp = project_get_object($val); // $val = group_id;
			        $group_id = $grp->getID();
			        $project_members_ids = $grp->getMembersId();
			        foreach ($project_members_ids as $key => $id){
			        	$group_Owner_object = UserManager::instance()->getUserById($id);
			        	if ($group_Owner_object->isMember($val,'A')) {
			        		$group_Owner_name = $group_Owner_object->getName();
			        	}
			        }
			        //field label
			        $unix_group_name_label = $GLOBALS["Language"]->getText('plugin_im_admin','unix_group_name_label');
			        $group_description_label = $GLOBALS["Language"]->getText('plugin_im_admin','group_description_label');
			        $group_Owner_name_label = $GLOBALS["Language"]->getText('plugin_im_admin','group_Owner_name_label');
			        $action_label = $GLOBALS["Language"]->getText('plugin_im_admin','action_label');
			        $action_on = $GLOBALS["Language"]->getText('plugin_im_admin','action_on_muc');
			        echo'<fieldset>';
			        echo'<legend class="project_sync">'.$group_name.'</legend>';
			        echo $unix_group_name_label.$unix_group_name.'<br>';
			        echo $group_description_label.$group_description.'<br>';
			        echo $group_Owner_name_label.$group_Owner_name.'<br>';
			        echo $action_label.$action_on.'<br>';
			        echo '
					     <FORM class="project_sync" action="/plugins/IM/?action=codex_im_admin" method="POST">
					      <INPUT TYPE="HIDDEN" NAME="action" VALUE="synchronize_muc_only">
                          <INPUT TYPE="HIDDEN" NAME="unix_group_name" VALUE="'.$unix_group_name.'">
					      <INPUT TYPE="HIDDEN" NAME="group_name" VALUE="'.$group_name.'">
					      <INPUT TYPE="HIDDEN" NAME="group_id" VALUE='.$group_id.'>
					      <INPUT TYPE="HIDDEN" NAME="group_description" VALUE="'.$group_description.'">
					   	  <INPUT TYPE="HIDDEN" NAME="group_Owner_name" VALUE="'.$group_Owner_name.'">
					   	  <INPUT type="submit" name="submit" value="'.$GLOBALS["Language"]->getText('plugin_im_admin','im_admin_synchro_muc').'">
					     </FORM>
					     ';
			        echo'</fieldset>';
				}	
			}
			
			echo '
				 <FORM class="project_sync" action="/plugins/IM/?action=codex_im_admin" method="POST">
				  <INPUT TYPE="HIDDEN" NAME="action" VALUE="synchronize_all"> 
				  <INPUT type="submit" name="submit" value="'.$GLOBALS["Language"]->getText('plugin_im_admin','im_admin_synchro_all').'">
				 </FORM>';
		} else {
            echo $GLOBALS["Language"]->getText('plugin_im_admin','no_project_to_synchronized');
		}
		echo'</fieldset>';
	}

}

?>
