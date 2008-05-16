<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// 
//
//
//	Originally by to the SourceForge Team,1999-2000
//
//  Written for CodeX by Stephane Bouhet
//

require_once('common/tracker/ArtifactType.class.php');
require_once('HTML_Graphs.php');
require_once('www/project/admin/ugroup_utils.php');
require_once('common/tracker/ArtifactGlobalNotificationFactory.class.php');
require_once('javascript_helpers.php');

$GLOBALS['Language']->loadLanguageMsg('tracker/tracker');

class ArtifactTypeHtml extends ArtifactType {

    var $FIELD_VALUE_STATUS_HIDDEN    = 'H';
    var $FIELD_VALUE_STATUS_PERMANENT = 'P';
    
	/**
	 *  ArtifactType() - constructor
	 *
	 *  @param $Group object
	 *  @param $artifact_type_id - the id # assigned to this artifact type in the db
	 */
	function ArtifactTypeHtml(&$Group,$artifact_type_id=false, $arr=false) {
		return $this->ArtifactType($Group,$artifact_type_id,$arr);
	}

	/**
	 *  Display the header menu for this artifact type
	 *
	 *  @param params: array of parameters used to display the header
	 * 
	 *  @return void
	 */
	function header($params) {
		global $Language;
		$group_id= $this->Group->getID();
        $hp = CodeX_HTMLPurifier::instance();
        
        $GLOBALS['HTML']->includeJavascriptFile("/scripts/prototype/prototype.js");
        $GLOBALS['HTML']->includeJavascriptFile("/scripts/scriptaculous/scriptaculous.js");
        $GLOBALS['HTML']->includeJavascriptFile("/scripts/fieldDependencies.js");
        $GLOBALS['HTML']->includeJavascriptFile("/scripts/fieldEditor.js");
                
        
		//required by new site_project_header
		$params['group']=$group_id;
		$params['toptab']='tracker';
		$params['tabtext']=$this->getName();

		site_project_header($params);
                if (!isset($params['pv']) || $params['pv'] == 0) {
                    echo '<h3>'.$Language->getText('tracker_import_admin','tracker').': <a href="/tracker/?group_id='.(int)$group_id.'&atid='.(int)$this->getID().'">'. $hp->purify(SimpleSanitizer::unsanitize($this->getName()), CODEX_PURIFIER_CONVERT_HTML) .'</a></h3><p>';

                    echo '<strong><a href="/tracker/?func=add&group_id='.(int)$group_id.'&atid='. (int)$this->getID() .'">'.$Language->getText('tracker_include_type','submit_new', $hp->purify($this->getCapsItemName(), CODEX_PURIFIER_CONVERT_HTML) ).'</a>';
                    echo ' | <a href="/tracker/?func=browse&set=my&group_id='.(int)$group_id.'&atid='. (int)$this->getID() .'">'.$Language->getText('tracker_include_type','my', $hp->purify($this->getCapsItemName(), CODEX_PURIFIER_CONVERT_HTML) ).'s </a>';
                    echo ' | <a href="/tracker/?func=browse&set=open&group_id='.(int)$group_id.'&atid='. (int)$this->getID() .'">'.$Language->getText('tracker_include_type','open', $hp->purify($this->getCapsItemName(), CODEX_PURIFIER_CONVERT_HTML) ).'s </a>';
                    if ($this->userIsAdmin()) {
			echo ' | <a href="/tracker/?func=masschange&group_id='.(int)$group_id.'&atid='. (int)$this->getID() .'">'.$Language->getText('tracker_index','mass_change').' </a>';
			echo ' | <a href="/tracker/?func=import&group_id='.(int)$group_id.'&atid='. (int)$this->getID() .'">'.$Language->getText('tracker_import_admin','import').' </a>';
                    }
                    echo ' | <a href="/tracker/?func=reporting&group_id='.(int)$group_id.'&atid='.(int)$this->getID().'">'.$Language->getText('tracker_include_type','reporting').'</a>';
                    echo ' | <a href="/tracker/admin/?group_id='.(int)$group_id.'&atid='.(int)$this->getID().'">'.$Language->getText('tracker_include_type','admin').'</a>';
                    if ($params['help']) {
                        echo ' | '.help_button($params['help'],false,$Language->getText('global','help'));
                    }
                    
                    echo '</strong><br>';
                    echo '<HR NoShade SIZE="1" SIZE="90%">';
                }
	}

	/**
	 *  Display the footer for this artifact type
	 *
	 *  @param params: array of parameters used to display the header
	 * 
	 *  @return void
	 */
	function footer($params) {
		site_project_footer($params);
	}

	/**
	 *  Display the admin header menu for this artifact type
	 *
	 *  @param params: array of parameters used to display the header
	 * 
	 *  @return void
	 */
	function adminHeader($params) {
	  global $Language;

		$group_id= $this->Group->getID();

        $GLOBALS['HTML']->includeJavascriptFile("/scripts/prototype/prototype.js");
        $GLOBALS['HTML']->includeJavascriptFile("/scripts/scriptaculous/scriptaculous.js");
        $GLOBALS['HTML']->includeJavascriptFile("/scripts/fieldDependencies.js");

		//required by new site_project_header
		$params['group']=$group_id;
		$params['toptab']='tracker';
		$params['tabtext']=$this->getName();

		site_project_header($params);

		echo '<strong><a href="/tracker/admin/?group_id='.(int)$group_id.'">'.$Language->getText('tracker_index','admin_all_trackers').'</a>';
		echo ' | <a href="/tracker/admin/?group_id='.(int)$group_id.'&atid='. (int)$this->getID() .'">'.$Language->getText('tracker_include_type','admin').'</a>';
		echo ' | <a href="/tracker/admin/?group_id='.(int)$group_id.'&atid='. (int)$this->getID() .'&func=editoptions">'.$Language->getText('tracker_include_type','settings').'</a>';
		echo ' | <a href="/tracker/admin/?group_id='.(int)$group_id.'&atid='. (int)$this->getID() .'&func=permissions">'.$Language->getText('tracker_include_type','permissions').'</a>';
        echo ' | <a href="/tracker/admin/?group_id='.(int)$group_id.'&atid='. (int)$this->getID() .'&func=fieldsets">'.$Language->getText('tracker_include_type','fieldsets').'</a>';
		echo ' | <a href="/tracker/admin/?group_id='.(int)$group_id.'&atid='. (int)$this->getID() .'&func=field_usage">'.$Language->getText('tracker_include_type','field_usage').'</a>';
		echo ' | <a href="/tracker/admin/?group_id='.(int)$group_id.'&atid='. (int)$this->getID() .'&func=field_values">'.$Language->getText('tracker_include_type','field_values').'</a>';
		echo ' | <a href="/tracker/admin/?group_id='.(int)$group_id.'&atid='. (int)$this->getID() .'&func=field_dependencies">'.$Language->getText('tracker_include_type','field_dependencies').'</a>';
		echo ' | <a href="/tracker/admin/?group_id='.(int)$group_id.'&atid='. (int)$this->getID() .'&func=canned">'.$Language->getText('tracker_include_type','canned_resp').'</a>';
		echo ' | <a href="/tracker/admin/?func=report&group_id='.(int)$group_id.'&atid='. (int)$this->getID() .'">'.$Language->getText('tracker_include_type','reports').'</a>';
		echo ' | <a href="/tracker/admin/?func=notification&group_id='.(int)$group_id.'&atid='. (int)$this->getID() .'&func=notification">'.$Language->getText('tracker_include_type','mail_notif').'</a>';
                echo ' | '.help_button($params['help'],false,$Language->getText('global','help'));
		echo '</strong><hr>';
	}

	/**
	 *  Display the admin header menu for artifact type
	 *
	 *  @param params: array of parameters used to display the header
	 * 
	 *  @return void
	 */
	function adminTrackersHeader($params) {
	  global $Language;

		$group_id= $this->Group->getID();

		//required by new site_project_header
		$params['group']=$group_id;
		$params['toptab']='tracker';
		$params['tabtext']=$this->getName();

		site_project_header($params);

		echo '<strong><a href="/tracker/admin/?group_id='.(int)$group_id.'">'.$Language->getText('tracker_index','admin_all_trackers').'</a>';
		echo ' | <a href="/tracker/admin/?group_id='.(int)$group_id.'&func=create">'.$Language->getText('tracker_index','create_new_tracker').'</a>';
		if ($params['help']) {
		    echo ' | '.help_button($params['help'],false,$Language->getText('global','help'));
		}
		echo '</strong><hr>';
	}

	/**
	 *  Display a select box for the canned responses
	 *
	 *  @param name: the select box name
	 *  @param checked: the default value
	 *  @param show_100: add the 100 value
	 *  @param text_100: the 100 label
	 * 
	 *  @return void
	 */
	function cannedResponseBox ($name='canned_response',$checked='xzxz') {
		return html_build_select_box ($this->getCannedResponses(),$name,$checked);
	}
	
	/**
	 *  Display the different options and the trackers lists
	 *
	 *  @return void
	 */
	function displayPendingTrackers() {
        $hp = CodeX_HTMLPurifier::instance();
		global $atf,$Language;
		

		echo '<form name="cal">';
        echo '<script type="text/javascript" src="/scripts/calendar_js.php"></script>';		
		// Get the artfact type list
		$at_arr = $atf->getPendingArtifactTypes();
		echo '<H2>'.$Language->getText('tracker_include_type','pending_removal').'</H2>';
		if (!$at_arr) {
			echo '<h3>'.$Language->getText('tracker_include_type','no_pending').'</h3>';
		} else {
		    echo '<H3>'.$Language->getText('tracker_include_type','pending_deletion').'</H3>';

			$title_arr=array();
			$title_arr[]=$Language->getText('tracker_include_report','id');
			$title_arr[]=$Language->getText('global','Project');
			$title_arr[]=$Language->getText('tracker_import_admin','tracker');
			$title_arr[]=$Language->getText('tracker_include_type','deletion_date');
			$title_arr[]=$Language->getText('tracker_include_type','delay');
			$title_arr[]=$Language->getText('tracker_include_type','restore');
			$title_arr[]=$Language->getText('tracker_include_canned','delete');
			echo html_build_list_table_top ($title_arr);
		
			$fmt = "\n".'<TR class="%s"><td>%s</td><td>%s</td><td>%s</td><td>%s</td>'.
			    '<td align="center">%s</td><td align="center">%s</td><td align="center">%s</td></tr>';
			$i=0;
			while ($arr = db_fetch_array($at_arr)) {
			    echo sprintf($fmt,
					    util_get_alt_row_color($i),
					    $hp->purify($arr['group_artifact_id'], CODEX_PURIFIER_CONVERT_HTML) ,
					    $hp->purify($arr['project_name'], CODEX_PURIFIER_CONVERT_HTML) ,
					    $hp->purify(SimpleSanitizer::unsanitize($arr['name']), CODEX_PURIFIER_CONVERT_HTML) ,
					    '<input type="text" name="delay_date" value="'.date("Y-m-d",$arr['deletion_date']).'"><a href="javascript:show_calendar(\'document.cal.delay_date\',document.cal.delay_date.value,\''.util_get_css_theme().'\',\''.util_get_dir_image_theme().'\');"><img src="'.util_get_image_theme("calendar/cal.png").'"  border="0"></a>',
					    '<a href="javascript: var delay = document.cal.delay_date.value; document.location=\'/tracker/admin/restore.php?func=delay&group_id='.$arr['group_id'].'&atid='.$arr['group_artifact_id'].'&delay_date=\'+delay;"><img src="'.util_get_image_theme("ic/save16b.png").'" border="0" onClick="return confirm(\''.$Language->getText('tracker_include_type','delay_deletion').'\')"></a>',
					    '<a href="/tracker/admin/restore.php?func=restore&group_id='.(int)$arr['group_id']."&atid=".(int)$arr['group_artifact_id'].'"><img src="'.util_get_image_theme("ic/convert.png").'" border="0" onClick="return confirm(\''.$Language->getText('tracker_include_type','restore_tracker').'\')"></a>',
					    '<a href="/tracker/admin/restore.php?func=delete&group_id='.(int)$arr['group_id']."&atid=".(int)$arr['group_artifact_id'].'"><img src="'.util_get_image_theme("ic/trash.png").'" border="0" onClick="return confirm(\''.$Language->getText('tracker_include_type','warning').'\')"></a>' );
			$i=0;
			}
		    // final touch...
		    echo "</TABLE></form>";
		}
	}
	
	/**
	 *  Display the different options and the trackers lists
	 *
	 *  @return void
	 */
	function displayAdminTrackers() {
        $hp = CodeX_HTMLPurifier::instance();
		global $atf,$Language;
				
		// Get the artfact type list
		$at_arr = $atf->getArtifactTypes();
		
		if (!$at_arr || count($at_arr) < 1) {
		    echo '<h2>'.$Language->getText('tracker_index','no_accessible_trackers_hdr').'</h2>';
		    echo '<p>'.$Language->getText('tracker_index','no_accessible_trackers_msg').'</p>';
		} else {
		    echo '<H2>'.$Language->getText('tracker_admin_trackers','all_admin').'</H2>';
			echo '<H3><a href="/tracker/admin/?group_id='.(int)$this->Group->getID().'&func=create">'.$Language->getText('tracker_index','create_new_tracker').'</a></H3>';
			echo $Language->getText('tracker_include_type','create_from_scratch');
			echo '<H3>'.$Language->getText('tracker_include_type','manage').'</H3>';
			echo $Language->getText('tracker_include_type','admin_or_del').'<p>';

			$title_arr=array();
			$title_arr[]=$Language->getText('tracker_include_report','id');
			$title_arr[]=$Language->getText('tracker_import_admin','tracker');
			$title_arr[]=$Language->getText('tracker_include_artifact','desc');
                        if ($this->Group->isTemplate()) 
			        $title_arr[]=$Language->getText('tracker_include_type','instantiate').'?';
			$title_arr[]=$Language->getText('tracker_include_canned','delete');
			echo html_build_list_table_top ($title_arr);
		
                        if ($this->Group->isTemplate()) {
                            // Add an additional column 'Instantiate for new projects'
			    $fmt = "\n".'<TR class="%s"><td>%s</td><td>%s</td><td>%s</td><td align="center">%s</td>'.
			    '<td align="center">%s</td></tr>';
                        } else {
			    $fmt = "\n".'<TR class="%s"><td>%s</td><td>%s</td><td>%s</td>'.
			    '<td align="center">%s</td></tr>';
                        }
			for ($i = 0; $i < count($at_arr); $i++) {
                            if ($this->Group->isTemplate()) {
			       echo sprintf($fmt,
					    util_get_alt_row_color($i),
					    "<a href=\"/tracker/admin/?group_id=".(int)$this->Group->getID()."&atid=".(int)$at_arr[$i]->getID()."\">". $hp->purify($at_arr[$i]->getID(), CODEX_PURIFIER_CONVERT_HTML) ."</a>",
					    $hp->purify(SimpleSanitizer::unsanitize($at_arr[$i]->getName()), CODEX_PURIFIER_CONVERT_HTML) ,
					    $hp->purify(SimpleSanitizer::unsanitize($at_arr[$i]->getDescription()), CODEX_PURIFIER_BASIC, $at_arr[$i]->getGroupId()) .'&nbsp;',
                                            ($at_arr[$i]->isInstantiatedForNewProjects()?'Yes':'No'),
					    "<a href=\"/tracker/admin/?func=delete_tracker&group_id=".(int)$this->Group->getID()."&atid=".(int)$at_arr[$i]->getID()."\"><img src=\"".util_get_image_theme("ic/trash.png")."\" border=\"0\" onClick=\"return confirm('".$Language->getText('tracker_include_type','warning')."')\"></a>" );

			    } else {
                                echo sprintf($fmt,
					    util_get_alt_row_color($i),
					    "<a href=\"/tracker/admin/?group_id=".(int)$this->Group->getID()."&atid=".(int)$at_arr[$i]->getID()."\">". $hp->purify($at_arr[$i]->getID(), CODEX_PURIFIER_CONVERT_HTML) ."</a>",
					    $hp->purify(SimpleSanitizer::unsanitize($at_arr[$i]->getName()), CODEX_PURIFIER_CONVERT_HTML) ,
					    $hp->purify(SimpleSanitizer::unsanitize($at_arr[$i]->getDescription()), CODEX_PURIFIER_BASIC, $at_arr[$i]->getGroupId()) .'&nbsp;',
					    "<a href=\"/tracker/admin/?func=delete_tracker&group_id=".(int)$this->Group->getID()."&atid=".(int)$at_arr[$i]->getID()."\"><img src=\"".util_get_image_theme("ic/trash.png")."\" border=\"0\" onClick=\"return confirm('".$Language->getText('tracker_include_type','warning')."')\"></a>" );
                            }
			}
		    // final touch...
		    echo "</TABLE>";
		}
	}
	
