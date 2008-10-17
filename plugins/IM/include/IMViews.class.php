<?php
require_once('pre.php');
require_once('common/mvc/Views.class.php');
require_once('common/include/HTTPRequest.class.php');

require_once('IMDao.class.php');
require_once('IMDataAccess.class.php');
require_once('JabbexFactory.class.php');

class IMViews extends Views {
	
    function IMViews(&$controler, $view=null) {
        $this->View($controler, $view);
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
        }
    }
    
    function _getHelp($section = '') {
        if (trim($section) !== '' && $section{0} !== '#') {
            $section = '#'.$section;
        }
        return '<b><a href="javascript:help_window(\''.get_server_url().'/plugins/IM/documentation/'.$GLOBALS['Language']->getLanguageCode().'/'.$section.'\');">'.$GLOBALS['Language']->getText('global', 'help').'</a></b>';
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
    	$project= new Group($request->get('group_id'));
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
       
        echo '<div id="mucroom_timer">';
        echo $GLOBALS['Language']->getText('plugin_im','wait_loading');
        echo $GLOBALS['HTML']->getImage('ic/spinner.gif');
        echo '</div>';

        $url = $plugin_path . '/webmuc/muckl.php?username=' . $user_unix_name . '&sessid=' . $sessionId . '&host=' . $server_dns . '&cs=' . $conference_service . '&room=' . $room_name;
        echo '<iframe id="mucroom" src="'.$url.'" width="800" height="600" frameborder="0"></iframe>';

        echo '<script type="text/javascript" src="mucroom.js"></script>';
        echo '</div>';
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
			        $grp = new Group($val); // $val = group_id;
			        $group_id = $grp->getID();
			        $project_members_ids = $grp->getMembersId();
			        foreach ($project_members_ids as $key => $id) {
			        	$group_Owner_object = new User($id);
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
			        $grp = new Group($val); // $val = group_id;
			        $group_id = $grp->getID();
			        $project_members_ids = $grp->getMembersId();
			        foreach ($project_members_ids as $key => $id) {
			        	$group_Owner_object = new User($id);
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
			        $grp = new Group($val); // $val = group_id;
			        $group_id = $grp->getID();
			        $project_members_ids = $grp->getMembersId();
			        foreach ($project_members_ids as $key => $id){
			        	$group_Owner_object = new User($id);
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