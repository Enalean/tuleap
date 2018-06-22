<?php
/**
 * Copyright (c) Enalean, 2011 - 2017. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Chart\ColorsForCharts;

require_once('pre.php');
require_once('common/mvc/Views.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('www/project/export/project_export_utils.php');

require_once('IMDao.class.php');
require_once('IMDataAccess.class.php');

require_once('IMMucLogManager.class.php');

class IMViews extends Views {

	protected $iconsPath;

    function __construct(&$controler, $view=null) {
        $this->View($controler, $view);
        $this->iconsPath = $controler->getIconPath();
    }

	function getIconsPath() {
        return $this->iconsPath;
    }

    function display($view='') {
        if ($view == 'get_presence') {
            $this->$view();
        } elseif ($view == 'export_muc_logs') {
            $this->$view();
        } else {
            parent::display($view);
        }
    }

    function header() {
        $request = HTTPRequest::instance();
        $group_id = $request->get('group_id');

        if ($this->getControler()->view == 'codendi_im_admin') {
            $GLOBALS['HTML']->header(array('title'=>$this->_getTitle(),'selected_top_tab' => 'admin', 'main_classes' => array('tlp-framed')));
        } else {
            $GLOBALS['HTML']->header(array('title'=>$this->_getTitle(),'group' => $group_id,'toptab' => 'IM'));
        	if (user_ismember($request->get('group_id'))) {
            	echo '<b><a href="/plugins/IM/?group_id='. $request->get('group_id') .'&amp;action=muc_logs">'. $GLOBALS['Language']->getText('plugin_im', 'toolbar_muc_logs') .'</a> | </b>';
        	}
            echo $this->_getHelp();
        }
    }

    function _getHelp() {
        return help_button('communication.html#instant-messaging-plug-in', false, $GLOBALS['Language']->getText('global', 'help'));
    }

    function _getTitle() {
        return $GLOBALS['Language']->getText('plugin_im','title');
    }

    function footer() {
        $GLOBALS['HTML']->footer(array());
    }

    // {{{ Views
    function codendi_im_admin() {
		echo '<h2><b>'.$GLOBALS['Language']->getText('plugin_im_admin','im_admin_title').'</b></h2>';
		echo '<h3><b>'.$GLOBALS['Language']->getText('plugin_im_admin','im_admin_warning').'</b></h3>';
		$this->_admin_synchronize_muc_and_grp();
	}

    function get_presence() {
        header('Content-type: application/json');
        $request = HTTPRequest::instance();
        if ($request->exist('jid')) {
            $presence = $this->getControler()->getPlugin()->getPresence($request->get('jid'));
            echo json_encode($presence);
        } else if (is_array($request->get('jids'))) {
            $presences = array();
            foreach($request->get('jids') as $jid) {
                $presence = $this->getControler()->getPlugin()->getPresence($jid);
                $presence['id'] = md5($jid);
                $presences[] = $presence;
            }
            echo(json_encode($presences));
        }
    }
    // }}}

    /**
     * Display muc logs of project $group_id when coming from a cross reference
     * using monitoring openfire's plugin
     */
    function ref_muc_logs() {
        $request = HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $chat_log = $request->get('chat_log');
        $date_log = substr($chat_log, 0, 4)."-".substr($chat_log, 4, 2)."-".substr($chat_log, 6, 2);
        $this->_display_muc_logs($group_id, $date_log, $date_log);
    }
    /**
     * Display muc logs of project $group_id
     * using monitoring openfire's plugin
     */
    function muc_logs() {
        $request = HTTPRequest::instance();
        $group_id = $request->get('group_id');

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

        $this->_display_muc_logs($group_id, $start_date, $end_date);

    }

    private function _display_muc_logs($group_id, $start_date, $end_date) {
        $pm = ProjectManager::instance();
        $project = $pm->getProject($group_id);
        $any = $GLOBALS['Language']->getText('global', 'any');

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

        $mclm = IMMucLogManager::getMucLogManagerInstance();
        $conversations = null;
        try {
            if ($start_date == $any && $end_date == $any) {
                $conversations = $mclm->getLogsByGroupName($project->getUnixName(true));
            } elseif ($start_date == $any && $end_date != $any) {
                $conversations = $mclm->getLogsByGroupNameBeforeDate($project->getUnixName(true), $end_date);
            } elseif ($start_date != $any && $end_date == $any) {
                $conversations = $mclm->getLogsByGroupNameAfterDate($project->getUnixName(true), $start_date);
            } else {
                $conversations = $mclm->getLogsByGroupNameBetweenDates($project->getUnixName(true), $start_date, $end_date);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        if (! $conversations || sizeof($conversations) == 0) {
            echo $GLOBALS['Language']->getText('plugin_im', 'no_muc_logs');
        } else {

            $purifier = Codendi_HTMLPurifier::instance();
            $uh = new UserHelper();

            $colors_for_charts = new ColorsForCharts();

            $nick_color_arr = array();  // association array nickname => color
            $available_colors = $colors_for_charts->getTextColors();

            echo '<table class="logs">';
            echo ' <tr>';
            echo '  <th>' . $GLOBALS['Language']->getText('plugin_im', 'muc_logs_time') . '</th>';
            echo '  <th>' . $GLOBALS['Language']->getText('plugin_im', 'muc_logs_user') . '</th>';
            echo '  <th>' . $GLOBALS['Language']->getText('plugin_im', 'muc_logs_message') . '</th>';
            echo ' </tr>';
            $current_day = null;
            $current_time_minute = null;
            $last_conversation_activity = null;
            foreach ($conversations as $conv) {
                if ($conv->getDay() != $current_day) {
                    $current_day = $conv->getDay();
                    echo ' <tr class="boxtitle">';
                    echo '  <td colspan="3">'.$conv->getDay().'</td>';
                    echo ' </tr>';
                } else {
                    if (($conv->getTimestamp() - $last_conversation_activity) > IMMucLog::DELAY_BETWEEN_CONVERSATIONS * 60) {
                        echo ' <tr class="conversation_separation">';
                        echo '  <td colspan="3"><hr class="conversation_separation"></td>';
                        echo ' </tr>';
                    }
                }

                // if nickname hasn't its color yet, we give it a new one
                if ( ! array_key_exists($conv->getNickname(), $nick_color_arr)) {
                    // if all the colors have been used, we start again with the same colors
                    if (sizeof($available_colors) == 0) {
                        $available_colors = $colors_for_charts->getChartColors();
                    }
                    $current_color = array_pop($available_colors);  // remove a color from the array, and set it to current color
                    $nick_color_arr[$conv->getNickname()] = $colors_for_charts->getColorCodeFromColorName($current_color);
                }

                echo ' <tr class="'.get_class($conv).'">';
                if ($conv->getTime() != $current_time_minute) {
                    $current_time_minute = $conv->getTime();
                    echo '  <td class="log_time">'.$current_time_minute.'</td>';
                } else {
                    echo '  <td class="log_time">&nbsp;</td>';
                }
                if ($conv->getNickname() != null) {
                    echo '  <td class="log_nickname"><span title="'.$purifier->purify($uh->getDisplayNameFromUserName($conv->getUsername())).'" style="color: '. $nick_color_arr[$conv->getNickname()] . ';">&lt;'.$purifier->purify($conv->getNickname(), CODENDI_PURIFIER_CONVERT_HTML).'&gt;</span></td>';
                } else {
                    echo '  <td class="log_nickname">&nbsp;</td>';
                }
                echo '  <td class="'.get_class($conv).'">'.$purifier->purify($conv->getMessage(), CODENDI_PURIFIER_BASIC, $group_id).'</td>';
                echo ' </tr>';

                // update last activity time
                $last_conversation_activity = $conv->getTimestamp();

            }
            echo '</table>';

            echo '<form action="" method="post" name="muc_logs_export_form" id="muc_logs_export_form">';
            echo ' <input name="type" value="export" type="hidden">';
            echo ' <font size="-1"><input value="'.$GLOBALS['Language']->getText('plugin_im', 'export_muc_logs').'" type="submit"></font><br>';
            echo '</form>';

        }
    }

    /**
     * Export muc logs of project $group_id
     * using monitoring openfire's plugin
     */
    function export_muc_logs() {
        $request = HTTPRequest::instance();
        $group_id = $request->get('group_id');
        $pm = ProjectManager::instance();
        $project = $pm->getProject($group_id);

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

        $mclm = IMMucLogManager::getMucLogManagerInstance();
        $conversations = null;
        try {
            if ($start_date == $any && $end_date == $any) {
                $conversations = $mclm->getLogsByGroupName($project->getUnixName(true));
            } elseif ($start_date == $any && $end_date != $any) {
                $conversations = $mclm->getLogsByGroupNameBeforeDate($project->getUnixName(true), $end_date);
            } elseif ($start_date != $any && $end_date == $any) {
                $conversations = $mclm->getLogsByGroupNameAfterDate($project->getUnixName(true), $start_date);
            } else {
                $conversations = $mclm->getLogsByGroupNameBetweenDates($project->getUnixName(true), $start_date, $end_date);
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        $eol = "\n";
        $col_list = array('date', 'nickname', 'message');
        $lbl_list = array(
                        'date' => $GLOBALS['Language']->getText('plugin_im', 'muc_logs_time'),
                        'nickname' => $GLOBALS['Language']->getText('plugin_im', 'muc_logs_user'),
                        'message' => $GLOBALS['Language']->getText('plugin_im', 'muc_logs_message')
                    );

        $file_name = 'muc_logs_'.$project->getUnixName();
        header ('Content-Type: text/csv');
        header ('Content-Disposition: filename='.$file_name.'.csv');

        if (! $conversations || sizeof($conversations) == 0) {
            echo $GLOBALS['Language']->getText('plugin_im', 'no_muc_logs');
        } else {

            // Build CSV header
            echo build_csv_header($col_list, $lbl_list).$eol;

            // Build CSV content
            foreach ($conversations as $conv) {
                $time = format_date(util_get_user_preferences_export_datefmt(), $conv->getTimestamp());
                if ($conv->getNickname() != null) {
                    $nick = $conv->getNickname();
                } else {
                    $nick = '';
                }
                $message = $conv->getMessage();

                echo build_csv_record($col_list, array('date'=>$time, 'nickname'=>$nick, 'message'=>$message)).$eol;

            }
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
		$res_grp = $res_grp->getResult();
		$res_muc = $im_dao->search_group_without_muc();
		$res_muc = $res_muc->getResult();

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

        $pm       = ProjectManager::instance();
        $purifier = Codendi_HTMLPurifier::instance();
        echo'<fieldset>';
		echo'<legend class="im_synchronize">'.$GLOBALS["Language"]->getText('plugin_im_admin','projects_to_sync').'</legend>';
		if ( $nb_grp != 0 || $nb_muc ) {
			//************form
			if (sizeof($array_muc_and_grp)) {
				foreach ($array_muc_and_grp as $key => $val) {
					$project     = $pm->getProject($val);
			        $unix_group_name     = $purifier->purify(strtolower($project->getUnixName()));
			        $group_name          = $purifier->purify($project->getPublicName());
			        $group_description   = $purifier->purify($project->getDescription());
			        $grp                 = $pm->getProject($val); // $val = group_id;
			        $group_id            = $grp->getID();
			        $project_members_ids = $grp->getMembersId();
			        foreach ($project_members_ids as $key => $id) {
			        	$group_Owner_object = UserManager::instance()->getUserById($id);
			        	if ($group_Owner_object->isMember($val,'A')) {
                                            $group_Owner_name = $purifier->purify(trim($group_Owner_object->getName()));
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
					        <FORM class="project_sync" action="/plugins/IM/?action=codendi_im_admin" method="POST">
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
				$pm = ProjectManager::instance();
                foreach ($array_grp_only as $key => $val) {
					$project     = $pm->getProject($val);
			        $unix_group_name     = $purifier->purify(strtolower($project->getUnixName()));
			        $group_name          = $purifier->purify($project->getPublicName());
			        $group_description   = $purifier->purify($project->getDescription());
			        $grp                 = $pm->getProject($val); // $val = group_id;
			        $group_id            = $grp->getID();
			        $project_members_ids = $grp->getMembersId();
			        foreach ($project_members_ids as $key => $id) {
			        	$group_Owner_object = UserManager::instance()->getUserById($id);
			        	if ($group_Owner_object->isMember($val,'A')) {
                                            $group_Owner_name = $purifier->purify($group_Owner_object->getName());
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
					        <FORM class="project_sync" action="/plugins/IM/?action=codendi_im_admin" method="POST">
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
				$pm = ProjectManager::instance();
                foreach ($array_muc_only as $key => $val) {
					$project     = $pm->getProject($val);
			        $unix_group_name     = $purifier->purify(strtolower($project->getUnixName()));
			        $group_name          = $purifier->purify($project->getPublicName());
			        $group_description   = $purifier->purify($project->getDescription());
			        $grp                 = $pm->getProject($val); // $val = group_id;
			        $group_id            = $grp->getID();
			        $project_members_ids = $grp->getMembersId();
			        foreach ($project_members_ids as $key => $id){
			        	$group_Owner_object = UserManager::instance()->getUserById($id);
			        	if ($group_Owner_object->isMember($val,'A')) {
                                            $group_Owner_name = $purifier->purify($group_Owner_object->getName());
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
					     <FORM class="project_sync" action="/plugins/IM/?action=codendi_im_admin" method="POST">
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
				 <FORM class="project_sync" action="/plugins/IM/?action=codendi_im_admin" method="POST">
				  <INPUT TYPE="HIDDEN" NAME="action" VALUE="synchronize_all">
				  <INPUT type="submit" name="submit" value="'.$GLOBALS["Language"]->getText('plugin_im_admin','im_admin_synchro_all').'">
				 </FORM>';
		} else {
            echo $GLOBALS["Language"]->getText('plugin_im_admin','no_project_to_synchronized');
		}
		echo'</fieldset>';
	}

}