	/**
	 *  Display the different options for administrate a tracker
	 *
	 *  @return void
	 */
	function displayAdminTracker($group_id,$atid) {
	  global $Language;
      $hp = CodeX_HTMLPurifier::instance();
	    echo '<H2>'.$Language->getText('tracker_import_admin','tracker').' \'<a href="/tracker/?group_id='.(int)$group_id.'&atid='.(int)$atid.'&func=browse">'.$hp->purify(SimpleSanitizer::unsanitize($this->getName()), CODEX_PURIFIER_CONVERT_HTML).'</a>\''.$Language->getText('tracker_include_type','administration').'</H2>';

	    if ( $this->userIsAdmin() ) {
			echo '<H3><a href="/tracker/admin/?group_id='.(int)$group_id.'&atid='.(int)$atid.'&func=editoptions">'.$Language->getText('tracker_include_type','settings').'</a></H3>';
			echo $Language->getText('tracker_include_type','define_title');
            echo '<H3><a href="/tracker/admin/?group_id='.(int)$group_id.'&atid='.(int)$atid.'&func=permissions">'.$Language->getText('tracker_include_type','manage_permissions').'</a></H3>';
            echo $Language->getText('tracker_include_type','define_manage_permissions');
            echo '<H3><a href="/tracker/admin/?group_id='.(int)$group_id.'&atid='.(int)$atid.'&func=fieldsets">'.$Language->getText('tracker_include_type','manage_fieldsets').'</a></H3>';
            echo $Language->getText('tracker_include_type','define_manage_fieldsets');
			echo '<H3><a href="/tracker/admin/?func=field_usage&group_id='.(int)$group_id.'&atid='.(int)$atid.'">'.$Language->getText('tracker_include_type','mng_field_usage').'</a></H3>';
			echo $Language->getText('tracker_include_type','define_use');
			echo '<H3><a href="/tracker/admin/?func=field_values&group_id='.(int)$group_id.'&atid='.(int)$atid.'">'.$Language->getText('tracker_include_type','mng_field_values').'</a></H3>';
			echo $Language->getText('tracker_include_type','define_values');
			echo '<H3><a href="/tracker/admin/?func=field_dependencies&group_id='.(int)$group_id.'&atid='.(int)$atid.'">'.$Language->getText('tracker_include_type','mng_field_dependencies').'</a></H3>';
			echo $Language->getText('tracker_include_type','define_field_dependencies');
			echo '<H3><a href="/tracker/admin/?group_id='.(int)$group_id.'&atid='.(int)$atid.'&func=canned">'.$Language->getText('tracker_include_type','mng_response').'</a></H3>';
			echo $Language->getText('tracker_include_type','add_del_resp');
		}

		echo '<H3><a href="/tracker/admin/?func=report&group_id='.(int)$group_id.'&atid='.(int)$atid.'">'.$Language->getText('tracker_include_type','mng_reports').'</a></H3>';
		echo $Language->getText('tracker_include_type','define_reports');
				
		echo '<H3><a href="/tracker/admin?func=notification&group_id='.(int)$group_id.'&atid='.(int)$atid.'">'.$Language->getText('tracker_include_type','mail_notif').'</a></H3>';
		echo $Language->getText('tracker_include_type','define_notif');

	}

        /**
         * Display Menu for permissions
         */
        function displayPermissionsGeneralMenu() {
            $this->displayAdminTitle($GLOBALS['Language']->getText('tracker_include_type','manage_permissions_title'));
            $permissions = array(
                                 array(
                                       'link' => '/tracker/admin/?group_id='.(int)$this->getGroupID().'&atid='.(int)$this->getID().'&func=permissions&perm_type=tracker',
                                       'name' => $GLOBALS['Language']->getText('tracker_include_type','manage_tracker_permissions'),
                                       'desc' => $GLOBALS['Language']->getText('tracker_include_type','define_manage_tracker_permissions')
                                       ),
                                 array(
                                       'link' => '/tracker/admin/?group_id='.(int)$this->getGroupID().'&atid='.(int)$this->getID().'&func=permissions&perm_type=fields',
                                       'name' => $GLOBALS['Language']->getText('tracker_include_type','manage_fields_tracker_permissions'),
                                       'desc' => $GLOBALS['Language']->getText('tracker_include_type','define_manage_fields_tracker_permissions')
                                       )
                                 );
            $this->_displayAdminMenu($permissions);

        }

        /**
         * Display the title of a tracker administration page
         * @protected
         */
        function displayAdminTitle($title) {
            $hp = CodeX_HTMLPurifier::instance();
            echo '<H2>',
                $GLOBALS['Language']->getText('tracker_import_admin','tracker'),
                ' \'<a href="/tracker/admin/?group_id=',(int)$this->getGroupID(),'&atid=',(int)$this->getID(),'">',
                 $hp->purify(SimpleSanitizer::unsanitize($this->getName()), CODEX_PURIFIER_CONVERT_HTML) ,
                '</a>\'',
                $title,
                '</H2>';
        }

        /**
         * Display the items of the menu and their description
         * @params array the items, each item is ['link', 'name', 'desc']. 
         * Only name is mandatory (else the item is not displayed.
         * @protected
         */
        function _displayAdminMenu($items) {
            $hp = CodeX_HTMLPurifier::instance();
            $html = '';
            foreach($items as $item) {
                if (isset($item['name'])) {
                    $html .= '<H3>';
                    $name =  $hp->purify($item['name'], CODEX_PURIFIER_CONVERT_HTML) ;
                    if (isset($item['link'])) {
                        $html .= '<a href="'.$item['link'].'">';
                        $html .= $name;
                        $html .= '</a>';
                    } else {
                        $html .= $name;
                    }
                    $html .= '</h3>';
                    if (isset($item['desc'])) {
                        $html .=  $hp->purify($item['desc'], CODEX_PURIFIER_BASIC, $this->getGroupId()) ;
                    }
                }
            }
            echo $html;
        }
 
        /**
         * Display the permissions for the fields of this tracker
         * @param array the informations about ugroups ands their permissions :
         *              ugroups_permissions[field_id]['field'] = ['name', 'id', 'link']
         *                                           ['ugroups'][ugroup_id]['ugroup'] = ['name', 'id', 'link']
         *                                                           isset(['permissions'][PERMISSION_TYPE]) = true if ugroup has this permissions for the Field
         */
        function displayPermissionsFieldsTracker($ugroups_permissions, $group_first, $selected_id = false) {
            $hp = CodeX_HTMLPurifier::instance();
            $this->displayAdminTitle($GLOBALS['Language']->getText('tracker_include_type','manage_fields_tracker_permissions_title'));

            $submit_permission = 'TRACKER_FIELD_SUBMIT';
            $read_permission   = 'TRACKER_FIELD_READ';
            $update_permission = 'TRACKER_FIELD_UPDATE';
            $none = 'TRACKER_NONE';
            $attributes_for_selected = "selected='selected' style='background:#EEE;'"; //TODO: put style in stylesheet
 
            $html = '';

            //form
            $url_action_without_group_first = "?group_id=".(int)$this->getGroupID().'&atid='.(int)$this->getID()."&func=permissions&perm_type=fields";
            $url_action_with_group_first    = $url_action_without_group_first."&group_first=".($group_first?1:0);

            //The change form
            $group_first_value = ($group_first?1:0);
            $group_id          = (int)$this->getGroupID();
            $atid              = (int)$this->getID();
            $html .= <<<EOS
                <script type="text/javascript">
                <!--
                function changeFirstPartId(wanted) {
                   document.form_tracker_permissions_change.selected_id.value = wanted;
                   document.form_tracker_permissions_change.submit();
                }
                //-->
                </script>
                <form name="form_tracker_permissions_change" action="$url_action_with_group_first" method="get">
                      <input type="hidden" name="group_id" value="$group_id" />
                      <input type="hidden" name="atid" value="$atid" />
                      <input type="hidden" name="func" value="permissions" />
                      <input type="hidden" name="perm_type" value="fields" />
                      <input type="hidden" name="group_first" value="$group_first_value" />
                      <input type="hidden" name="selected_id" value="" />
                </form>
EOS;

            //We remove the pseudo field "comment_type"
            $comment_type_field_id = false;
            reset($ugroups_permissions);
            while((list($key,$value) = each($ugroups_permissions)) && !$comment_type_field_id) {
                if ($value['field']['shortname'] === "comment_type_id") {
                    $comment_type_field_id = $key;
                }
            }
            if ($comment_type_field_id) {
                unset($ugroups_permissions[$comment_type_field_id]);
            }
            if ($group_first) {
                //We reorganize the associative array
                $tablo = $ugroups_permissions;
                $ugroups_permissions = array();
                foreach($tablo as $key_field => $value_field) {
                    foreach($value_field['ugroups'] as $key_ugroup => $value_ugroup) {
                        if (!isset($ugroups_permissions[$key_ugroup])) {
                            $ugroups_permissions[$key_ugroup] = array(
                                                                      'values'              => $value_ugroup['ugroup'],
                                                                      'related_parts'       => array(),
                                                                      'tracker_permissions' => $value_ugroup['tracker_permissions']
                                                                      );

                        }
                        $ugroups_permissions[$key_ugroup]['related_parts'][$key_field] = array(
                                                                                        'values'       => $value_field['field'],
                                                                                        'permissions' => $value_ugroup['permissions']
                                                                                        );
                    }
                }
                ksort($ugroups_permissions);
                $header = array(
                                $GLOBALS['Language']->getText('tracker_admin_permissions', 'ugroup'), 
                                $GLOBALS['Language']->getText('tracker_include_report', 'field_label'),
                                $GLOBALS['Language']->getText('tracker_admin_permissions', $submit_permission),
                                $GLOBALS['Language']->getText('tracker_admin_permissions', 'permissions')) ;
            } else {
                foreach($ugroups_permissions as $key_field => $value_field) {
                    $ugroups_permissions[$key_field]['values']        =& $ugroups_permissions[$key_field]['field'];
                    $ugroups_permissions[$key_field]['related_parts'] =& $ugroups_permissions[$key_field]['ugroups'];
                    foreach($value_field['ugroups'] as $key_ugroup => $value_ugroup) {
                        $ugroups_permissions[$key_field]['related_parts'][$key_ugroup]['values'] =& $ugroups_permissions[$key_field]['related_parts'][$key_ugroup]['ugroup'];
                    }
                    ksort($ugroups_permissions[$key_field]['related_parts']);
                    reset($ugroups_permissions[$key_field]['related_parts']);
                }
                $header = array(
                               $GLOBALS['Language']->getText('tracker_include_report', 'field_label'), 
                               $GLOBALS['Language']->getText('tracker_admin_permissions', 'ugroup'), 
                               $GLOBALS['Language']->getText('tracker_admin_permissions', $submit_permission),
                               $GLOBALS['Language']->getText('tracker_admin_permissions', 'permissions')) ;
            }
            reset($ugroups_permissions);
            list($key, $value) = each($ugroups_permissions);


            //header
            if (($group_first && count($ugroups_permissions) < 1) || (!$group_first && count($ugroups_permissions[$key]['related_parts']) < 1)) {
                $html .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'fields_no_ugroups');
            } else {
                
                //The permission form
                $html .= "<form name='form_tracker_permissions' action='".$url_action_with_group_first."' method='post'>";
                $html .= "<div>";
                $html .= '<input type="hidden" name="selected_id" value="'.($selected_id?(int)$selected_id:"false").'" />';
                //intro
                $html .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'fields_tracker_intro');
                
                //We display 'group_first' or 'field_first'
                if ($group_first) {
                    $html .= $GLOBALS['Language']->getText('tracker_admin_permissions', 
                                                           'fields_tracker_toggle_field', 
                                                           $url_action_without_group_first."&group_first=0");
                } else {
                    $html .= $GLOBALS['Language']->getText('tracker_admin_permissions', 
                                                           'fields_tracker_toggle_group', 
                                                           $url_action_without_group_first."&group_first=1");
                }

                $html .= html_build_list_table_top($header);
                
                //body
                $i = 0;
                $a_star_is_displayed = false;
                
                //The select box for the ugroups or fields (depending $group_first)
                $html .= "\n<tr class='". util_get_alt_row_color($i++)."'>";
                $html .= "<td rowspan='".(count($ugroups_permissions[$key]['related_parts'])+1)."' style='vertical-align:top;'>";
                $html .= "<select onchange=\"changeFirstPartId(this.options[this.selectedIndex].value);\">";
                foreach($ugroups_permissions as $part_permissions) {
                    if ($selected_id === false) {
                        $selected_id = $part_permissions['values']['id'];
                    }                   
                    $html .= "<option value='".(int)$part_permissions['values']['id']."' ";
                    if ($part_permissions['values']['id'] === $selected_id) {
                        $first_part    = $part_permissions['values'];
                        $related_parts = $part_permissions['related_parts'];
                        $html .= $attributes_for_selected;
                    }
                    $html .= " >";
                    $html .= $part_permissions['values']['name'];
                    if ($group_first) {
                        if (isset($part_permissions['tracker_permissions'])
                        && count($part_permissions['tracker_permissions']) === 0) {
                            $html .= " *";
                            $a_star_is_displayed = true;
                        }
                    }
                    $html .= "</option>";
                }
                $html .= "</select>";
                $html .= "</td>";
                $is_first = true;
                
                //The permissions for the current item (field or ugroup, depending $group_id)
                foreach($related_parts as $ugroup_permissions) {
                    $second_part = $ugroup_permissions['values'];
                    $permissions = $ugroup_permissions['permissions'];
                    
                    
                    //The group
                    if (!$is_first) {
                        $html .= "\n<tr class='". util_get_alt_row_color($i++)."'>";
                    } else {
                        $is_first = false;
                    }
                    $html .= '<td>';
                    
                    $name = "<a href='".$url_action_without_group_first."&selected_id=".(int)$second_part['id']."&group_first=".($group_first?0:1)."'>";
                    $name .=  $hp->purify($second_part['name'], $group_first ? CODEX_PURIFIER_DISABLED : CODEX_PURIFIER_BASIC ) ;
                    $name .= "</a>";
                    if (!$group_first && isset($ugroup_permissions['tracker_permissions']) && count($ugroup_permissions['tracker_permissions']) === 0) {
                        $name = "<span >".$name." *</span>"; //TODO css
                        $a_star_is_displayed = true;
                    }
                    $html .= $name;
                    
                    $html .= '</td>';
                    
                    //The permissions
                    {
                        //Submit permission
                        $html .= "<td style='text-align:center;'>";
                        if ($group_first) {
                            $name_of_variable = "permissions[".(int)$second_part['id']."][".(int)$first_part['id']."]";
                        } else {
                            $name_of_variable = "permissions[".(int)$first_part['id']."][".(int)$second_part['id']."]";
                        }
                        $html .= "<input type='hidden' name='".$name_of_variable."[submit]' value='off'/>";
                        
                        $can_submit_or_update = (($group_first 
                                                   && $second_part['shortname'] !== "artifact_id"
                                                   && $second_part['shortname'] !== "submitted_by"
                                                   && $second_part['shortname'] !== "open_date"
                                                   && $second_part['shortname'] !== "last_update_date")
                                                  ||
                                                  (!$group_first 
                                                   && $first_part['shortname'] !== "artifact_id"
                                                   && $first_part['shortname'] !== "submitted_by"
                                                   && $first_part['shortname'] !== "open_date"
                                                   && $first_part['shortname'] !== "last_update_date"));
                        
                        $can_submit = $can_submit_or_update; //(And add here those who can only be submitted)

                        $can_update = $can_submit_or_update &&  (($group_first && $first_part['id'] > 2)
								 ||
								 (!$group_first && $second_part['id'] > 2));

                        $html .= "<input type='checkbox' name=\"".$name_of_variable.'[submit]"  '.
                            (isset($permissions[$submit_permission])?"checked='checked'":"")." ".($can_submit?"":"disabled='disabled'")." /> ";
                        $html .= "</td><td>";
                        
                        
                        //Other permissions (R/W)
                        $html .= "<select name='".$name_of_variable."[others]' >";
                        $html .= "<option value='100' ".(!isset($permissions[$read_permission]) && !isset($permissions[$update_permission])?$attributes_for_selected:"")." >".$GLOBALS['Language']->getText('tracker_admin_permissions', $none)."</option>";
                        $html .= "<option value='0' ".(isset($permissions[$read_permission]) && !isset($permissions[$update_permission])?$attributes_for_selected:"")." >".$GLOBALS['Language']->getText('tracker_admin_permissions', $read_permission)."</option>";
                        
                        if ($can_update) {
                            $html .= "<option value='1' ".(isset($permissions[$update_permission])?$attributes_for_selected:"")." >".$GLOBALS['Language']->getText('tracker_admin_permissions', $update_permission)."</option>";
                        }
                        $html .= "</select>";
                        
                    }
                    $html .= "</td>";
                    $html .= "</tr>\n";
                }
                
                //end of table
                $html .= "</table>";
                if ($a_star_is_displayed) {
                    $html .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'ug_may_have_no_access',"/tracker/admin/?group_id=".(int)$this->getGroupID()."&atid=".(int)$this->getID()."&func=permissions&perm_type=tracker");
                }
                $GLOBALS['Language']->loadLanguageMsg('project/project');
                $html .= "<input type='submit' name='update' value=\"".$GLOBALS['Language']->getText('project_admin_permissions','submit_perm')."\" />";
                //{{{20050602 NTY: removed. what is default permissions ???
                //$html .= "<input type='submit' name='reset' value=\"".$GLOBALS['Language']->getText('project_admin_permissions','reset_to_def')."\" />";
                //}}}
            }
            $html .= "</div></form>";
            $html .= "<p>";
            $html .= $GLOBALS['Language']->getText('project_admin_permissions',
                                                   'admins_create_modify_ug',
                                                   array(
                                                         "/project/admin/editugroup.php?func=create&group_id=".(int)$this->getGroupID(),
                                                         "/project/admin/ugroup.php?group_id=".(int)$this->getGroupID()
                                                         )
                                                   );
            $html .= "</p>";
            print $html;
        }

        /**
         * Display the permissions for this tracker
         * @param array the informations about ugroups ands their permissions :
         *              ugroups_permissions[i]['ugroup'] = ['name', 'id', 'link']
         *                              isset(['permissions'][PERMISSION_TYPE]) = true if ugroup has this permissions for the artifactType
         */
        function displayPermissionsTracker($ugroups_permissions) {
            $hp = CodeX_HTMLPurifier::instance();
            $this->displayAdminTitle($GLOBALS['Language']->getText('tracker_include_type','manage_tracker_permissions_title'));
            $full_permission      = 'TRACKER_ACCESS_FULL';
            $assignee_permission  = 'TRACKER_ACCESS_ASSIGNEE';
            $submitter_permission = 'TRACKER_ACCESS_SUBMITTER';
            $none                 = 'TRACKER_NONE';

            $html = '';

            //form
            $html .= "<form name='form_tracker_permissions' action='?group_id=".(int)$this->getGroupID().'&atid='.(int)$this->getID()."&func=permissions&perm_type=tracker' method='post'>";
            $html .= "<div>";

            //intro
            $html .= $GLOBALS['Language']->getText('tracker_admin_permissions', 'tracker_intro');

            //header
            $html .= html_build_list_table_top(array(
                                                     $GLOBALS['Language']->getText('tracker_admin_permissions', 'ugroup'), 
                                                     $GLOBALS['Language']->getText('tracker_admin_permissions', 'permissions')));

            //body
            ksort($ugroups_permissions);
            reset($ugroups_permissions);
            $i = 0;
            foreach($ugroups_permissions as $ugroup_permissions) {
                $ugroup      = $ugroup_permissions['ugroup'];
                $permissions = $ugroup_permissions['permissions'];
                
                $html .= '<tr class="'. util_get_alt_row_color($i++).'">';
                $html .= '<td>';
                $name  =  $hp->purify($ugroup['name'], CODEX_PURIFIER_CONVERT_HTML) ;
                if (isset($ugroup['link'])) {
                    $html .= "<a href='".$ugroup['link']."'>";
                    $html .= $name;
                    $html .= "</a>";
                } else {
                    $html .= $name;
                }
                $html .= '</td>';
                $html .= "<td>";

                $html .="<select name='permissions_".$ugroup['id']."' >";
                $attributes_for_selected = "selected='selected' style='background:#EEE;'"; //TODO: put style in stylesheet
                $html .= "<option value='100' ".(count($permissions) == 0?$attributes_for_selected:"")." >".$GLOBALS['Language']->getText('tracker_admin_permissions', $none)."</option>";
                $html .= "<option value='0' ".(isset($permissions[$full_permission])?$attributes_for_selected:"")." >".$GLOBALS['Language']->getText('tracker_admin_permissions', $full_permission)."</option>";
                
                //We don't show specific access permissions for anonymous users and registered
                if ($ugroup['id'] != $GLOBALS['UGROUP_ANONYMOUS'] && $ugroup['id'] != $GLOBALS['UGROUP_REGISTERED']) {
                    $html .= "<option value='1' ".(isset($permissions[$assignee_permission]) && !isset($permissions[$submitter_permission])?$attributes_for_selected:"")." >".$GLOBALS['Language']->getText('tracker_admin_permissions', $assignee_permission)."</option>";
                    $html .= "<option value='2' ".(!isset($permissions[$assignee_permission]) && isset($permissions[$submitter_permission])?$attributes_for_selected:"")." >".$GLOBALS['Language']->getText('tracker_admin_permissions', $submitter_permission)."</option>";
                    $html .= "<option value='3' ".(isset($permissions[$assignee_permission]) && isset($permissions[$submitter_permission])?$attributes_for_selected:"")." >".$GLOBALS['Language']->getText('tracker_admin_permissions', $assignee_permission."_AND_".$submitter_permission)."</option>";
                }
                $html .= '</select></td>';
                $html .= '</tr>';
            }
            //end of table
            $html .= "</table>";
            $GLOBALS['Language']->loadLanguageMsg('project/project');
            $html .= "<input type='submit' name='update' value=\"".$GLOBALS['Language']->getText('project_admin_permissions','submit_perm')."\" />";
            //{{{20050602 NTY: removed. what is default permissions ???
            //$html .= "<input type='submit' name='reset' value=\"".$GLOBALS['Language']->getText('project_admin_permissions','reset_to_def')."\" />";
            //}}}
            $html .= "</div></form>";
            $html .= "<p>";
            $html .= $GLOBALS['Language']->getText('project_admin_permissions',
                                                   'admins_create_modify_ug',
                                                   array(
                                                         "/project/admin/editugroup.php?func=create&group_id=".(int)$this->getGroupID(),
                                                         "/project/admin/ugroup.php?group_id=".(int)$this->getGroupID()
                                                         )
                                                   );
            $html .= "</p>";
            print $html;
            
        }
	/**
	 *  Display the select box with the permissions values
	 *
	 *  @return void
	 */
	function displayPermValues($i,$perm_level) {
	  global $Language;

		$out = '<FONT size="-1"><SELECT name="user_name['.$i.']">';
		$out .= '<OPTION value="0"'.(($perm_level==0)?" selected":"").'>'.$Language->getText('global','none');
		$out .= '<OPTION value="1"'.(($perm_level==1)?" selected":"").'>'.$Language->getText('project_admin_userperms','tech_only');
		$out .= '<OPTION value="2"'.(($perm_level==2)?" selected":"").'>'.$Language->getText('project_admin_userperms','tech&admin');
		$out .= '<OPTION value="3"'.(($perm_level==3)?" selected":"").'>'.$Language->getText('project_admin_userperms','admin_only');
		$out .= '</SELECT></FONT>';
		
		return $out;
	}

	/**
	 *  Display the users permissions for this tracker
	 *
	 *  @return void
	 */
	function displayUsersPerm() {
		
	  global $Language;

	    $result=$this->getUsersPerm($this->getID());
	    $rows=db_numrows($result);
	
	    if ($rows > 0) {
	
			$title_arr=array();
			$title_arr[]=$Language->getText('tracker_include_type','user');
			$title_arr[]=$Language->getText('tracker_include_type','perm');
			$title_arr[]=$Language->getText('tracker_include_canned','delete');
					
			echo html_build_list_table_top ($title_arr);
		
			for ($i=0; $i < $rows; $i++) {
				$user_id = db_result($result, $i, 'user_id');
			    $user_name = db_result($result, $i, 'user_name');
			    $perm_level =  db_result($result, $i, 'perm_level');
		
			    echo '<TR class="'. util_get_alt_row_color($i).'">'.
			    	 '<TD>'.util_user_link($user_name).'</TD>';
				echo '<TD align="center">'.$this->displayPermValues($i,$perm_level).'</TD>';
				echo '<TD align="center"><a href="/tracker/admin/?group_id='.(int)$this->Group->getID().'&atid='.(int)$this->getID().'&func=deleteuser&user_id='.(int)$user_id.'"><img src="'.util_get_image_theme("ic/trash.png").'" border="0" onClick="return confirm(\''.$Language->getText('tracker_include_type','del_user').'\')"></a></TD>';
				echo '</TR>';
			}
			echo '</TABLE>';
		} else {
			echo '<H3>'.$Language->getText('tracker_include_type','no_user').'</H3>';
		}
		
	}
	
	
	/**
	 *  Display the differents options for this tracker
	 *
	 *  @return void
	 */
	function displayOptions($group_id,$atid) {
        $hp = CodeX_HTMLPurifier::instance();
	  global $Language;

		echo '<H2>'.$Language->getText('tracker_import_admin','tracker').' \'<a href="/tracker/admin/?group_id='.(int)$group_id.'&atid='.(int)$atid.'">'. $hp->purify(SimpleSanitizer::unsanitize($this->getName()), CODEX_PURIFIER_CONVERT_HTML) .'</a>\' - '.$Language->getText('tracker_include_type','settings').'</H2>';
		echo '<form name="form1" >
		  <input type="hidden" name="update" value="1">
		  <input type="hidden" name="group_id" value="'.(int)$group_id.'">
		  <input type="hidden" name="atid" value="'.(int)$atid.'">
		  <input type="hidden" name="func" value="editoptions">
		  <table width="100%" border="0" cellpadding="5">
		    <tr> 
		      <td width="21%"><b>'.$Language->getText('tracker_include_artifact','name').'</b> <font color="red">*</font>:</td>
		      <td width="79%"> 
              <input type="text" name="name" value="'. $hp->purify(SimpleSanitizer::unsanitize($this->getName()), CODEX_PURIFIER_CONVERT_HTML) .'">
		      </td>
		    </tr>
		    <tr> 
		      <td width="21%"><b>'.$Language->getText('tracker_include_artifact','desc').'</b>: <font color="red">*</font></td>
		      <td width="79%"> 
		        <textarea name="description" rows="3" cols="50">'. $hp->purify(SimpleSanitizer::unsanitize($this->getDescription()), CODEX_PURIFIER_CONVERT_HTML) .'</textarea>
		      </td>
		    </tr>
		    <tr> 
		      <td width="21%"><b>'.$Language->getText('tracker_include_type','short_name').'</b>: <font color="red">*</font></td>
		      <td width="79%"> 
		        <input type="text" name="itemname" value="'. $hp->purify($this->getItemName(), CODEX_PURIFIER_CONVERT_HTML) .'">
		      </td>
		    </tr>
		    <tr> 
		      <td width="21%"><b>'.$Language->getText('tracker_include_type','allow_copy').'</b></td>
		      <td width="79%">';
		if ( $this->allowsCopy() ) {
			echo '<input type="checkbox" name="allow_copy" value="1" checked>';
		} else {
			echo '<input type="checkbox" name="allow_copy" value="1">';
		}
		echo '	
		      </td>
		    </tr>';

		if ( $this->Group->isTemplate() ) { // Template group
		echo '	
		    <tr> 
		      <td width="21%"><b>'.$Language->getText('tracker_include_type','instantiate').':</b></td>
		      <td width="79%">';
		if ( $this->isInstantiatedForNewProjects() ) {
			echo '<input type="checkbox" name="instantiate_for_new_projects" value="1" checked>';
		} else {
			echo '<input type="checkbox" name="instantiate_for_new_projects" value="1">';
		}
		echo '	
		      </td>
		    </tr>';
                } else {
                    echo '<input type="hidden" name="instantiate_for_new_projects" value="0">';
                }
		echo '	
		    <tr> 
		      <td width="21%">'.$Language->getText('tracker_include_type','submit_instr').'</td>
		      <td width="79%"> 
		        <textarea name="submit_instructions" rows="3" cols="50">'. $hp->purify($this->getSubmitInstructions(), CODEX_PURIFIER_CONVERT_HTML) .'</textarea>
		      </td>
		    </tr>
		    <tr> 
		      <td width="21%">'.$Language->getText('tracker_include_type','browse_instr').'</td>
		      <td width="79%"> 
		        <textarea name="browse_instructions" rows="3" cols="50">'. $hp->purify($this->getBrowseInstructions(), CODEX_PURIFIER_CONVERT_HTML) .'</textarea>
		      </td>
		    </tr>
		  </table>
		  <p align="center"><input type="submit" value="'.$Language->getText('global','btn_submit').'"></p>
		</form>';
	}


	/**
	 *  Display a select box for the tracker list for a group
	 *
	 *  @param group_id: the project id
	 *  @param name: the select box name
	 *  @param checked: the default value
	 * 
	 *  @return void
	 */
	function trackersSelectBox ($group_id,$name,$checked='xzxz') {
		global $atf;
        $hp = CodeX_HTMLPurifier::instance();
                $tracker_names = array();
                $tracker_ids   = array();
                $trackers_array = $atf->getArtifactTypesFromId($group_id);
                if ( $trackers_array !== false) {                    
                    foreach($trackers_array as $tracker) {
                        $tracker_names[] =  $hp->purify(SimpleSanitizer::unsanitize($tracker->getName()), CODEX_PURIFIER_CONVERT_HTML) ;
                        $tracker_ids[] = $tracker->getId();
                    }
                }
		
		return html_build_select_box_from_arrays($tracker_ids,$tracker_names, $name,$checked);
	}

	/**
	 *  Display the create tracker form
	 *
	 *  @param group_id: the group id
	 *  @param codex_template: the artifact type id chosen for a CodeX template
	 *  @param group_id_template: the group id chosen for an existing tracker
	 *  @param atid_template: the artifact type id chosen for an existing tracker
	 *  @param name: the name chosen
	 *  @param description: the description chosen
	 *  @param itemname: the short name chosen
	 *
	 *  @return void
	 */
	function displayCreateTracker($group_id,$codex_template,$group_id_template,$atid_template,$name,$description,$itemname) {
	  global $Language;
        $hp = CodeX_HTMLPurifier::instance();
		echo '<script language="JavaScript">
		      function trimStr(value) {
		      	trimValue = "";
		      	for(i=0;i<value.length;i++) {
		      		if ( value.charAt(i) != " " ) {
		      			trimValue = trimValue + value.charAt(i);
		      		}
		      	}
		      	return trimValue;
		      }
		    
			  function onChangeGroup() {
			  	document.form_create.func.value = "create";
			  	document.form_create.submit();
			  }
			  
			  function checkValues() {
			  	if ( trimStr(document.form_create.name.value) == "" ) {
		  			document.form_create.feedback.value = "'.$Language->getText('tracker_include_type','fill_name').'";
		  			return false;
				}
			  	if ( trimStr(document.form_create.description.value) == "" ) {
		  			document.form_create.feedback.value = "'.$Language->getText('tracker_include_type','fill_desc').'";
		  			return false;
				}
			  	if ( trimStr(document.form_create.itemname.value) == "" ) {
		  			document.form_create.feedback.value = "'.$Language->getText('tracker_include_type','fill_short').'";
		  			return false;
				}
	  			return true;
				
			  }
			
			  function onSubmitCreateTemplate() {
			  	if ( checkValues() ) {
			  		if ( (document.form_create.group_id_template.value == "")||(document.form_create.atid_template.value == "") ) {
			  			document.form_create.feedback.value = "'.$Language->getText('tracker_include_type','choose_proj').'";
					  	document.form_create.func.value = "create";
			  		}
			  		document.form_create.atid_chosen.value = document.form_create.atid_template.value;
			  		document.form_create.group_id_chosen.value = document.form_create.group_id_template.value;
				} else {
				  	document.form_create.func.value = "create";
				}
			  	document.form_create.submit();
			  }

			  function onSubmitCreateCodeXTemplate() {
			  	if ( checkValues() ) {
                    if ( document.form_create.codex_template.value == 100 ) {
			  			document.form_create.feedback.value = "'.$Language->getText('tracker_include_type','choose_tmpl').'";
					  	document.form_create.func.value = "create";
			  		}
			  		document.form_create.atid_chosen.value = document.form_create.codex_template.value;
			  		document.form_create.group_id_chosen.value = 100;
				} else {
				  	document.form_create.func.value = "create";
				}
			  	document.form_create.submit();
			  }

			  function showGroupSelection() {
			  	win=window.open("","group_id_selection","height=210,width=480,toolbar=no,location=no,resizable=yes,left=200,top=200");
				win.location = "/tracker/group_selection.php?opener_form=form_create&opener_field=group_id_template&filter=member";
			  }

			  function showTrackerSelection() {
			  	if ( document.form_create.group_id_template.value == "" ) {
			  		alert("'.$Language->getText('tracker_include_type','select_proj').'");
			  		return;
			  	}
			  	win=window.open("","artifact_group_id_selection","height=45,width=400,toolbar=no,location=no,resizable=yes,left=200,top=200");
				win.location = "/tracker/tracker_selection.php?group_id=" + document.form_create.group_id_template.value + "&opener_form=form_create&opener_field=atid_template";
			  }

			  </script>
			 ';
		echo $Language->getText('tracker_include_type','create_tracker');
		echo '<form name="form_create" >
		  <input type="hidden" name="group_id" value="'.(int)$group_id.'">
		  <input type="hidden" name="func" value="docreate">
		  <input type="hidden" name="atid_chosen" value="">
		  <input type="hidden" name="group_id_chosen" value="">
		  <input type="hidden" name="feedback" value="">
		  <table width="100%" border="0" cellpadding="5">
		    <tr> 
		      <td width="21%"><b>'.$Language->getText('tracker_include_artifact','name').'</b>: <font color="red">*</font></td>
		      <td width="79%"> 
		        <input type="text" name="name" value="'. $hp->purify($name, CODEX_PURIFIER_CONVERT_HTML) .'">
		      </td>
		    </tr>
		    <tr> 
		      <td width="21%"><b>'.$Language->getText('tracker_include_artifact','desc').'</b>: <font color="red">*</font></td>
		      <td width="79%"> 
		        <textarea name="description" rows="3" cols="50">'. $hp->purify($description, CODEX_PURIFIER_CONVERT_HTML) .'</textarea>
		      </td>
		    </tr>
		    <tr> 
		      <td width="21%"><b>'.$Language->getText('tracker_include_type','short_name').'</b>: <font color="red">*</font></td>
		      <td width="79%"> 
		        <input type="text" name="itemname" value="'. $hp->purify($itemname, CODEX_PURIFIER_CONVERT_HTML) .'">
		      </td>
		    </tr>
                    <tr><td colspan=2><i>'.$Language->getText('tracker_include_type','avoid_spaces').'</i></td></tr>';
		echo '</table>';
		echo '<p>'.$Language->getText('tracker_include_type','choose_creation').'</p>';
		echo '<table>
			  <tr valign="top">
			     <td width="300"><li><b>'.$Language->getText('tracker_include_type','from_tmpl').'</b></li></td>
			     <td colspan="2">';
        echo $this->trackersSelectBox(100,"codex_template",$codex_template);
        echo '	 &nbsp;<input type="button" name="CreateCodeXTemplate" value="'.$Language->getText('global','btn_create').'" onClick="onSubmitCreateCodeXTemplate()"><br><br></td>
			  <tr valign="top">    
			     <td width="300"><li>'.$Language->getText('tracker_include_type','from_exist').'</li></td>
			     
			     <td>
			     	<table>
			     	  <tr>
			     	  	<td>'.$Language->getText('tracker_include_type','proj_id').'</td>
			     		<td><input name="group_id_template" value="'. $hp->purify($group_id_template, CODEX_PURIFIER_CONVERT_HTML) .'"><a href="javascript:showGroupSelection()"><img src="'.util_get_image_theme("button_choose.png").'" align="absmiddle" border="0"></a></td>
			     	  </tr>
			     	  <tr>
				        <td>'.$Language->getText('tracker_include_type','tracker_id').'</td>
			     		<td><input name="atid_template" value="'. $hp->purify($atid_template, CODEX_PURIFIER_CONVERT_HTML) .'"><a href="javascript:showTrackerSelection()"><img src="'.util_get_image_theme("button_choose.png").'" align="absmiddle" border="0"></a></td>
					  <tr>
					</table>
				 </td>			     		
		         <td><input type="button" name="CreateTemplate" value="'.$Language->getText('global','btn_create').'" onClick="onSubmitCreateTemplate()"></td>
		      <tr>';

	    echo '</form>
			  </table>';
		
	}

	/**
	 *  Display the field usage list
	 *
	 *  @return void
	 */
	function displayFieldUsageList() {
		global $ath,$art_field_fact,$art_fieldset_fact,$Language;
		$hp = CodeX_HTMLPurifier::instance();
		echo '<h3>'.$Language->getText('tracker_include_type','list_all_fields').'</h3>';
		echo '<p>'.$Language->getText('tracker_include_report','mod');
		
		
		// Show all the fields currently available in the system
		$i=0;
		$title_arr=array();
		$title_arr[]=$Language->getText('tracker_include_report','field_label');
		$title_arr[]=$Language->getText('tracker_include_type','type');
		$title_arr[]=$Language->getText('tracker_include_artifact','desc');
        $title_arr[]=$Language->getText('tracker_include_type','fieldset');
		$title_arr[]=$Language->getText('tracker_include_type','rank_in_fieldset');
		$title_arr[]=$Language->getText('global','status');
		$title_arr[]=$Language->getText('tracker_include_canned','delete');
		
		echo html_build_list_table_top ($title_arr);
		
		// Build HTML ouput for  Used fields
		$iu=0;
		$fieldsets_with_used_fields = $art_fieldset_fact->getAllFieldSetsContainingUsedFields();
        $fieldsets_with_unused_fields = $art_fieldset_fact->getAllFieldSetsContainingUnusedFields();
        
        $html = "";
		$tracker_url = '?group_id='.(int)$this->Group->getID().'&atid='.(int)$this->getID();
        
        while (list($fieldset_id, $fieldset) = each($fieldsets_with_used_fields)) {
            $used_fields_in_fieldset = $fieldset->getAllUsedFields();
            // separation between fieldsets
            $html .= '<tr class="fieldset_separator"><td colspan="7">'. $hp->purify(SimpleSanitizer::unsanitize($fieldset->getLabel()), CODEX_PURIFIER_CONVERT_HTML) .'</td></tr>';
            while (list($key, $field) = each($used_fields_in_fieldset) ) {
                $rank = ($field->getPlace()?$field->getPlace():"-");
                $status = ($field->getUseIt()?$Language->getText('tracker_include_type','used'):$Language->getText('tracker_include_type','unused'));
                
                $html .= '<TR class="'.util_get_alt_row_color($iu) .'">';
                $html .= '<TD><A HREF="'.$tracker_url.'&func=display_field_update&field_id='.(int)$field->getID().'">'.
                $hp->purify(SimpleSanitizer::unsanitize($field->getLabel()), CODEX_PURIFIER_CONVERT_HTML) .'</A></td>'.
                "\n<td>". $hp->purify($field->getLabelFieldType(), CODEX_PURIFIER_CONVERT_HTML) .'</td>'.
                "\n<td>". $hp->purify(SimpleSanitizer::unsanitize($field->getDescription()), CODEX_PURIFIER_BASIC, $this->getGroupId()) .'</td>'.
                "\n<td><a href=\"".$tracker_url."&func=display_fieldset_update&fieldset_id=".(int)$fieldset->getID()."\">". $hp->purify(SimpleSanitizer::unsanitize($fieldset->getLabel()), CODEX_PURIFIER_CONVERT_HTML) .'</td>'.
                "\n<td align =\"center\">". $hp->purify($rank, CODEX_PURIFIER_CONVERT_HTML) .'</td>'.
                "\n<td align =\"center\">". $hp->purify($status, CODEX_PURIFIER_CONVERT_HTML) .'</td>';
                if ( $field->isStandardField() ) {
                    // For standard, we can't delete them - Only unused them
                    $html .= "\n<td align =\"center\">-</td>";
                } else {
                    $html .= "\n<td align =\"center\"><a href=\"/tracker/admin/?func=field_delete&group_id=".(int)$this->Group->getID()."&atid=".(int)$this->getID()."&field_id=".(int)$field->getID()."\"><img src=\"".util_get_image_theme("ic/trash.png")."\" border=\"0\" onClick=\"return confirm('".$Language->getText('tracker_include_type','warning_loose_data')."')\"></a></td>";
                }
            
                $html .= "<TR>";
                                        
                $iu++;
            }
        }
        // Now print the HTML table (for used fields)
		if ($iu == 0) {
		    echo '<tr><td colspan="7"><center><b>'.$Language->getText('tracker_include_type','no_field_in_use').'</b></center></tr>'.$html;  
		} else {
		    echo '<tr><td colspan="7"><center><b>'.$Language->getText('tracker_include_type','used_field').'</b></center></tr>'.$html;  
		}
        
        $html = '';
        
        while (list($fieldset_id, $fieldset) = each($fieldsets_with_unused_fields)) {
            $unused_fields_in_fieldset = $fieldset->getAllUnusedFields();
            // separation between fieldsets
            $html .= '<tr class="fieldset_separator"><td colspan="7">'. $hp->purify(SimpleSanitizer::unsanitize($fieldset->getLabel()), CODEX_PURIFIER_CONVERT_HTML) .'</td></tr>';
            while (list($key, $field) = each($unused_fields_in_fieldset) ) {
                $rank = ($field->getPlace()?$field->getPlace():"-");
                $status = ($field->getUseIt()?$Language->getText('tracker_include_type','used'):$Language->getText('tracker_include_type','unused'));
                
                $html .= '<TR class="'.util_get_alt_row_color($iu) .'">';
                $html .= '<TD><A HREF="'.$tracker_url.'&func=display_field_update&field_id='.(int)$field->getID().'">'.
                $hp->purify(SimpleSanitizer::unsanitize($field->getLabel()), CODEX_PURIFIER_CONVERT_HTML) .'</A></td>'.
                "\n<td>". $hp->purify($field->getLabelFieldType(), CODEX_PURIFIER_CONVERT_HTML) .'</td>'.
                "\n<td>". $hp->purify(SimpleSanitizer::unsanitize($field->getDescription()), CODEX_PURIFIER_BASIC, $this->getGroupId()) .'</td>'.
                "\n<td><a href=\"".$tracker_url."&func=display_fieldset_update&fieldset_id=".(int)$fieldset->getID()."\">". $hp->purify(SimpleSanitizer::unsanitize($fieldset->getLabel()), CODEX_PURIFIER_CONVERT_HTML) .'</td>'.
                "\n<td align =\"center\">". $hp->purify($rank, CODEX_PURIFIER_CONVERT_HTML) .'</td>'.
                "\n<td align =\"center\">". $hp->purify($status, CODEX_PURIFIER_CONVERT_HTML) .'</td>';
                if ( $field->isStandardField() ) {
                    // For standard, we can't delete them - Only unused them
                    $html .= "\n<td align =\"center\">-</td>";
                } else {
                    $html .= "\n<td align =\"center\"><a href=\"/tracker/admin/?func=field_delete&group_id=".(int)$this->Group->getID()."&atid=".(int)$this->getID()."&field_id=".(int)$field->getID()."\"><img src=\"".util_get_image_theme("ic/trash.png")."\" border=\"0\" onClick=\"return confirm('".$Language->getText('tracker_include_type','warning_loose_data')."')\"></a></td>";
                }
            
                $html .= "<TR>";
                                        
                $iu++;
                
            }
        }
        // Now print the HTML table (for unused fields)
		if ($iu == 0) {
		    echo '<tr><td colspan="7"><center><b>'.$Language->getText('tracker_include_type','no_unused_field').'</b></center></tr>'.$html;  
		} else {
		    echo '<tr><td colspan="7"><center><b>'.$Language->getText('tracker_include_type','unused_field').'</b></center></tr>'.$html;  
		}

		echo '</TABLE>';
		echo '<hr>';
		
	}
	
	/**
	 *  Display the field type select box
	 *
	 *  @param data_type: the field data type (string, int, flat or date)
	 *  @param display_type: the field display type (select box, text field, ...)
	 *  @param form_name: the HTTP form name
	 *
	 *  @return void
	 */
	function displayFieldType($data_type,$display_type,$form_name) {
	  global $Language;
		
		$af = new ArtifactField();
		
		echo '<script language="JavaScript">

			  function onChangeFieldType(form) {
			  		switch ( form.field_type.value ) {
			  		// Select Box
			  		case "1":
			  			form.data_type.value = '.(int)$af->DATATYPE_INT.';
					  	form.display_type.value = "SB";
					  	form.display_size.value = "N/A";
					  	break;
			  		// Multi Select Box
			  		case "2":
			  			form.data_type.value = '.(int)$af->DATATYPE_INT.';
					  	form.display_type.value = "MB";
					  	form.display_size.value = "N/A";
					  	break;
			  		// TextField
			  		case "3":
			  			form.data_type.value = '.(int)$af->DATATYPE_TEXT.';
					  	form.display_type.value = "TF";
					  	form.display_size.value = "N/A";
					  	break;
			  		// TextArea
			  		case "4":
			  			form.data_type.value = '.(int)$af->DATATYPE_TEXT.';
					  	form.display_type.value = "TA";
					  	form.display_size.value = "60/7";
					  	break;
			  		// DateField
			  		case "5":
			  			form.data_type.value = '.(int)$af->DATATYPE_DATE.';
					  	form.display_type.value = "DF";
					  	form.display_size.value = "N/A";
					  	break;
			  		// FloatField
			  		case "6":
			  			form.data_type.value = '.(int)$af->DATATYPE_FLOAT.';
					  	form.display_type.value = "TF";
					  	form.display_size.value = "N/A";
					  	break;
			  		// IntegerField
			  		case "7":
			  			form.data_type.value = '.(int)$af->DATATYPE_INT.';
					  	form.display_type.value = "TF";
					  	form.display_size.value = "N/A";
					  	break;
					default:
						alert("Unknow field type!");
						break;
			  		}
			  }

			  </script>
			 ';

        echo '<select name="field_type" onChange="onChangeFieldType('.$form_name.')">';
		if ( $data_type&&$display_type ) {
			$selected = "";
			if ( ($data_type == $af->DATATYPE_INT || $data_type == $af->DATATYPE_USER)
				 &&($display_type == "SB") ) 
				$selected = " selected";
			echo '<option value="1"'.$selected.'>'.$Language->getText('tracker_include_type','sb').'</option>';
			
			$selected = "";
			if ( ($data_type == $af->DATATYPE_INT || $data_type == $af->DATATYPE_USER)
				 &&($display_type == "MB") ) 
				$selected = " selected";
			echo '<option value="2"'.$selected.'>'.$Language->getText('tracker_include_type','mb').'</option>';
			
			$selected = "";
			if ( ($data_type == $af->DATATYPE_TEXT)
				 &&($display_type == "TF") ) 
				$selected = " selected";
			echo '<option value="3"'.$selected.'>'.$Language->getText('tracker_include_type','tf').'</option>';
			
			$selected = "";
			if ( ($data_type == $af->DATATYPE_TEXT)
				 &&($display_type == "TA") ) 
				$selected = " selected";
			echo '<option value="4"'.$selected.'>'.$Language->getText('tracker_include_type','ta').'</option>';

			$selected = "";
			if ( ($data_type == $af->DATATYPE_DATE)
				 &&($display_type == "DF") ) 
				$selected = " selected";
			echo '<option value="5"'.$selected.'>'.$Language->getText('tracker_include_type','df').'</option>';

			$selected = "";
			if ( ($data_type == $af->DATATYPE_FLOAT)
				 &&($display_type == "TF") ) {
				$selected = " selected";
			}
			echo '<option value="6"'.$selected.'>'.$Language->getText('tracker_include_type','ff').'</option>';

			$selected = "";
			if ( ($data_type == $af->DATATYPE_INT)
				 &&($display_type == "TF") ) 
				$selected = " selected";
			echo '<option value="7"'.$selected.'>'.$Language->getText('tracker_include_type','if').'</option>';

		} else {
			echo '
			<option value="1">'.$Language->getText('tracker_include_type','sb').'</option>
			<option value="2">'.$Language->getText('tracker_include_type','mb').'</option>
			<option value="3">'.$Language->getText('tracker_include_type','tf').'</option>
			<option value="4">'.$Language->getText('tracker_include_type','ta').'</option>
			<option value="5">'.$Language->getText('tracker_include_type','df').'</option>
			<option value="6">'.$Language->getText('tracker_include_type','ff').'</option>
			<option value="7">'.$Language->getText('tracker_include_type','if').'</option>';
		}
        echo '</select>';
	}

	/**
	 *  Display the field usage add or update form
	 *
	 *  @param func: field_create or field_update
	 *  @param field_id: the field id
	 *  @param field_name: the field name
	 *  @param description: the field description
	 *  @param label: the field label
	 *  @param data_type: the field data type (string, int, flat or date)
	 *  @param default_value: the default value
	 *  @param display_type: the field display type (select box, text field, ...)
	 *  @param display_size: the field display size
	 *  @param rank_on_screen: rank on screen
	 *  @param empty_ok: allow empty fill
	 *  @param keep_history: keep in the history
	 *  @param special: is the field has special process
	 *  @param use_it: this field is used or not
	 *  @param show_use: boolean - display the checkbox for using or not this field
     *  @param fieldset_id: int - the field set id that the field belong to
	 *
	 *  @return void
	 */
	function displayFieldUsageForm($func="field_create",$field_id=false,$field_name=false,$description=false,$label=false,$data_type=false,$default_value=false,$display_type=false,
								   $display_size=false,$rank_on_screen=false,
								   $empty_ok=false,$keep_history=false,$special=false,$use_it=false,$show_use=false, $fieldset_id=false) {
		global $art_field_fact,$Language;
		$hp = CodeX_HTMLPurifier::instance();
		$field = $art_field_fact->getFieldFromId($field_id);

		$af = new ArtifactField();

		if ( $func == "field_create" ) {
			echo '<h3>'.$Language->getText('tracker_include_type','create_field').'</h3>';
			echo '
			  <form name="form_create" method="/tracker/admin/index.php">
			  <input type="hidden" name="func" value="'. $hp->purify($func, CODEX_PURIFIER_CONVERT_HTML) .'">
			  <input type="hidden" name="group_id" value="'.(int)$this->Group->getID().'">
			  <input type="hidden" name="atid" value="'.(int)$this->getID().'">
			  <input type="hidden" name="field_id" value="">
			  <input type="hidden" name="field_name" value="">
			  <input type="hidden" name="data_type" value="'.(int)$af->DATATYPE_INT.'">
			  <input type="hidden" name="display_type" value="SB">';
		} else {
			echo "<h3>".$Language->getText('tracker_include_type','upd_label', $hp->purify(SimpleSanitizer::unsanitize($label), CODEX_PURIFIER_CONVERT_HTML) )."</h3>";
			echo '
			  <form name="form_create" method="/tracker/admin/index.php">
			  <input type="hidden" name="func" value="'. $hp->purify($func, CODEX_PURIFIER_CONVERT_HTML) .'">
			  <input type="hidden" name="group_id" value="'.(int)$this->Group->getID().'">
			  <input type="hidden" name="atid" value="'.(int)$this->getID().'">
			  <input type="hidden" name="field_id" value="'.(int)$field_id.'">
			  <input type="hidden" name="field_name" value="'. $hp->purify(SimpleSanitizer::unsanitize($field_name), CODEX_PURIFIER_CONVERT_HTML) .'">
			  <input type="hidden" name="data_type" value="'. $hp->purify($data_type, CODEX_PURIFIER_CONVERT_HTML) .'">
			  <input type="hidden" name="display_type" value="'. $hp->purify($display_type, CODEX_PURIFIER_CONVERT_HTML) .'">';
		}
		
		if ( $field && $field->isStandardField() ) {
			echo '<p><i>'.$Language->getText('tracker_include_type','imp_note', $hp->purify(SimpleSanitizer::unsanitize($field->getLabel()), CODEX_PURIFIER_CONVERT_HTML) ).'</i></p>';
		}
	
		echo '
		  <b>'.$Language->getText('tracker_include_type','field_ident').'</b> ';
		echo  help_button('TrackerAdministration.html#CreationandModificationofaTrackerField');
		echo '
                              <p>'.$Language->getText('tracker_include_report','field_label').': <font color="red">*</font> ';
		      
		if ( $label ) {
			echo '<input type="text" name="label" size="30" maxlength="40" value="'. $hp->purify(SimpleSanitizer::unsanitize($label), CODEX_PURIFIER_CONVERT_HTML) .'">';
		} else {
			echo '<input type="text" name="label" size="30" maxlength="40">';
		}
		
		echo '<p>'.$Language->getText('tracker_include_artifact','desc').': ';
		            
		echo '<input type=text name="description" size="70" maxlength="255" value="'. $hp->purify(SimpleSanitizer::unsanitize($description), CODEX_PURIFIER_CONVERT_HTML) .'">';

		
		echo '
		  <p>'.$Language->getText('tracker_include_type','field_type').': <font color="red">*</font>&nbsp;';
		

		//be more conservative for semi-standard fields like assigned_to ...
		if ( $field && 
		     !user_is_super_user() &&
		     ($field->isStandardField() ||
		      $field->getName() == "assigned_to" ||
		      $field->getName() == "multi_assigned_to")) {
			echo $field->getLabelFieldType();
		} else {
			$this->displayFieldType($data_type,$display_type,"form_create");
		}
		
		echo '			
		  <p><b>'.$Language->getText('tracker_include_type','display_info').' </b>';
		echo  help_button('TrackerAdministration.html#CreationandModificationofaTrackerField');
		echo'
                                <table width="100%" border="0" cellpadding="5" cellspacing="0">
		    <tr> 
		      <td colspan="2">'.$Language->getText('tracker_include_type','display_size').':&nbsp;';
		      
		if ( $display_size ) {
		  echo '<input type="text" name="display_size" size="7" maxlength="7" value="'. $hp->purify($display_size, CODEX_PURIFIER_CONVERT_HTML) .'">';
		} else {
		  echo '<input type="text" name="display_size" size="7" maxlength="7" value="N/A">';
		}
			    
	    echo '
		      </td>
            </tr>
            <tr>
              <td width="30%" >'.$Language->getText('tracker_include_type','fieldset').':&nbsp';
              $this->displayFieldSetDropDownList($this->getID(), $fieldset_id);
        echo '</td>
		      <td>'.$Language->getText('tracker_include_type','rank_screen').':&nbsp;';
		      
		if ( $rank_on_screen ) {
	        echo '<input type="text" name="rank_on_screen" size="5" maxlength="5" value="'. $hp->purify($rank_on_screen, CODEX_PURIFIER_CONVERT_HTML) .'">';
	    } else {
	        echo '<input type="text" name="rank_on_screen" size="5" maxlength="5">';
	    }
	    
	    echo '
		      </td>
		    </tr>
		  </table>
		  <b>'.$Language->getText('tracker_include_type','misc').'</b>
		  <table width="100%" border="0" cellpadding="5" cellspacing="0">
		    <tr> 
		      <td width="30%">'.$Language->getText('tracker_include_type','allow_empty').': ';
		
	    if ( $field && $field->isStandardField() && !user_is_super_user()) {
			if ( $empty_ok ) {
				echo $Language->getText('global','yes');
			    echo '<input type="hidden" name="empty_ok" value="1">';
			} else {
				echo $Language->getText('global','no');
			    echo '<input type="hidden" name="empty_ok" value="0">';
			}
		} else {
			if ( $empty_ok ) {
				echo '<input type="checkbox" name="empty_ok" value="1" checked>';
			} else {
				echo '<input type="checkbox" name="empty_ok" value="1">';
			}
		}
				
		echo '
		        </td><td>'.$Language->getText('tracker_include_type','keep_change_history').': ';
		
		if ( !$this->userIsAdmin()) {
			if ( $keep_history ) {
				echo $Language->getText('global','yes');
			    echo '<input type="hidden" name="keep_history" value="1">';
			} else {
				echo $Language->getText('global','no');
			    echo '<input type="hidden" name="keep_history" value="0">';
			}
		} else {
			if ( $keep_history ) {
				echo '<input type="checkbox" name="keep_history" value="1" checked>';
			} else {
				echo '<input type="checkbox" name="keep_history" value="1">';
			}
		}
				
		echo '
		      </td></tr>';
		      
		if ( $show_use ) {
		    echo  '
		      <tr><td>';
			echo $Language->getText('tracker_include_type','use_field').': ';
			if ( $use_it == 1 ) {
			  //be more conservative for semi-special fields like assigned_to ...
			  if ( $field && $field->isSpecial() && ($field->getName() != "comment_type_id") && !user_is_super_user()) {
			    echo $Language->getText('global','yes').'<input type="hidden" name="use_it" value="1">';
			  } else {
			    echo '<input type="checkbox" name="use_it" value="1" checked>';
			  }
			} else {
			  if ( $field && $field->isSpecial() && ($field->getName() != "comment_type_id") && !user_is_super_user()) {
			    echo $Language->getText('global','no').'<input type="hidden" name="use_it" value="0">';
			  } else {
			    echo '<input type="checkbox" name="use_it" value="1">';
			  }
			}
                        echo '
                      </td><td><a href="/tracker/admin/?group_id='.(int)$this->Group->getID().'&atid='.(int)$this->getID().'&func=permissions&perm_type=fields&selected_id='.(int)$field_id.'&group_first=0">'.$Language->getText('tracker_include_type','edit_field_perm').'</a>';

		    echo  '
		      </td></tr>';
		} else {
			echo '<input type="hidden" name="use_it" value="1">';
		}

		if ( $special ) {
			echo '<input type="hidden" name="special" value="1">';
		} else {
			echo '<input type="hidden" name="special" value="0">';
		}
		
		echo '
		  </table><p>';
		  
		if ( $func == "field_create" ) {
			echo '<input type="submit" name="Submit" value="'.$Language->getText('global','btn_create').'">';
		} else {
			echo '<input type="submit" name="Submit" value="'.$Language->getText('global','btn_update').'">';
		}
		
		echo '    
		  </p>
		</form>
		<p><font color="red">*</font>: '.$Language->getText('tracker_include_type','fields_requ').'</p>';
		
	}

	/**
	 *  Display the field values list for editing the values
	 *
	 *  @return void
	 */
	function displayFieldValuesEditList() {
        $hp = CodeX_HTMLPurifier::instance();
		global $ath,$art_field_fact,$art_fieldset_fact,$Language;
		
		echo '<p>'.$Language->getText('tracker_include_report','mod');
		
		
		// Show all the fields currently available in the system
		$i=0;
		$title_arr=array();
		$title_arr[]=$Language->getText('tracker_include_report','field_label');
		$title_arr[]=$Language->getText('tracker_include_artifact','desc');
        $title_arr[]=$Language->getText('tracker_include_type','fieldset');
		
		echo html_build_list_table_top ($title_arr);
		
		// Build HTML ouput for  Used fields
		$iu=0;
		//$fields = $art_field_fact->getAllUsedFields();
        $fieldsets = $art_fieldset_fact->getAllFieldSetsContainingUsedFields();
		$html = "";
		while (list($fieldset_id, $fieldset) = each($fieldsets)) {
            
            $html .= '<tr class="fieldset_separator"><td colspan="3">'. $hp->purify(SimpleSanitizer::unsanitize($fieldset->getLabel()), CODEX_PURIFIER_CONVERT_HTML) .'</td></tr>';
            
            $fields = $fieldset->getAllUsedFields();
            while (list($field_name,$field) = each($fields)) {
            
              // Special case for special fields (excluded comment_type_id)
              if ( (($field->getName() != "comment_type_id")&&($field->isSpecial())) ||
                   (($field->getName() == "status_id") && !user_is_super_user()) ) {
                continue;
              }
                
                $html .= '<TR class="'. 
                    util_get_alt_row_color($iu) .'">';
            
                $html .= '<TD><A HREF="?group_id='.(int)$this->Group->getID()."&atid=".(int)$this->getID().
                '&func=display_field_values&field_id='.(int)$field->getID().'">'.
                $hp->purify(SimpleSanitizer::unsanitize($field->getLabel()), CODEX_PURIFIER_CONVERT_HTML).'</A></td>'.
                "\n<td>". $hp->purify(SimpleSanitizer::unsanitize($field->getDescription()), CODEX_PURIFIER_BASIC, $this->getGroupId()) .'</td>';
                
                $html .= '<td>'. $hp->purify(SimpleSanitizer::unsanitize($fieldset->getLabel()), CODEX_PURIFIER_CONVERT_HTML) .'</td>';
            
                $html .= "<TR>";
    
                $iu++;
            }
		}
		// Now print the HTML table
		if ($iu == 0) {
		    echo '<tr><td colspan="4"><center><b>'.$Language->getText('tracker_include_type','no_used_field').'</b></center></tr>';
		} else {
			echo $html;
		}

		echo '</TABLE>';
		echo '<hr>';
		
	}

	/**
	 *  Display the value function form
	 *
	 *  @param field_id: the field id to edit
	 *  @param value_function: the value function to bind to
	 *  @param or_label: display an additionnal label to display "Or ..."
	 *
	 *  @return void
	 */
	function displayValueFunctionForm($field_id,$value_function,$or_label="") {
        $hp = CodeX_HTMLPurifier::instance();
	  global $Language;
	    if ( $or_label ) {
	    	echo '<h3>'. $hp->purify($or_label, CODEX_PURIFIER_CONVERT_HTML) .' '.$Language->getText('tracker_include_type','bind_to_list').' ';
	    } else {
	    	echo '<h3>'.$Language->getText('tracker_include_type','bind_to_list').' ';
	    }
	    echo  help_button('TrackerAdministration.html#TrackerBindingFieldToValueList').'</h3>';
	

	    echo '
	      <FORM ACTION="" METHOD="POST">
	      <INPUT TYPE="HIDDEN" NAME="func" VALUE="update_binding">
	      <INPUT TYPE="HIDDEN" NAME="field_id" VALUE="'.(int)$field_id.'">
	      <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.(int)$this->Group->getID().'">
	      <INPUT TYPE="HIDDEN" NAME="atid" VALUE="'.(int)$this->getID().'">
	      '.$Language->getText('tracker_include_type','bind_to').': &nbsp; 
	      <select multiple name="value_function[]">
	          <option value="">'.$Language->getText('global','none').'</option>';

	
	    $selected = "";
	    if ( $value_function && in_array("artifact_submitters",$value_function)) {
	      $selected = " selected";
	    }
	    echo '<option value="artifact_submitters"'.$selected.'>'.$Language->getText('tracker_include_type','submitters').'</option>';
	    
	    $selected = "";
	    $ugroup_res = ugroup_db_get_ugroup($GLOBALS['UGROUP_PROJECT_MEMBERS']);
	    $name = util_translate_name_ugroup(db_result($ugroup_res, 0, 'name'));
	    if ( $value_function && in_array("group_members",$value_function)) {
	      $selected = " selected";
	    }
	    echo '<option value="group_members"'.$selected.'>'. $hp->purify($name, CODEX_PURIFIER_CONVERT_HTML) .'</option>';
	    
	    $selected = "";
	    $ugroup_res = ugroup_db_get_ugroup($GLOBALS['UGROUP_PROJECT_ADMIN']);
	    $name = util_translate_name_ugroup(db_result($ugroup_res, 0, 'name'));
	    if ( $value_function && in_array("group_admins",$value_function)) {
	      $selected = " selected";
	    }
	    echo '<option value="group_admins"'.$selected.'>'. $hp->purify($name, CODEX_PURIFIER_CONVERT_HTML) .'</option>';
	    
	    $selected = "";
	    $ugroup_res = ugroup_db_get_ugroup($GLOBALS['UGROUP_TRACKER_ADMIN']);
	    $name = util_translate_name_ugroup(db_result($ugroup_res, 0, 'name'));
	    if ( $value_function && in_array("tracker_admins",$value_function)) {
	      $selected = " selected";
	    }
	    echo '<option value="tracker_admins"'.$selected.'>'. $hp->purify($name, CODEX_PURIFIER_CONVERT_HTML) .'</option>';
	    
	    
	    $ugroup_res = ugroup_db_get_existing_ugroups(100);
	    $rows = db_numrows($ugroup_res);
	    for($i=0;$i<$rows;$i++) {
	      $ug = db_result($ugroup_res, $i,'ugroup_id');
	      $selected = "";
	      if (($ug == $GLOBALS['UGROUP_NONE']) || 
		  ($ug == $GLOBALS['UGROUP_ANONYMOUS']) || 
		  ($ug == $GLOBALS['UGROUP_PROJECT_MEMBERS']) || 
		  ($ug == $GLOBALS['UGROUP_PROJECT_ADMIN']) || 
		  ($ug == $GLOBALS['UGROUP_TRACKER_ADMIN'])) { continue; }
	      
	      $ugr="ugroup_".$ug;
	      if ( $value_function && in_array($ugr,$value_function)) {
		$selected = " selected";
	      }
	      echo '<option value="'.$ugr.'"'.$selected.'>'. $hp->purify(util_translate_name_ugroup(db_result($ugroup_res, $i, 'name')), CODEX_PURIFIER_CONVERT_HTML) .'</option>';	      
	    }
	    
	    if ($this->Group->getID() != 100) {
	      $ugroup_res = ugroup_db_get_existing_ugroups($this->Group->getID());
	      $rows = db_numrows($ugroup_res);
	      for($i=0;$i<$rows;$i++) {
		$selected = "";
		$ug = db_result($ugroup_res, $i,'ugroup_id');
		$ugr="ugroup_".$ug;
	        if ( $value_function && in_array($ugr,$value_function)) {
		    $selected = " selected";
	        }
		echo '<option value="'.$ugr.'"'.$selected.'>'. $hp->purify(util_translate_name_ugroup(db_result($ugroup_res, $i, 'name')), CODEX_PURIFIER_CONVERT_HTML) .'</option>';
	      }
	    }
	    echo '
		        </select>
	      &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('tracker_include_type','bind').'">
	      </FORM>';
	    
	}
	
	/**
	 *  Display the field value form
	 *
	 *  @param $func: value_create or value_update
	 *  @param field_id: the field id to edit
	 *  @param value_id: the value id
	 *  @param value: the value 
	 *  @param order_id: rank
	 *  @param status: the field value status (Visible or Hidden)
	 *  @param description: the field value description
	 *
	 *  @return void
	 */
	function displayFieldValueForm($func,$field_id,$value_id=false,$value=false,$order_id=false,$status=false,$description=false) {
		global $Language;
		$hp = CodeX_HTMLPurifier::instance();
        
		if ( $func == "value_create" ) {
			echo '<h3>'.$Language->getText('tracker_include_type','create_value').' '.help_button('TrackerAdministration.html#TrackerCreatingaTrackerFieldValue').'</h3>';
		} else {
			echo '<h3>'.$Language->getText('tracker_include_type','update_value').' '.help_button('TrackerAdministration.html#TrackerUpdatingaTrackerFieldValue').'</h3>';
		}
					
		echo '
	      <FORM ACTION="" METHOD="POST">
	      <INPUT TYPE="HIDDEN" NAME="func" VALUE="'. $hp->purify($func, CODEX_PURIFIER_CONVERT_HTML) .'">
	      <INPUT TYPE="HIDDEN" NAME="field_id" VALUE="'.(int)$field_id.'">
	      <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.(int)$this->Group->getID().'">
	      <INPUT TYPE="HIDDEN" NAME="atid" VALUE="'.(int)$this->getID().'">';

		if ( $status == "P" ) {
			echo '<p><i>'.$Language->getText('tracker_include_type','note_standard').'</i></p>';
		}

		if ( $func == "value_create" ) {
		    if ( $value_id ) {
		      	echo '<INPUT TYPE="hidden" NAME="value_id" VALUE="'.(int)$value_id.'">';
		    } else {
		      	echo '<INPUT TYPE="hidden" NAME="value_id" VALUE="">';
		    }
		} else {
	      	echo '<INPUT TYPE="hidden" NAME="value_id" VALUE="'.(int)$value_id.'">';
		}

	    echo '  
		  <table width="100%" border="0" cellpadding="5" cellspacing="0">
		    <tr> 
		      <td width="45%">Value: <font color="red">*</font>&nbsp;';

	    if ( $value ) {
	      	echo '<INPUT TYPE="TEXT" NAME="value" VALUE="'. $hp->purify(SimpleSanitizer::unsanitize($value), CODEX_PURIFIER_CONVERT_HTML) .'" SIZE="30" MAXLENGTH="60">';
	    } else {
	      	echo '<INPUT TYPE="TEXT" NAME="value" VALUE="" SIZE="30" MAXLENGTH="60">';
	    }
	    
	    echo '
	    	  </td>
		      <td width="25%">Rank:&nbsp;';
	      
	    if ( $order_id ) {
	    	echo '<INPUT TYPE="TEXT" NAME="order_id" VALUE="'. $hp->purify($order_id, CODEX_PURIFIER_CONVERT_HTML) .'" SIZE="6" MAXLENGTH="6">';
		} else {
	    	echo '<INPUT TYPE="TEXT" NAME="order_id" VALUE="" SIZE="6" MAXLENGTH="6">';
		}
	    
	    echo '</td>
		      <td width="30%">';
	       
		if ( $func == "value_update" ) {
			if (( $status == "P" ) && ( $this->Group->getID() != 100 ) ) {
                            // Can't change 'Permanent' status unless you're working on the tracker templates (group 100)
                            echo $Language->getText('global','status').': '.$Language->getText('tracker_include_type','permanent');
                            echo '<input type="hidden" name="status" value="P">';
			} else {
				echo $Language->getText('global','status').':&nbsp;
				<select name="status">';
				if ( $status ) {
					$selected = "";
					if ( $status == "A" ) 
						$selected = " selected";
					echo '<option value="A"'.$selected.'>'.$Language->getText('tracker_include_type','active').'</option>';
		
					$selected = "";
					if ( $status == $this->FIELD_VALUE_STATUS_HIDDEN ) 
						$selected = " selected";
					echo '<option value="'.$this->FIELD_VALUE_STATUS_HIDDEN.'"'.$selected.'>'.$Language->getText('tracker_include_type','hidden').'</option>';

                                        if ($this->Group->getID() == 100 ) {
                                            $selected = "";
                                            if ( $status == $this->FIELD_VALUE_STATUS_PERMANENT  ) 
						$selected = " selected";
                                            echo '<option value="'.$this->FIELD_VALUE_STATUS_PERMANENT.'"'.$selected.'>'.$Language->getText('tracker_include_type','permanent').'</option>';
                                        }
		
				} else {
					echo '				
				          <option value="A" selected>'.$Language->getText('tracker_include_type','active').'</option>
				          <option value="H">'.$Language->getText('tracker_include_type','hidden').'</option>;';
                                        if ($this->Group->getID() == 100 ) {
                                            echo '				
				          <option value="P">'.$Language->getText('tracker_include_type','permanent').'</option>';
                                        }
				}
				echo '
				</select> ';
			}
		} else {
			echo '&nbsp;</td>';
		}
	
		     
		echo '
		  </tr>
		  <tr>
		     <td colspan="4">'.$Language->getText('tracker_include_artifact','desc').':<BR>';
	     
	    if ( $description ) {
	    	echo '<textarea name="description" rows="2" cols="65">'. $hp->purify(SimpleSanitizer::unsanitize($description), CODEX_PURIFIER_CONVERT_HTML) .'</textarea>';
	   	} else {
	    	echo '<textarea name="description" rows="2" cols="65"></textarea>';
		}
	
		echo '</td>
			</tr>
			</table>
			<p>';
	      
		if ( $func == "value_create" ) {
			echo '<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_create').'">';
		} else {
			echo '<INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('global','btn_update').'">';
		}

		echo '    
		  </p>
		</form>
		<p><font color="red">*</font>: '.$Language->getText('tracker_include_type','fields_requ').'</p>';
	}
	
	/**
	 *  Display the field values list for a field
	 *
	 *  @param field_id: the field id
	 *
	 *  @return void
	 */
	function displayFieldValuesList($field_id) {
		global $ath,$art_field_fact,$Language;
		$hp = CodeX_HTMLPurifier::instance();
		
		$field = $art_field_fact->getFieldFromId($field_id);
		if ( !$field ) {
			return;
		}

		$values = $field->getFieldValues ($this->getID(), array('A','P'));
		$rows = db_numrows($values);
		
		if (!$values || ($rows == 0)) {
		    echo "\n<H3>".$Language->getText('tracker_include_type','no_values')."</H3>";
		    return;
		} else {
		    echo '<h3>'.$Language->getText('tracker_include_type','exist_values').'</h3>';
		    echo '<p>'.$Language->getText('tracker_include_report','mod');
		}

		
		// Show all the fields currently available in the system
		$i=0;
		$title_arr=array();
		if ( $field->getName() == "severity" ) {
			$title_arr[]=$Language->getText('tracker_include_report','id');
		}
		$title_arr[]=$Language->getText('tracker_include_type','value_label');
		$title_arr[]=$Language->getText('tracker_include_artifact','desc');
		$title_arr[]=$Language->getText('tracker_include_type','rank');
		$title_arr[]=$Language->getText('global','status');
		$title_arr[]=$Language->getText('tracker_include_canned','delete');
		
		echo html_build_list_table_top ($title_arr);
		
		// Build HTML ouput for  Used fields
		$iu=0;
		$html = "";

		while ($row = db_fetch_array($values)) {
		
		    $rank = $row['order_id']?$row['order_id']:"-";
		    $status = $this->getLabelValueStatus($row['status']);
		
			$html .= '<TR class="'. 
			    util_get_alt_row_color($iu) .'">';
		
			if ( $field->getName() == "severity" ) {
				$html .= '<TD align="center">'. $hp->purify($row['value_id'], CODEX_PURIFIER_CONVERT_HTML) .'</TD>';
			}
		    $html .= '<TD><A HREF="?group_id='.(int)$this->Group->getID()."&atid=".(int)$this->getID().
			'&func=display_field_value&field_id='.(int)$field->getID().'&value_id='.(int)$row['value_id'].'">'.
			$hp->purify(SimpleSanitizer::unsanitize($row['value']), CODEX_PURIFIER_CONVERT_HTML).'</A></td>'.
			"\n<td>". $hp->purify(SimpleSanitizer::unsanitize($row['description']), CODEX_PURIFIER_BASIC, $this->getGroupId()) .'</td>'.
			"\n<td align =\"center\">". $hp->purify($rank, CODEX_PURIFIER_CONVERT_HTML) .'</td>'.
			"\n<td align =\"center\">". $hp->purify($status, CODEX_PURIFIER_CONVERT_HTML) .'</td>';
			
			if (( $row['status'] == "P" || $field->getName() == "severity" )
                            &&(!user_is_super_user())){
				// Unable to delete Permanent values, except for values in the tracker templates (for CodeX admins)
		    	$html .= "\n<td align =\"center\">-</td>";
			} else {
		    	$html .= "\n<td align =\"center\"><a href=\"/tracker/admin/?func=value_delete&group_id=".(int)$this->Group->getID()."&atid=".(int)$this->getID()."&field_id=".(int)$field->getID()."&value_id=".(int)$row['value_id']."\"><img src=\"".util_get_image_theme("ic/trash.png")."\" border=\"0\" onClick=\"return confirm('".$Language->getText('tracker_include_type','del_value')."')\"></a></td>";
		    }
		
			$html .= "<TR>";
					    			
			$iu++;
		}
		
		// Now print the HTML table
		if ($iu == 0) {
		    echo '<tr><td colspan="4"><center><b>'.$Language->getText('tracker_include_type','no_active_val').'</b></center></tr>'.$html;  
		} else {
		    echo '<tr><td colspan="4"><center><b>'.$Language->getText('tracker_include_type','active_val').'</b></center></tr>'.$html;  
		}

		// Build HTML ouput for Unused fields
		$iu=0;
		$values = $field->getFieldValues ($this->getID(), array('H'));
		$html = "";
		
		while ($row = db_fetch_array($values)) {
		
		    $rank = $row['order_id']?$row['order_id']:"-";
		    $status = $this->getLabelValueStatus($row['status']);
		
			$html .= '<TR class="'. 
			    util_get_alt_row_color($iu) .'">';
		
			if ( $field->getName() == "severity" ) {
				$html .= '<TD align="center">'.(int)$row['value_id'].'</TD>';
			}

		    $html .= '<TD><A HREF="?group_id='.(int)$this->Group->getID()."&atid=".(int)$this->getID().
			'&func=display_field_value&field_id='.(int)$field->getID().'&value_id='.(int)$row['value_id'].'">'.
			$row['value'].'</A></td>'.
			"\n<td>". $hp->purify($row['description'], CODEX_PURIFIER_BASIC, $this->getGroupId()) .'</td>'.
			"\n<td align =\"center\">". $hp->purify($rank, CODEX_PURIFIER_CONVERT_HTML) .'</td>'.
			"\n<td align =\"center\">". $hp->purify($status, CODEX_PURIFIER_CONVERT_HTML) .'</td>';

			if ( $row['status'] == "P" || $field->getName() == "severity" ) {
				// Unable to delete Permanent values
		    	$html .= "\n<td align =\"center\">-</td>";
			} else {
		    	$html .= "\n<td align =\"center\"><a href=\"/tracker/admin/?func=value_delete&group_id=".(int)$this->Group->getID()."&atid=".(int)$this->getID()."&field_id=".(int)$field->getID()."&value_id=".(int)$row['value_id']."\"><img src=\"".util_get_image_theme("ic/trash.png")."\" border=\"0\" onClick=\"return confirm('".$Language->getText('tracker_include_type','del_value')."')\"></a>";
		    }
		
			$html .= "<TR>";
					    			
			$iu++;
		}
		
		// Now print the HTML table
		if ($iu == 0) {
		    echo '<tr><td colspan="4"><center><b>'.$Language->getText('tracker_include_type','no_hidden_val').'</b></center></tr>'.$html;
		} else {
		    echo '<tr><td colspan="4"><center><b>'.$Language->getText('tracker_include_type','hidden_val').'</b></center></tr>'.$html;  
		}

		echo '</TABLE>';
		echo '<hr>';
		
	}


	/* 
	 *
	 * displayNotificationForm
	 *
	 * return void
	 *
	 *
	 * "
	 */
	
	function displayNotificationForm($user_id) {
	  global $Language;
      $hp = CodeX_HTMLPurifier::instance();
		// By default it's all 'yes'
		for ($i=0; $i<$this->num_roles; $i++) {
		    $role_label = $this->arr_roles[$i]['role_label'];
		    for ($j=0; $j<$this->num_events; $j++) {
				$event_label = $this->arr_events[$j]['event_label'];
				$arr_notif[$role_label][$event_label] = 1;
				//echo "[$role_label][$event_label] = 1<br>";
		    }
		}
		
		$res_notif = $this->getNotificationWithLabels($user_id);
		while ($arr = db_fetch_array($res_notif)) {
		    $arr_notif[$arr['role_label']][$arr['event_label']] = $arr['notify'];
		}
		
		$group = $this->getGroup();
		$group_artifact_id = $this->getID();
		$group_id = $group->getGroupId();

		echo '<H2>'.$Language->getText('tracker_import_admin','tracker').' \'<a href="/tracker/admin/?group_id='.(int)$group_id.'&atid='.$group_artifact_id.'">'. $hp->purify(SimpleSanitizer::unsanitize($this->getName()), CODEX_PURIFIER_CONVERT_HTML) .'</a>\' - '.$Language->getText('tracker_include_type','mail_notif').'</h2>';
		// Build Wachees UI
		$res = $this->getWatchees($user_id);
		$arr_watchees = array();
		while ($row_watchee = db_fetch_array($res)) {
		    $arr_watchees[] = user_getname($row_watchee['watchee_id']);
		}
		$watchees = join(',',$arr_watchees);
		
		echo '
		<FORM action="/tracker/admin" method="get">
		<INPUT type="hidden" name="func" value="notification">
		<INPUT type="hidden" name="atid" value="'.(int)$this->getID().'">
		<INPUT type="hidden" name="group_id" value="'.(int)$group_id.'">';
		
		if ($this->userIsAdmin()) {
	    	echo '<h3><a name="ToggleEmailNotification"></a>'.$Language->getText('tracker_include_type','toggle_notification').' '.
			help_button('TrackerAdministration.html#ToggleEmailNotification').'</h3>';			        
			echo '
			<P>'.$Language->getText('tracker_include_type','toggle_notif_note').'<BR>
			<BR><INPUT TYPE="checkbox" NAME="stop_notification" VALUE="1" '.(($this->getStopNotification())?'CHECKED':'').'> '.$Language->getText('tracker_include_type','stop_notification');     
		} else if ($this->getStopNotification()) {
		    echo '<h3><a name="ToggleEmailNotification"></a>'.$Language->getText('tracker_include_type','notification_suspended').' '.
			help_button('TrackerAdministration.html#ToggleEmailNotification').'</h3>';			        
			echo '
			<P><b>'.$Language->getText('tracker_include_type','toggle_notif_warn').'</b><BR>';
		}
		
		echo '<h3><a name="GlobalEmailNotification"></a>'.$Language->getText('tracker_include_type','global_mail_notif').' '.
		help_button('TrackerAdministration.html#TrackerGlobalEmailNotification').'</h3>';
		
        $agnf =& new ArtifactGlobalNotificationFactory();
        $notifs = $agnf->getGlobalNotificationsForTracker($this->getID());
		if ($this->userIsAdmin()) {
		    echo '<p>'. $Language->getText('tracker_include_type','admin_note') .'</p>';
            if (count($notifs)) {
                foreach($notifs as $key => $nop) {
                    echo '<div>';
                    echo '<a href="?func=notification&amp;group_id='. (int)$group_id .'&amp;atid='. (int)$this->getId() .'&amp;action=remove_global&amp;global_notification_id='. (int)$notifs[$key]->getId() .'">'. $GLOBALS['Response']->getimage('ic/trash.png') .'</a> &nbsp;';
                    //addresses
                    echo '<input type="text" name="global_notification['. (int)$notifs[$key]->getId() .'][addresses]" value="'.  $hp->purify($notifs[$key]->getAddresses(), CODEX_PURIFIER_CONVERT_HTML)  .'" size="55" />';
                    //all_updates
                    echo '&nbsp;&nbsp;&nbsp;'. $Language->getText('tracker_include_type','send_all') .' ';
                    echo '<input type="hidden" name="global_notification['. (int)$notifs[$key]->getId() .'][all_updates]" value="0" />';
                    echo '<input type="checkbox" name="global_notification['. (int)$notifs[$key]->getId() .'][all_updates]" value="1" '. (($notifs[$key]->isAllUpdates())?'checked="checked"':'') .' />';
                    //check_permissions
                    echo '&nbsp;&nbsp;&nbsp;'. $Language->getText('tracker_include_type','check_perms') .' ';
                    echo '<input type="hidden" name="global_notification['. (int)$notifs[$key]->getId() .'][check_permissions]" value="0" />';
                    echo '<input type="checkbox" name="global_notification['. (int)$notifs[$key]->getId() .'][check_permissions]" value="1" '. (($notifs[$key]->isCheckPermissions())?'checked="checked"':'') .' />';
                    
                    echo '</div>';
                }
            }
            echo '<p><a href="?func=notification&amp;group_id='. (int)$group_id .'&amp;atid='. (int)$this->getId() .'&amp;action=add_global">'. $Language->getText('tracker_include_type','add') .'</a></p>';
		} else {
            $ok = false;
            if (count($notifs)) {
                reset($notifs);
                while(!$ok && (list($id,) = each($notifs))) {
                    $ok = $notifs[$id]->getAddresses();
                }
            }
            if ($ok) {
                echo $Language->getText('tracker_include_type','admin_conf');
                foreach($notifs as $key => $nop) {
                    if ($notifs[$key]->getAddresses()) {
                        echo '<div>'. $notifs[$key]->getAddresses() .'&nbsp;&nbsp;&nbsp; ';
                        echo $Language->getText('tracker_include_type','send_all_or_not',($notifs[$key]->isAllUpdates()?$Language->getText('global','yes'):$Language->getText('global','no')));
                        echo '</div>';
                    }
                }
            } else {
				echo $Language->getText('tracker_include_type','admin_not_conf');
            }
		}
		 
		
		echo '<h3>'.$Language->getText('tracker_include_type','perso_mail_notif').'</h3>';
		  
		if ($this->userIsAdmin()) {
		    // To watch other users you must have at least admin rights on the tracker
		    echo'
		<h4>'.$Language->getText('tracker_include_type','users_to_watch').' '.
		help_button('TrackerAdministration.html#TrackerWatchers').'</h4>
		<P>'.$Language->getText('tracker_include_type','backup_person').'
		<p><INPUT TYPE="TEXT" NAME="watchees" VALUE="'. $hp->purify($watchees, CODEX_PURIFIER_CONVERT_HTML) .'" SIZE="55" MAXLENGTH="255"><br></p>
		';
		
		    $res = $this->getWatchers($user_id);
		    $arr_watchers = array();
		    $watchers="";
                    while ($row_watcher = db_fetch_array($res)) {
				$watcher_name = user_getname($row_watcher['user_id']);
				$watchers .= '<a href="/users/'.urlencode($watcher_name).'">'. $hp->purify($watcher_name, CODEX_PURIFIER_CONVERT_HTML) .'</a>,';
		    }
		    $watchers = substr($watchers,0,-1); // remove extra comma at the end
		    
		    if ($watchers) {
				echo "<p>".$Language->getText('tracker_include_type','watchers', $hp->purify($watchers, CODEX_PURIFIER_CONVERT_HTML) );
		    } else {
				echo "<p>".$Language->getText('tracker_include_type','no_watcher');
		    }
		    echo '<br><br>';
		}
		
		// Build Role/Event table 
		// Rk: Can't use html_build_list_table_top because of the specific layout
		echo '<h4>'.$Language->getText('tracker_include_type','event_settings').' '.
		help_button('TrackerAdministration.html#TrackerEventRoleBasedEmailNotification').'</h4>
		              <P>'.$Language->getText('tracker_include_type','tune_settings');
		
		echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<table BORDER="0" CELLSPACING="1" CELLPADDING="2" class="small">
		<tr class="boxtitle">
		    <td colspan="'.(int)$this->num_roles.'" align="center" width="50%"><b>'.$Language->getText('tracker_include_type','role_is').'</b></td>
		    <td rowspan="2" width="50%"><b>&nbsp;&nbsp;&nbsp;'.$Language->getText('tracker_include_type','notify_me').'</b></td>
		</tr>';
		
		for ($i=0; $i<$this->num_roles; $i++) {
		    echo '<td align="center" width="10%"><b>'.$Language->getText('tracker_common_types',$this->arr_roles[$i]['short_description_msg'])."</b></td>\n";
		}
		echo "</tr>\n";
		
		for ($j=0; $j<$this->num_events; $j++) {
		    $event_id = $this->arr_events[$j]['event_id'];
		    $event_label = $this->arr_events[$j]['event_label'];
		    echo "<tr class=\"".util_get_alt_row_color($j)."\">\n";
		    for ($i=0; $i<$this->num_roles; $i++) {
				$role_id = $this->arr_roles[$i]['role_id'];
				$role_label = $this->arr_roles[$i]['role_label'];
				$cbox_name = 'cb_'.$role_id.'_'.$event_id;
				//echo "<BR>$role_label $role_id $event_label $event_id ".$arr_notif['$role_label']['$event_label'];
				if ( (($event_label == 'NEW_ARTIFACT') && ($role_label != 'ASSIGNEE') && ($role_label != 'SUBMITTER')) ||
					(($event_label == 'ROLE_CHANGE') && ($role_label != 'ASSIGNEE') && ($role_label != 'CC')) ) {
				    // if the user is not a member then the ASSIGNEE column cannot
				    // be set. If it's not an assignee or a submitter the new_artifact event is meaningless
				    echo '   <td align="center"><input type="hidden" name="'.$cbox_name.'" value="1">-</td>'."\n";
				} else {
				    echo '   <td align="center"><input type="checkbox" name="'.$cbox_name.'" value="1" '.
					($arr_notif[$role_label][$event_label] ? 'checked':'')."></td>\n";
				}
		    }
		    echo '   <td>&nbsp;&nbsp;&nbsp;'.$Language->getText('tracker_common_types',$this->arr_events[$j]['description_msg'])."</td>\n";
		    echo "</tr>\n";
		}
		
		echo'
		</table>
		
		<HR>
		<P align="center"><INPUT type="submit" name="submit" value="'.$Language->getText('tracker_include_artifact','submit').'">
		</FORM>';
			
		
	}

	/**
	 *  Display the default value form
	 *
	 *  @param field_id: the field id to edit
	 *  @param default_value: the default value
	 *
	 *  @return void
	 */
	function displayDefaultValueForm($field_id,$default_value) {
		global $ath,$art_field_fact,$Language;
		$hp = CodeX_HTMLPurifier::instance();
		$field = $art_field_fact->getFieldFromId($field_id);
		if ( !$field ) {
			return;
		}

		echo '<h3>'.$Language->getText('tracker_include_type','def_default').' '.help_button('TrackerAdministration.html#TrackerDefiningaDefaultFieldValue').'</h3>';
					
		echo '
	      <FORM ACTION="" METHOD="POST" name="artifact_form">
	      <INPUT TYPE="HIDDEN" NAME="func" VALUE="update_default_value">
	      <INPUT TYPE="HIDDEN" NAME="field_id" VALUE="'.(int)$field_id.'">
	      <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.(int)$this->Group->getID().'">
	      <INPUT TYPE="HIDDEN" NAME="atid" VALUE="'.(int)$this->getID().'">
		  <script type="text/javascript" src="/scripts/calendar_js.php"></script>';

		if ( $field->isSelectBox() || $field->isMultiSelectBox() ) {
			echo $Language->getText('tracker_include_type','val').': ';
			echo html_build_select_box ($field->getFieldValues($this->getID(),array('A','P')),"default_value",$default_value);
		} else if ( $field->isDateField() ) {
            echo '<input type="radio" name="default_date_type" value="current_date"'.($default_value==""?" checked=\"checked\"":"").'>'.$Language->getText('tracker_include_type','current_date').'</input><br />';
            echo '<input type="radio" name="default_date_type" value="selected_date"'.($default_value!=""?" checked=\"checked\"":"").'>'.$Language->getText('tracker_include_type','date_value').'</input> ';
            echo '<INPUT TYPE="text" name="default_value" size="10" MAXLENGTH="10" VALUE="'.($default_value?format_date("Y-m-j",$default_value,''):'').'">'.
			'<a href="javascript:show_calendar(\'document.artifact_form.default_value\', document.artifact_form.default_value.value,\''.util_get_css_theme().'\',\''.util_get_dir_image_theme().'\');">'.
			'<img src="'.util_get_image_theme("calendar/cal.png").'" width="16" height="16" border="0" alt="Click Here to Pick up a date"></a><br />';
		} else {
	      	echo $Language->getText('tracker_include_type','val').': <INPUT TYPE="text" NAME="default_value" VALUE="'. $hp->purify($default_value, CODEX_PURIFIER_CONVERT_HTML) .'">';
		}

		echo '
		<INPUT type="submit" name="submit" value="'.$Language->getText('global','btn_update').'">
		</form>';
	}

	/**
	 *  Display the default value form for fields having a value function (e.g. group_members, artifact_submitters)
	 *
	 *  @param field_id: the field id to edit
	 *  @param default_value: the default value
	 *  @param show_none,text_none,show_any,text_any,show_value: values used by html_build_select_box function
	 *
	 *  @return void
	 */
	function displayDefaultValueFunctionForm($field_id,$default_value,$show_none=true,$text_none=false,$show_any=false,$text_any=false,$show_value=false) {
		global $ath,$art_field_fact,$Language;
		
		if (!$text_any) $text_any=$Language->getText('global','any');
		if (!$text_none) $text_none=$Language->getText('global','none');

		$field = $art_field_fact->getFieldFromId($field_id);
		if ( !$field ) {
			return;
		}

		echo '<h3>'.$Language->getText('tracker_include_type','def_default').' '.help_button('TrackerAdministration.html#TrackerDefiningaDefaultFieldValue').'</h3>';
					
		echo '
	      <FORM ACTION="" METHOD="POST" name="artifact_form">
	      <INPUT TYPE="HIDDEN" NAME="func" VALUE="update_default_value">
	      <INPUT TYPE="HIDDEN" NAME="field_id" VALUE="'.(int)$field_id.'">
	      <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.(int)$this->Group->getID().'">
	      <INPUT TYPE="HIDDEN" NAME="atid" VALUE="'.(int)$this->getID().'">';

		//new stuff by MLS
		$field_value = $field->getDefaultValue();
		$predefinedValues = $field->getFieldPredefinedValues($this->getID());
	
		if ( $field->isSelectBox()) {
			echo html_build_select_box ($predefinedValues,"default_value",$default_value);
	   	} else if ($field->isMultiSelectBox() ) {
			echo html_build_multiple_select_box($predefinedValues,"default_value[]",$default_value,
							($field->getDisplaySize()!=""?$field->getDisplaySize():"6"),
							$show_none,$text_none,$show_any,$text_any,false,'',$show_value);
		} else {
			echo 'Why are we in this case ??';
		}

		echo '
		 &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <INPUT type="submit" name="submit" value="'.$Language->getText('global','btn_update').'">
		</form><hr>';
	}

	/**
	 *  Get tha label for a code status
	 *
	 *  @param status: the code status
	 *
	 *  @return string
	 */
	function getLabelValueStatus($status) {
	  global $Language;

		switch ( $status ) {
		case "P":
			return $Language->getText('tracker_include_type','permanent');
		case "A":
			return $Language->getText('tracker_include_type','active');
		case "H":
			return $Language->getText('tracker_include_type','hidden');
		default:
			return $Language->getText('tracker_include_type','unknown_stat').":".$status;
		}
	}

	/**
	 *  Display the reporting main page
	 *
	 *  @return void
	 */
	function reportingMainPage() {
        $hp = CodeX_HTMLPurifier::instance();
		global $art_field_fact,$Language;
	
		$this->header(array("title"=>$Language->getText('tracker_import_admin','tracker')." '". $hp->purify(SimpleSanitizer::unsanitize($this->getName()), CODEX_PURIFIER_CONVERT_HTML) ."' - ".$Language->getText('tracker_include_type','report_system'),
				  'help' => 'TrackerReporting.html'));
	
		echo '<H2>'.$Language->getText('tracker_include_type','tracker_report_syst').'</H2>';
		echo "\n<P>";
		echo "\n<A HREF=\"/tracker?func=reporting&group_id=".(int)$this->Group->getID()."&atid=".(int)$this->getID()."&field=aging\">".$Language->getText('tracker_include_type','aging_rep')."</A><BR>";
	
		$fields = $art_field_fact->getAllUsedFields();
	
		while (list($field_name,$field) = each($fields)	) {
		    if ($field->isSpecial()) { continue;}
	
		    if ($field->isSelectBox()) {
	
			echo "\n<A HREF=\"/tracker?func=reporting&group_id=".(int)$this->Group->getID()."&atid=".(int)$this->getID()."&field=".(int)$field->getID()."\"> ". $hp->purify($this->getItemName(), CODEX_PURIFIER_CONVERT_HTML) ."s by '". $hp->purify(SimpleSanitizer::unsanitize($field->getLabel()), CODEX_PURIFIER_CONVERT_HTML) ."'</A><BR>";
		    }
		}
	
		$this->footer(array());
	
	}
	
	/**
	 *  Display the reporting by age
	 *
	 *  @return void
	 */
	function reportingByAge() {
		global $Language;
        $hp = CodeX_HTMLPurifier::instance();
		$this->header(array("title"=>$Language->getText('tracker_import_admin','tracker')." '". $hp->purify(SimpleSanitizer::unsanitize($this->getName()), CODEX_PURIFIER_CONVERT_HTML) ."' - ".$Language->getText('tracker_include_type','aging_rep'),
				  'help' => 'TrackerReporting.html'));
	
		
		$closedItems = $this->getArtifactsByAge();
	
	
		echo '<H2>'.$Language->getText('tracker_include_type','tracker_aging_rep').'</H2>';
	
		GraphIt($closedItems['names'], $closedItems['values'],$Language->getText('tracker_include_type','avg_time', $hp->purify($this->getItemName(), CODEX_PURIFIER_CONVERT_HTML) ."s"));
		echo "<P>";
	
		$openItems = $this->getOpenArtifactsByAge();
		
		GraphIt($openItems['names'], $openItems['values'],$Language->getText('tracker_include_type','nb_open', $hp->purify($this->getItemName(), CODEX_PURIFIER_CONVERT_HTML) ."s"));
		$this->footer(array());
	}
	
	/**
	 *  Display the reporting by field
	 *
	 *  @return void
	 */
	function reportingByField($field_id) {
		global $art_field_fact,$Language;
        $hp = CodeX_HTMLPurifier::instance();
		$field = $art_field_fact->getFieldFromId($field_id);
	
		$this->header(array("title"=>$Language->getText('tracker_import_admin','tracker')." '". $hp->purify(SimpleSanitizer::unsanitize($this->getName()), CODEX_PURIFIER_CONVERT_HTML) ."' - ".$Language->getText('tracker_include_type','rep_field')." '". $hp->purify(SimpleSanitizer::unsanitize($field->getLabel()), CODEX_PURIFIER_CONVERT_HTML) ."'",
				  'help' => 'TrackerReporting.html'));
	
		if ($field->isStandardField()) {
			$openItems = $this->getOpenArtifactsBy($field);
			$allItems = $this->getArtifactsBy($field);
		} else {
			$openItems = $this->getOpenArtifactsByField($field);
			$allItems = $this->getArtifactsByField($field);
		}
	
		echo '<H3>'.$Language->getText('tracker_include_type','open_by',array('\'<a href="/tracker?group_id='.(int)$this->Group->getID().'&atid='.(int)$this->getID().'">'. $hp->purify(SimpleSanitizer::unsanitize($this->getName()), CODEX_PURIFIER_CONVERT_HTML) .'</a>\'', $hp->purify(SimpleSanitizer::unsanitize($field->getLabel()), CODEX_PURIFIER_CONVERT_HTML) )).'</H3>';
	
		GraphIt($openItems['names'], $openItems['values'],$Language->getText('tracker_include_type','open_by',array( $hp->purify($this->getItemName(), CODEX_PURIFIER_CONVERT_HTML) , $hp->purify(SimpleSanitizer::unsanitize($field->getLabel()), CODEX_PURIFIER_CONVERT_HTML) )));
		echo '<H3>'.$Language->getText('tracker_include_type','all_by',array('\'<a href="/tracker?group_id='.(int)$this->Group->getID().'&atid='.(int)$this->getID().'">'. $hp->purify(SimpleSanitizer::unsanitize($this->getName()), CODEX_PURIFIER_CONVERT_HTML) .'</a>\'', $hp->purify(SimpleSanitizer::unsanitize($field->getLabel()), CODEX_PURIFIER_CONVERT_HTML) )).'</H3>';
	
		GraphIt($allItems['names'], $allItems['values'],$Language->getText('tracker_include_type','all_by',array( $hp->purify($this->getItemName(), CODEX_PURIFIER_CONVERT_HTML) , $hp->purify(SimpleSanitizer::unsanitize($field->getLabel()), CODEX_PURIFIER_CONVERT_HTML) )));
		$this->footer(array());
	
	}



	/**
         * Display the artifact to do mass changes
         *
         * @param ro: read only parameter - Display mode or update mode
         * @param pv: printer version
	 * @param query: only in the case of func=masschange, the query that retrieves all the artifacts to be changed
	 * @param $mass_change_ids[]: only in the case of func=masschange_selected, an array containing all the artifact ids to be changed
         *
         * @return void
         */
        function displayMassChange($ro, $mass_change_ids=null,$query=null,$art_report_html=null) {
            global $art_field_fact,$sys_datefmt,$sys_max_size_attachment,$Language;
            $hp = CodeX_HTMLPurifier::instance();
            $fields_per_line=2;
            $max_size=40;
            
            $group = $this->getGroup();
            $atid = $this->getID();
            $group_id = $group->getGroupId();

	    if ($query) {
		$art_report_html->getQueryElements($query,$advsrch,$from,$where);
	  	$sql = "select distinct a.artifact_id ".$from." ".$where;
	  	$result = db_query($sql);
		while ($row = db_fetch_array($result)) {
			$mass_change_ids[] = $row['artifact_id'];
		}
	    }
            
	    //if ($mass_change_ids) {
            	echo '<H2>'.$Language->getText('tracker_include_type','changing_items',count($mass_change_ids)).' </H2>';
		reset($mass_change_ids);
		while ( list($key, $val) = each($mass_change_ids)) {
            $url = '/tracker/?func=detail&group_id='. (int)$group_id .'&aid='. (int)$val .'&atid='. (int)$atid;
			if ($key == 0) echo '<a href="'. $url .'">'. $hp->purify($this->getItemName(), CODEX_PURIFIER_CONVERT_HTML) .' #'. (int)$val .'</a>';
			if ($key > 0) echo ', <a href="'. $url .'"> #'. (int)$val .'</a>';
			if ($key == 100) {echo ", .."; break;}
		}


		echo '
	    <br><br>';
                echo '
            <FORM ACTION="" METHOD="POST" enctype="multipart/form-data" NAME="masschange_form">
            <INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="'.$sys_max_size_attachment.'">
            <INPUT TYPE="HIDDEN" NAME="func" VALUE="postmasschange">
            <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.(int)$group_id.'">
            <INPUT TYPE="HIDDEN" NAME="atid" VALUE="'.(int)$atid.'">';
		reset($mass_change_ids);
		while (list(,$val) = each($mass_change_ids)) {
			echo '
	    <INPUT TYPE="HIDDEN" NAME="mass_change_ids[]" VALUE="'. $hp->purify($val, CODEX_PURIFIER_CONVERT_HTML) .'">';	
		}


                echo '
            <TABLE cellpadding="0">';

		//first special case for submitted_by
		$field = $art_field_fact->getFieldFromName('submitted_by');
		$field_html = new ArtifactFieldHtml($field);
		$field_value = $Language->getText('global','unchanged');
                                
                list($sz,) = explode("/",$field->getDisplaySize());
                $label = $field_html->labelDisplay(false,false,!$ro);
                // original submission field must be displayed read-only
                $value = $field_html->display($this->getID(),$field_value,false,false,$ro,false,false,$Language->getText('global','none'),false,'Any',true,$Language->getText('global','unchanged'));

                echo "\n<TR>";
                echo '
	    <TD valign="top">'.$label.'&nbsp;</TD>
            <TD valign="top">'.$value.'&nbsp;</TD>
            <TD colspan="2"><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('tracker_include_type','submit_mass_change').'"></TD>';
            	echo '
            </TR>
            <TR><TD COLSPAN="'.($fields_per_line*2).'">&nbsp</TD></TR>
            <script type="text/javascript" src="/scripts/calendar_js.php"></script>';
        
        
                // Now display the variable part of the field list (depend on the project)
        
		$i = 0;            
            	$result_fields = $art_field_fact->getAllUsedFields();
        	while ( list($key, $field) = each($result_fields) ) {
                
                	$field_html = new ArtifactFieldHtml($field);
                
                        // if the field is a special field (except summary and details) 
                        // then skip it.
                        if ( !$field->isSpecial() && $field->getName()!='summary' && $field->getName()!='details') {
                                           
                                // display the artifact field
                                // if field size is greatest than max_size chars then force it to
                                // appear alone on a new line or it won't fit in the page
                                
                                $field_value = $Language->getText('global','unchanged');
                                
                                list($sz,) = explode("/",$field->getDisplaySize());
                                $label = $field_html->labelDisplay(false,false,!$ro);
                                $value = $field_html->display($this->getID(),$field_value,false,false,$ro,false,false,$Language->getText('global','none'),false,$Language->getText('global','any'),true,$Language->getText('global','unchanged'));

                                        
                                // Details field must be on one row
                                if ($sz > $max_size) {
                                        echo "\n<TR>".
                                          '<TD valign="top">'.$label.'</td>'.
                                          '<TD valign="top" colspan="'.(2*$fields_per_line-1).'">'.
                                          $value.'</TD>'.                     
                                          "\n</TR>";
                                        $i=0;
                                } else {
                                        echo ($i % $fields_per_line ? '':"\n<TR>");
                                        echo '<TD valign="top">'.$label.'</td>'.
                                          '<TD valign="top">'.$value.'</TD>';
                                        $i++;
                                        echo ($i % $fields_per_line ? '':"\n</TR>");
                                }
                        }
                } // while
                          

		echo '
	    </TABLE>
	    <table cellspacing="0">';

		//
                // Followups comments
                //
                echo '
	    <TR><TD colspan="2" align="top"><HR></td></TR>
	    <TR><TD>
            <h3>'.$Language->getText('tracker_include_artifact','follow_ups').' '.help_button('ArtifactUpdate.html#ArtifactComments').'</h3></td>
            <TD>
            <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('tracker_include_type','submit_mass_change').'">
            </td></tr>';

                echo '
            <tr><TD colspan="2" align="top">
            <B>'.$Language->getText('tracker_include_artifact','use_canned').'</B>&nbsp;';
                        
                echo $this->cannedResponseBox ();
                        
                echo '
            &nbsp;&nbsp;&nbsp;<A HREF="/tracker/admin/?func=canned&atid='.(int)$atid.'&group_id='.(int)$group_id.'&create_canned=1">'.$Language->getText('tracker_include_artifact','define_canned').'</A>
            </TD></TR>';
                        
                echo '
            <TR><TD colspan="2">';
                        
                $field = $art_field_fact->getFieldFromName('comment_type_id');
                if ( $field ) {
                     $field_html = new ArtifactFieldHtml( $field );
                     echo '<P><B>'.$Language->getText('tracker_include_artifact','comment_type').'</B>'.
                          $field_html->fieldBox('',$atid,$field->getDefaultValue(),true,$Language->getText('global','none')).'<BR>';
                }
                echo '<TEXTAREA NAME="comment" ROWS="10"  style="width:100%" WRAP="SOFT"></TEXTAREA><p>';
                                
        
                echo '</td></tr>';
                
                //
                // CC List
                //
                echo '          
                <TR><TD colspan="2"><hr></td></tr>
                
                <TR><TD colspan="2">
                <h3>'.$Language->getText('tracker_include_artifact','cc_list').' '.help_button('ArtifactUpdate.html#ArtifactCCList').'</h3>';
                
                if ( !$ro ) {
                    echo '
                                '.$Language->getText('tracker_include_artifact','fill_cc_list_msg');
                    echo $Language->getText('tracker_include_artifact','fill_cc_list_lbl');
                    echo '<input type="text" name="add_cc" id="tracker_cc" size="30">';
                    echo $Language->getText('tracker_include_artifact','fill_cc_list_cmt');
                    echo '<input type="text" name="cc_comment" size="40" maxlength="255">';
                    //echo autocomplete_for_lists_users('tracker_cc', 'tracker_cc_autocomplete');
                }
                
	
		
		echo $this->showCCList($mass_change_ids);


                echo '</TD></TR>';
                        
                //
                // File attachments
                //
                echo '
                <TR><TD colspan="2"><hr></td></tr>
                <TR><TD colspan="2">
                <h3>'.$Language->getText('tracker_include_artifact','attachment').' '.help_button('ArtifactUpdate.html#ArtifactAttachments').'</h3>';
                
                echo $Language->getText('tracker_include_artifact','upload_checkbox');
		echo ' <input type="checkbox" name="add_file" VALUE="1">';
                echo $Language->getText('tracker_include_artifact','upload_file_lbl');
                echo '<input type="file" name="input_file" size="40">';
                echo $Language->getText('tracker_include_artifact','upload_file_msg',formatByteToMb($sys_max_size_attachment));

                echo $Language->getText('tracker_include_artifact','upload_file_desc');
		echo '<input type="text" name="file_description" size="60" maxlength="255">';
                                
		reset($mass_change_ids);
                echo $this->showAttachedFiles($mass_change_ids);
                echo '</TD></TR>';

                //
                // Artifact dependencies
                //
                echo '
                <TR><TD colspan="2"><hr></td></tr>
                <TR ><TD colspan="2">';
                
                echo '<h3>'.$Language->getText('tracker_include_artifact','dependencies').' '.help_button('ArtifactUpdate.html#ArtifactDependencies').'</h3>
                <B>'.$Language->getText('tracker_include_artifact','dependent_on').'</B><BR>
                <P>';
                if ( !$ro ) {
                        echo '
                        <B>'.$Language->getText('tracker_include_artifact','aids').'</B>&nbsp;
                        <input type="text" name="artifact_id_dependent" size="20" maxlength="255">
                        &nbsp;<i>'.$Language->getText('tracker_include_artifact','fill').'</i><p>';
                }
		echo $this->showDependencies($mass_change_ids);

                echo '</TD></TR>';
        	echo '<TR><TD colspan="2"><hr></td></tr>';                        
		echo '</TD></TR>                
                        <TR><TD colspan="2" ALIGN="center">
                                <INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="'.$Language->getText('tracker_include_type','submit_mass_change').'">
                                </FORM>
                        </TD></TR>';
                
                echo '</table>';
	}


	/**
	* Show all the cc addresses of all the artifacts in $change_ids
	* @param group_id: the group id
        * @param group_artifact_id: the artifact type ID
	* @param change_ids: all the ids of the artifacts affected
	*/
	function showCCList($change_ids) {
        $hp = CodeX_HTMLPurifier::instance();
	  global $Language;
		$out = "";
			
		$result = $this->getCC($change_ids);			
		
		if (db_numrows($result) > 0) {
			$title_arr=array();
                        $title_arr[]=$Language->getText('tracker_include_artifact','cc_address');
                        $title_arr[]=$Language->getText('tracker_include_type','occurrence');
                        $title_arr[]=$Language->getText('tracker_include_canned','delete');
                        $out .= html_build_list_table_top ($title_arr);
                
                        $fmt = "\n".'<TR class="%s"><td>%s</td><td align="center">%s</td><td align="center">%s</td></tr>';				

			
			// Loop through the cc and format them
			$email = "";
			$row_color = 0;
			$i = 0;
            		while ($row = db_fetch_array($result)) {
				if ($row['email'] != $email) {
					if ($email != "") {
						$html_delete = '
		<INPUT TYPE="CHECKBOX" NAME="delete_cc[]" VALUE="'. $hp->purify($delete_ids, CODEX_PURIFIER_CONVERT_HTML) .'">';
						$out .= sprintf($fmt,
                                        		util_get_alt_row_color($row_color),
                                        		$href_cc,
							$i,
                                        		$html_delete);
						$row_color++;
						$i = 0;	
					}
					$email = $row['email'];
					$res_username = user_get_result_set_from_unix($email);
                        		if ($res_username && (db_numrows($res_username) == 1))
                            			$href_cc = util_user_link($email);
                        		else
                            			$href_cc = "<a href=\"mailto:".util_normalize_email($email)."\">". $hp->purify($email, CODEX_PURIFIER_CONVERT_HTML) .'</a>';
					$delete_ids = $row['artifact_cc_id'];
				} else {
					$delete_ids .= ",".$row['artifact_cc_id'];
				}
				$i++;
			}
			$html_delete = '
		<INPUT TYPE="CHECKBOX" NAME="delete_cc[]" VALUE="'.$delete_ids.'">';
			$out .= sprintf($fmt,
                                        util_get_alt_row_color($row_color),
                                        $href_cc,
					$i,
                                        $html_delete);	
			$out .= "</TABLE>";
            	}
        
            	return($out);
	}

	/**
	* Display the list of attached files of all the artifacts in $change_ids
	* @param group_id: the group id
        * @param group_artifact_id: the artifact type ID
	* @param change_ids: all the ids of the artifacts affected
	*/
	function showAttachedFiles($change_ids) {
	  global $Language;
      $hp = CodeX_HTMLPurifier::instance();
		$out = "";
		$result = $this->getAttachedFiles($change_ids);		
	
		if (db_numrows($result) > 0) {
			$title_arr=array();
                	$title_arr[]=$Language->getText('tracker_include_artifact','name');
                	$title_arr[]=$Language->getText('tracker_include_artifact','size_kb');
                	$title_arr[]=$Language->getText('tracker_include_type','occurrence');
            		$title_arr[]=$Language->getText('tracker_include_canned','delete');
        
                	$out .= html_build_list_table_top ($title_arr);

			$fmt = '<TR class="%s"><td>%s</td><td align="center">%s</td><td align="center">%s</td><td align="center">%s</td></tr>';

			// Loop throuh the attached files and format them
			$i = 0;
			$rowcolor = 0;
			$filename = "";
			$filesize = -1;
			while ($row = db_fetch_array($result)) {
				if ($row['filename'] != $filename || $row['filesize'] != $filesize) {
					if ($filename != "") {
						$html_delete = '
	<INPUT TYPE="CHECKBOX" NAME="delete_attached[]" VALUE="'. $hp->purify($delete_ids, CODEX_PURIFIER_CONVERT_HTML) .'">';
						$out .= sprintf($fmt,
                                            		util_get_alt_row_color($rowcolor),
                                            		 $hp->purify($filename, CODEX_PURIFIER_CONVERT_HTML) ,
                                            		intval($filesize/1024),
							$i,
                                            		$html_delete);
						$i = 0;	
						$rowcolor++;
					}
					$delete_ids = $row['id'];
					$filename = $row['filename'];
					$filesize = $row['filesize'];	
				} else {
					$delete_ids .= ",".$row['id'];						
				}
				$i++;
			}
			$html_delete = '
	<INPUT TYPE="CHECKBOX" NAME="delete_attached[]" VALUE="'. $hp->purify($delete_ids, CODEX_PURIFIER_CONVERT_HTML) .'">';
			$out .= sprintf($fmt,
                                        util_get_alt_row_color($rowcolor),
                                         $hp->purify($filename, CODEX_PURIFIER_CONVERT_HTML) ,
                                        intval($filesize/1024),
					$i,
                                        $html_delete);	
			$out .= "</TABLE>";	
		}
		return $out;

	}

	/**
         * Display the artifact dependencies list for all artifacts in change_ids 
         *
         * @param change_ids: the artifacts for that we search dependencies
         * @return string
         */
        function showDependencies ($change_ids) {
	  global $Language;
            $hp = CodeX_HTMLPurifier::instance();
            $result=$this->getDependencies($change_ids);
            $rows=db_numrows($result);
            $out = '';
            // Nobody in the dependencies list -> return now
            if ($rows > 0) {
            	$title_arr=array();
              	$title_arr[]=$Language->getText('tracker_include_artifact','artifact');
                $title_arr[]=$Language->getText('tracker_include_artifact','summary');
                $title_arr[]=$Language->getText('tracker_import_admin','tracker');
                $title_arr[]=$Language->getText('tracker_include_artifact','group');
                $title_arr[]=$Language->getText('tracker_include_type','occurrence');
                $title_arr[]=$Language->getText('tracker_include_canned','delete');
                $out .= html_build_list_table_top ($title_arr);
                
                $fmt = "\n".'<TR class="%s"><td>%s</td><td>%s</td><td align="center">%s</td>'.
                            '<td align="center">%s</td><td align="center">%s</td><td align="center">%s</td></tr>';
                
            	// Loop through the denpendencies and format them
		$occ = 0;
		$dependent_on_artifact_id = -1;
		$row_color = 0;
            	for ($i=0; $i < $rows; $i++) {
        		if ($dependent_on_artifact_id != db_result($result, $i, 'is_dependent_on_artifact_id')) {
				if ($dependent_on_artifact_id != -1) {
					$html_delete = '
	<INPUT TYPE="CHECKBOX" NAME="delete_depend[]" VALUE="'. $hp->purify($depend_ids, CODEX_PURIFIER_CONVERT_HTML) .'">';
					$out .= sprintf($fmt,
                                            	util_get_alt_row_color($row_color),
                                            	'<a href="/tracker/?func=gotoid&group_id='. (int)$group_id .'&aid='. (int)$dependent_on_artifact_id .'">'.  $hp->purify($dependent_on_artifact_id, CODEX_PURIFIER_CONVERT_HTML)  ."</a>",
                                            	 $hp->purify(util_unconvert_htmlspecialchars($summary), CODEX_PURIFIER_BASIC, $this->getGroupId()) ,
                                            	 $hp->purify($tracker_label, CODEX_PURIFIER_CONVERT_HTML) ,
                                            	 $hp->purify($group_label, CODEX_PURIFIER_CONVERT_HTML) ,
						$occ,
                                            	$html_delete);
					$row_color++;
					$occ = 0;	
				}
                $dependent_on_artifact_id = db_result($result, $i, 'is_dependent_on_artifact_id');
                $summary                  = db_result($result, $i, 'summary');
                $tracker_label            = db_result($result, $i, 'name');
                $group_label              = db_result($result, $i, 'group_name');
                $group_id                 = db_result($result, $i, 'group_id');
				$depend_ids               = db_result($result, $i, 'artifact_depend_id');
			} else {
				$depend_ids .= ",".db_result($result, $i, 'artifact_depend_id');
			}
			$occ++;
                } // for
		$html_delete = '
	<INPUT TYPE="CHECKBOX" NAME="delete_depend[]" VALUE="'.$depend_ids.'">';
		$out .= sprintf($fmt,
                                util_get_alt_row_color($row_color),
                                '<a href="/tracker/?func=gotoid&group_id='. (int)$group_id .'&aid='. (int)$dependent_on_artifact_id .'">'.  $hp->purify($dependent_on_artifact_id, CODEX_PURIFIER_CONVERT_HTML)  ."</a>",
                                 $hp->purify(util_unconvert_htmlspecialchars($summary), CODEX_PURIFIER_BASIC, $this->getGroupId()) ,
                                 $hp->purify($tracker_label, CODEX_PURIFIER_CONVERT_HTML) ,
                                 $hp->purify($group_label, CODEX_PURIFIER_CONVERT_HTML) ,
				$occ,
                                $html_delete);
        
            	// final touch...
            	$out .= "</TABLE>";
            }
        
            return($out);
        }




	/**
	 *  Display the fieldset creation or update form
	 *
	 *  @param string $func fieldset_create or fieldset_update
	 *  @param int $fieldset_id the fieldset id
	 *  @param string $fieldset_name the fieldset name
	 *  @param string $description: the fieldset description
	 *  @param int $rank rank on screen
	 *
	 *  @return void
	 */
    function displayFieldSetCreateForm($func="fieldset_create",$fieldset_id=false,$fieldset_name=false,$description=false,$rank=false) {
        global $art_fieldset_fact,$Language;
        $hp = CodeX_HTMLPurifier::instance();
        $fieldset = $art_fieldset_fact->getFieldSetById($fieldset_id);

        $afs = new ArtifactFieldSet();

        if ( $func == "fieldset_create" ) {
            echo '<h3>'.$Language->getText('tracker_include_type','create_fieldset').'</h3>';
            echo '
              <form name="form_create" method="/tracker/admin/index.php">
              <input type="hidden" name="func" value="'.$func.'">
              <input type="hidden" name="group_id" value="'.(int)$this->Group->getID().'">
              <input type="hidden" name="atid" value="'.(int)$this->getID().'">
              <input type="hidden" name="fieldset_id" value="">
              <input type="hidden" name="fieldset_name" value="">
              <input type="hidden" name="description" value="">
              <input type="hidden" name="rank" value="">';
        } else {
            echo "<h3>".$Language->getText('tracker_include_type','update_fieldset',$fieldset_name)."</h3>";
            echo '
              <form name="form_create" method="/tracker/admin/index.php">
              <input type="hidden" name="func" value="'. $hp->purify($func, CODEX_PURIFIER_CONVERT_HTML) .'">
              <input type="hidden" name="group_id" value="'.$this->Group->getID().'">
              <input type="hidden" name="atid" value="'.(int)$this->getID().'">
              <input type="hidden" name="fieldset_id" value="'.(int)$fieldset_id.'">
              <input type="hidden" name="fieldset_name" value="'. $hp->purify(SimpleSanitizer::unsanitize($fieldset_name), CODEX_PURIFIER_CONVERT_HTML) .'">
              <input type="hidden" name="description" value="'. $hp->purify(SimpleSanitizer::unsanitize($description), CODEX_PURIFIER_CONVERT_HTML) .'">
              <input type="hidden" name="rank" value="'. $hp->purify($rank, CODEX_PURIFIER_CONVERT_HTML) .'">';
        }
        echo '<fieldset>';
        echo '<legend>'.$Language->getText('tracker_include_type','fieldset_ident').'</legend>';
        echo '<p>';
        echo '<label for="name">'.$Language->getText('tracker_include_type','fieldset_name').': <font color="red">*</font></label> ';
        echo '<input type="text" name="name" id="name" value="'. $hp->purify(SimpleSanitizer::unsanitize($fieldset_name?$fieldset_name:""), CODEX_PURIFIER_CONVERT_HTML) .'" size="30" maxlength="40" />';
        echo '</p>';
        echo '<p>';
        echo '<label for="description">'.$Language->getText('tracker_include_type','fieldset_desc').':</label>';
        echo '<input type="text" name="description" id="description" value="'. $hp->purify(SimpleSanitizer::unsanitize($description?$description:""), CODEX_PURIFIER_CONVERT_HTML) .'" size="70" maxlength="255" />';
        echo '</p>';
        echo '</fieldset>';
        echo '<fieldset>';
        echo '<legend>'.$Language->getText('tracker_include_type','fieldset_display').'</legend>';
        echo '<p>';
        echo '<label for="rank">'.$Language->getText('tracker_include_type','rank_screen').':</label>';
        echo '<input type="text" name="rank" id="rank" value="'. $hp->purify(($rank?$rank:""), CODEX_PURIFIER_CONVERT_HTML) .'" size="5" maxlength="5" />';
        echo '</p>';
        echo '</fieldset>';

        if ( $func == "fieldset_create" ) {
            echo '<input type="submit" name="Submit" value="'.$Language->getText('global','btn_create').'">';
        } else {
            echo '<input type="submit" name="Submit" value="'.$Language->getText('global','btn_update').'">';
        }

        echo '</form>';
        echo '<p><font color="red">*</font>: '.$Language->getText('tracker_include_type','fields_requ').'</p>';

    }

    /**
     *  Display the field sets list
     *
     *  @return void
     */
    function displayFieldSetList() {
        global $ath,$art_fieldset_fact,$Language;
        $hp = CodeX_HTMLPurifier::instance();
        echo '<h3>'.$Language->getText('tracker_include_type','list_all_fieldsets').'</h3>';
        echo '<p>'.$Language->getText('tracker_include_report','mod');
        
        
        // Show all the fields currently available in the system
        $i=0;
        $title_arr=array();
        $title_arr[]=$Language->getText('tracker_include_type','fieldset_name');
        $title_arr[]=$Language->getText('tracker_include_artifact','desc');
        $title_arr[]=$Language->getText('tracker_include_type','fields_inside');
        $title_arr[]=$Language->getText('tracker_include_type','rank_screen');
        $title_arr[]=$Language->getText('tracker_include_canned','delete');
        
        echo html_build_list_table_top ($title_arr);
        
        // Build HTML ouput for  Used fields
        $iu=0;
        $fieldsets = $art_fieldset_fact->getAllFieldSets();
        $html = "";
        
        foreach ($fieldsets as $fieldset) {
            
            $rank = ($fieldset->getRank())?$fieldset->getRank():"-";
            
            $html .= '<tr class="'.util_get_alt_row_color($iu) .'">';
            $html .= '<td><a href="?group_id='.(int)$this->Group->getID()."&atid=".(int)$this->getID().'&func=display_fieldset_update&fieldset_id='.(int)$fieldset->getID().'">'. $hp->purify(SimpleSanitizer::unsanitize($fieldset->getLabel()), CODEX_PURIFIER_CONVERT_HTML) .'</A></td>';
            $html .= '<td>'. $hp->purify(SimpleSanitizer::unsanitize($fieldset->getDescriptionText()), CODEX_PURIFIER_BASIC, $this->getGroupId()) .'</td>';
            $html .= '<td>';
            $fields_contained = $fieldset->getArtifactFields();
            if (count($fields_contained) > 0) {
                $html .= '<ul>';
                foreach($fields_contained as $field_contained) {
                    $link_field_usage = '/tracker/admin/?func=display_field_update&group_id='.(int)$this->Group->getID().'&atid='.(int)$this->getID().'&field_id='.(int)$field_contained->getID();
                    $html .= '<li>';
                    if ($field_contained->getUseIt()) {
                        $html .= '<strong><a href="'.$link_field_usage.'">'. $hp->purify(SimpleSanitizer::unsanitize($field_contained->getLabel()), CODEX_PURIFIER_CONVERT_HTML) .'</a></strong>';
                    } else {
                        $html .= '<em><a href="'.$link_field_usage.'">'. $hp->purify(SimpleSanitizer::unsanitize($field_contained->getLabel()), CODEX_PURIFIER_CONVERT_HTML) .'</a></em>';
                    }
                    $html .= '</li>';
                }
                $html .= '</ul>';
            } else {
                $html .= '- - -'; // $Language-> getText('tracker_include_type','empty_fieldset');
            }
            $html .= '</td>';
            $html .= '<td align="center">'. $hp->purify($rank, CODEX_PURIFIER_CONVERT_HTML) .'</td>';
            $html .= '<td align="center">';
            // Only possible to delete empty field sets (containing no fields inside)
            if (count($fields_contained) <= 0) {
                $html .= '<a href="/tracker/admin/?func=fieldset_delete&group_id='.(int)$this->Group->getID().'&atid='.(int)$this->getID().'&fieldset_id='.(int)$fieldset->getID().'"><img src="'.util_get_image_theme("ic/trash.png").'" border="0" onClick="return confirm(\''.$Language->getText('tracker_include_type','warning_delete_fieldset').'\')"></a>';
            }
            $html .= '</td>';
            $html .= '</tr>';
                                    
            $iu++;
        }
        echo $html;
        echo '</table>';
        echo '<hr>';
        
    }
    
    /**
     * Display the dropdownlist (select list) of all available fieldsets of the tracker $artifact_group_id
     *
     * @param int $artifact_group_id the tracker id
     * @param int $selected_field_set_id the id of the fieldset that must be selected, or false if no default fieldset is selected
     */
    function displayFieldSetDropDownList($artifact_group_id, $selected_fieldset_id = false) {
        $hp = CodeX_HTMLPurifier::instance();
        global $ath,$art_fieldset_fact;
        
        $fieldsets = $art_fieldset_fact->getArtifactFieldSetsFromId($artifact_group_id);
        
        $html = '<select name="field_set_id">';
        foreach($fieldsets as $fieldset) {
            $html .= '<option value="'.$fieldset->getID().'"';
            if ($fieldset->getID() === $selected_fieldset_id) {
                $html .= ' selected="selected"';
            }
            $html .= '>'. $hp->purify(SimpleSanitizer::unsanitize($fieldset->getLabel()), CODEX_PURIFIER_CONVERT_HTML) .'</option>';
        }
        
        $html .= '</select>';
        echo $html;
    }



}

?>
