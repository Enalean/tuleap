<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('TrackerFactory.class.php');
require_once('Tracker_URL.class.php');
require_once('Tracker_CannotAccessTrackerException.class.php');
require_once('FormElement/Tracker_FormElementFactory.class.php');
require_once('Artifact/Tracker_ArtifactFactory.class.php');
require_once('Report/Tracker_ReportFactory.class.php');

class TrackerManager { /* extends Engine? */
    
    /**
     * Check that the service is used and the plugin is allowed for project $project
     * if it is not the case then exit with an error
     * 
     * @param Project $project
     * 
     * @return bool true if success. Otherwise the process terminates.
     */
    public function checkServiceEnabled(Project $project) {
        if ($project->usesService('plugin_tracker')) {
            return true;
        }
        $GLOBALS['Response']->addFeedback('error', "The project doesn't use the 'tracker v5' service");
        $GLOBALS['HTML']->redirect('/projects/'. $project->getUnixName() .'/');
        exit();
    }
    
    public function checkUserCanAccessTracker($tracker, $user) {
        $this->checkServiceEnabled($tracker->getProject());
        
        if (!$tracker->isActive()) {
            throw new Tracker_CannotAccessTrackerException($GLOBALS['Language']->getText('plugin_tracker_common_type', 'tracker_not_exist'));
        }
        if (!$tracker->userCanView($user)) {
            throw new Tracker_CannotAccessTrackerException($GLOBALS['Language']->getText('plugin_tracker_common_type', 'no_view_permission'));
        }
    }
    
    /**
     * Controler
     *
     * @param Codendi_Request $request The request
     * @param User            $user    The user that execute the request
     *
     * @return void
     */
    public function process($request, $user) {
        $url = $this->getUrl();
        try {
            $object = $url->getObjectFromRequest($request, $user);
            
            // Tracker related check
            $tracker = null;
            if ($object instanceof Tracker) {
                $tracker = $object;
            } else {
                if (method_exists($object, 'getTracker')) {
                    $tracker = $object->getTracker();
                }
            }
            if ($tracker) {
                $this->checkUserCanAccessTracker($tracker, $user);
                $GLOBALS['group_id'] = $tracker->getGroupId();
            }

            // Dispatch depending of the type of object
            if ($object instanceof Tracker) {
                $tracker->process($this, $request, $user);
            } else if ($object instanceof Tracker_Report) {
                $report = $object;
                $report->process($this, $request, $user);
            } else if ($object instanceof Tracker_FormElement_Interface) {
                $formElement = $object;
                $formElement->process($this, $request, $user);
            } else if ($object instanceof Tracker_Artifact) {
                $artifact = $object;
                if ((int)$request->get('aid')) {
                    if ($artifact->userCanView($user)) {
                        $artifact->process($this, $request, $user);
                    } else {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_type', 'no_view_permission_on_artifact'));
                        $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $artifact->getTrackerId());
                    }
                } else if ($request->get('func') == 'new-artifact-link') {
                        echo '<html>';
                        echo '<head>';
                        $GLOBALS['HTML']->displayStylesheetElements(array());
                        $GLOBALS['HTML']->displayJavascriptElements(array());
                        echo '</head>';

                        echo '<body>';
                        echo '<div class="contenttable">';

                        $project = $artifact->getTracker()->getProject();
                        echo $this->fetchTrackerSwitcher($user, ' ', $project, null);
                } else if ((int)$request->get('link-artifact-id')) {
                    $artifact->getTracker()->displayAReport($this, $request, $user);
                }
            }
        } catch (Tracker_RessourceDoesntExistException $e) {
             exit_error($GLOBALS['Language']->getText('global', 'error'), $e->getMessage());
        } catch (Tracker_CannotAccessTrackerException $e) {
            $GLOBALS['Response']->addFeedback('error', $e->getMessage());
            $this->displayAllTrackers($tracker->getProject(), $user);
        } catch (Tracker_NoMachingRessourceException $e) {
            //show, admin all trackers
            if ((int)$request->get('group_id')) {
                $group_id = (int)$request->get('group_id');
                if ($project = $this->getProject($group_id)) {
                    if ($this->checkServiceEnabled($project)) {
                        switch($request->get('func')) {
                            case 'docreate':
                                if ($this->userCanCreateTracker($group_id)) {
                                    if ($request->exist('preview_xml') && $request->get('preview_xml')) {
                                        //todo: check that a file is uploaded
                                        $this->displayTrackerPreview($_FILES["file"]["tmp_name"]);
                                    } else {
                                        $new_tracker = null;
                                        $name              = trim($request->get('name'));
                                        $description       = trim($request->get('description'));
                                        $itemname          = trim($request->get('itemname'));
                                        $codendi_template  = (int)$request->get('codendi_template');
                                        $group_id_template = (int)$request->get('group_id_template');
                                        $atid_template     = (int)$request->get('atid_template');

                                        if ($codendi_template != 100) {
                                            $group_id_template =100;
                                            $atid_template = $codendi_template;
                                        }

                                        if ($request->exist('create_from_xml') && $request->get('create_from_xml')) {
                                            //todo: check that a file is uploaded
                                            $new_tracker = $this->importTracker($project, $name, $description, $itemname, $_FILES["file"]["tmp_name"]);
                                        } else {
                                            $new_tracker = $this->getTrackerFactory()->create($project->getId(), $group_id_template, $atid_template, $name, $description, $itemname);
                                        }

                                        if ($new_tracker) {
                                            $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?group_id='. $project->group_id .'&tracker='. $new_tracker->id);
                                        } else {
                                            $codendi_template  = $codendi_template  ? $codendi_template  : '';
                                            $group_id_template = $group_id_template ? $group_id_template : '';
                                            $atid_template     = $atid_template     ? $atid_template     : '';
                                            $this->displayCreateTracker($project,$name,$description,$itemname,$codendi_template,$group_id_template,$atid_template);
                                        }
                                    }
                                } else {
                                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?group_id='. $group_id);
                                }
                                break;
                            case 'create':
                                if ($this->userCanCreateTracker($group_id)) {
                                    $this->displayCreateTracker($project);
                                } else {
                                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?group_id='. $group_id);
                                }
                                break;
                            case 'csvimportoverview':
                                $this->displayCSVImportOverview($project, $group_id, $user);
                                break;
                            default:
                                $this->displayAllTrackers($project, $user);
                                break;
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Display header for tracker service
     *
     * @param Project $project    The project
     * @param string  $title      The title for this page
     * @param array   $breadcrumb The breadcrumbs for this page
     * @param ?       $toolbar    The toolbar
     *
     * @return void
     */
    public function displayHeader($project, $title, $breadcrumbs, $toolbar) {
        if (count($breadcrumbs)) {
            $breadcrumbs = array_merge(
                array(
                    array(
                        'title'     => $GLOBALS['Language']->getText('plugin_tracker', 'trackers'),
                        'url'       => TRACKER_BASE_URL.'/?group_id='. $project->group_id,
                        'classname' => 'trackers',
                    )
                ),
                $breadcrumbs
            );
        } else {
            $breadcrumbs = array();
        }
        if ($service = $project->getService('plugin_tracker')) {
            $service->displayHeader($title, $breadcrumbs, $toolbar);
        }
    }
    
    /**
     * Display footer for tracker service
     *
     * @param Project $project The project
     */
    public function displayFooter($project) {
        if ($service = $project->getService('plugin_tracker')) {
            $service->displayFooter();
        }
    }
    
    public function displayCreateTracker($project, 
                                         $name = '', 
                                         $description = '', 
                                         $itemname = '', 
                                         $codendi_template = '', 
                                         $group_id_template = '', 
                                         $atid_template = '') {
        global $Language;
        $breadcrumbs = array(
            array(
                'title' => $GLOBALS['Language']->getText('plugin_tracker_index', 'create_new_tracker'),
                'url'   => TRACKER_BASE_URL.'/?group_id='. $project->group_id .'&amp;func=create'
            )
        );
        $toolbar = array();
        $this->displayHeader($project, 'Trackers', $breadcrumbs, $toolbar);
        
        $hp = Codendi_HTMLPurifier::instance();
        echo '<script type="text/javascript">
              function onChangeGroup() {
                document.form_create.func.value = "create";
                document.form_create.submit();
              }
              
              function checkValues() {
                codendi.feedback.clear();
                var is_error = false;
                if ( !document.form_create.name.value.match(/\S/) ) {
                    codendi.feedback.log(\'error\', \''. addslashes($Language->getText('plugin_tracker_include_type','fill_name')) .'\');
                    is_error = true;
                }
                if ( !document.form_create.description.value.match(/\S/) ) {
                    codendi.feedback.log(\'error\', \''. addslashes($Language->getText('plugin_tracker_include_type','fill_desc')) .'\');
                    is_error = true;
                }
                if ( !document.form_create.itemname.value.match(/\S/) ) {
                    codendi.feedback.log(\'error\', \''. addslashes($Language->getText('plugin_tracker_include_type','fill_short')) .'\');
                    is_error = true;
                }
                return !is_error;
                
              }
            
              function onSubmitCreateTemplate() {
                codendi.feedback.clear();
                if ( checkValues() ) {
                    if ( (document.form_create.group_id_template.value == "")||(document.form_create.atid_template.value == "") ) {
                        document.form_create.feedback.value = "'.$Language->getText('plugin_tracker_include_type','choose_proj').'";
                        document.form_create.func.value = "create";
                    } else {
                        document.form_create.atid_chosen.value = document.form_create.atid_template.value;
                        document.form_create.group_id_chosen.value = document.form_create.group_id_template.value;
                        document.form_create.submit();
                    }
                }
              }
    
              function onSubmitCreateCodendiTemplate() {
                codendi.feedback.clear();
                if ( checkValues() ) {
                    if ( document.form_create.codendi_template.value == 100 ) {
                        codendi.feedback.log(\'error\', \''. addslashes($Language->getText('plugin_tracker_include_type','choose_tmpl')) .'\');
                    } else {
                        document.form_create.atid_chosen.value = document.form_create.codendi_template.value;
                        document.form_create.group_id_chosen.value = 100;
                        document.form_create.submit();
                    }
                }
              }
    
              function showGroupSelection() {
                win=window.open("","group_id_selection","height=210,width=480,toolbar=no,location=no,resizable=yes,left=200,top=200");
                win.location = "'.TRACKER_BASE_URL.'/group_selection.php?opener_form=form_create&opener_field=group_id_template&filter=member";
              }
    
              function showTrackerSelection() {
                if ( document.form_create.group_id_template.value == "" ) {
                    alert("'.$Language->getText('plugin_tracker_include_type','select_proj').'");
                    return;
                }
                win=window.open("","artifact_group_id_selection","height=45,width=400,toolbar=no,location=no,resizable=yes,left=200,top=200");
                win.location = "'.TRACKER_BASE_URL.'/tracker_selection.php?group_id=" + document.form_create.group_id_template.value + "&opener_form=form_create&opener_field=atid_template";
              }
    
              </script>
             ';
        echo $Language->getText('plugin_tracker_include_type','create_tracker');
        echo '<form name="form_create" method="post" enctype="multipart/form-data">
          <input type="hidden" name="group_id" value="'.(int)$project->group_id.'">
          <input type="hidden" name="func" value="docreate">
          <input type="hidden" name="atid_chosen" value="">
          <input type="hidden" name="group_id_chosen" value="">
          <table width="100%" border="0" cellpadding="5">
            <tr> 
              <td width="21%"><b>'.$Language->getText('plugin_tracker_include_artifact','name').'</b>: <font color="red">*</font></td>
              <td width="79%"> 
                <input type="text" name="name" value="'. $hp->purify($name, CODENDI_PURIFIER_CONVERT_HTML) .'">
              </td>
            </tr>
            <tr> 
              <td width="21%"><b>'.$Language->getText('plugin_tracker_include_artifact','desc').'</b>: <font color="red">*</font></td>
              <td width="79%"> 
                <textarea name="description" rows="3" cols="50">'. $hp->purify($description, CODENDI_PURIFIER_CONVERT_HTML) .'</textarea>
              </td>
            </tr>
            <tr> 
              <td width="21%"><b>'.$Language->getText('plugin_tracker_include_type','short_name').'</b>: <font color="red">*</font></td>
              <td width="79%"> 
                <input type="text" name="itemname" value="'. $hp->purify($itemname, CODENDI_PURIFIER_CONVERT_HTML) .'">
              </td>
            </tr>
                    <tr><td colspan=2><i>'.$Language->getText('plugin_tracker_include_type','avoid_spaces').'</i></td></tr>';
        echo '</table>';

        echo '<p>'.$Language->getText('plugin_tracker_include_type','choose_creation').'</p>';
        echo '<table>';
        /*
        echo ' <tr valign="top">
                 <td width="300"><li><b>'.$Language->getText('plugin_tracker_include_type','from_tmpl').'</b></li></td>
                 <td colspan="2">';
        echo $this->trackersSelectBox(100,"codendi_template",$codendi_template);
        echo ' &nbsp;<input type="button" name="CreateCodendiTemplate" value="'.$Language->getText('global','btn_create').'" onClick="onSubmitCreateCodendiTemplate()"><br><br></td></tr>';
        echo ' <tr valign="top">
                 <td width="300"><li>'.$Language->getText('plugin_tracker_include_type','from_exist').'</li></td>
                 <td>
                    <table>
                      <tr>
                        <td>'.$Language->getText('plugin_tracker_include_type','proj_id').'</td>
                        <td><input name="group_id_template" value="'. $hp->purify($group_id_template, CODENDI_PURIFIER_CONVERT_HTML) .'"><a href="javascript:showGroupSelection()"><img src="'.util_get_image_theme("button_choose.png").'" align="absmiddle" border="0"></a></td>
                      </tr>
                      <tr>
                        <td>'.$Language->getText('plugin_tracker_include_type','tracker_id').'</td>
                        <td><input name="atid_template" value="'. $hp->purify($atid_template, CODENDI_PURIFIER_CONVERT_HTML) .'"><a href="javascript:showTrackerSelection()"><img src="'.util_get_image_theme("button_choose.png").'" align="absmiddle" border="0"></a></td>
                      <tr>
                    </table>
                 </td>
                 <td><input type="button" name="CreateTemplate" value="'.$Language->getText('global','btn_create').'" onClick="onSubmitCreateTemplate()"></td>
               </tr>';
        */
        echo ' <tr>
                <td width="300">'.$Language->getText('plugin_tracker_include_type','from_xml').'</td>
                <td>
                 <input type="hidden" name="create_mode" value="">
                 <input type="file" name="file" id="file" />
                </td>
                <td><input type="submit" name="create_from_xml" value="'.$Language->getText('global','btn_create').'" /></td>
                <td><input type="submit" name="preview_xml" id="button_preview_xml" value="'.$Language->getText('global','btn_preview').'" /></td>
               </tr>';
        echo '</table>';
    
        echo '</form>
              </table>';
        $this->displayFooter($project);
    }
    
    /**
     * Display all trackers of project $project that $user is able to see
     *
     * @param Project $project The project
     * @param User    $user    The user
     *
     * @return void
     */
    public function displayAllTrackers($project, $user) {
        $hp = Codendi_HTMLPurifier::instance();
        $breadcrumbs = array();
        $toolbar = array();
        $html = '';
        $trackers = $this->getTrackerFactory()->getTrackersByGroupId($project->group_id);
        
        if (HTTPRequest::instance()->isAjax()) {
            foreach ($trackers as $tracker) {
                if ($tracker->userCanView($user)) {
                    $html .= '<a href="'.TRACKER_BASE_URL.'/?tracker='. $tracker->id .'" title="';
                    $html .= $hp->purify($tracker->description, CODENDI_PURIFIER_CONVERT_HTML);
                    $html .= '">';
                    $html .= $GLOBALS['HTML']->getImage('ic/clipboard-list.png', array('border' => 0, 'alt' => '', 'style="vertical-align:top;"')) .' ';
                    $html .= $hp->purify($tracker->name, CODENDI_PURIFIER_CONVERT_HTML);
                    $html .= '</a>';
                    $html .= '<br />';
                }
            }
            echo $html;
        } else {
            
            $this->displayHeader($project, $GLOBALS['Language']->getText('plugin_tracker', 'trackers'), $breadcrumbs, $toolbar);
            
            
            $html .= '<p>';
            if (count($trackers)) {
                $html .= $GLOBALS['Language']->getText('plugin_tracker_index','choose_tracker');
            } else {
                $html .= $GLOBALS['Language']->getText('plugin_tracker_index','no_accessible_trackers_msg');
            }
            if ($this->userCanCreateTracker($project->group_id, $user)) {
                $html .= '<br /><a id="tracker_createnewlink" href="'.TRACKER_BASE_URL.'/?group_id='. $project->group_id .'&amp;func=create">';
                $html .= $GLOBALS['HTML']->getImage('ic/add.png', array('alt' => 'add')) .' ';
                $html .= $GLOBALS['Language']->getText('plugin_tracker_index', 'create_new_tracker');
                $html .= '</a>';
            }
            $html .= '</p>';
            foreach ($trackers as $tracker) {
                if ($tracker->userCanView($user)) {
                    $html .= '<dt>';
                    if ($tracker->userCanDeleteTracker()) {
                        $html .= '<div style="float:right;">
                                <a href="'.TRACKER_BASE_URL.'/?tracker='. $tracker->id .'&amp;func=delete" 
                                   onclick="return confirm(\'Do you want to delete this tracker?\');"
                                   title=" ' . $GLOBALS['Language']->getText('plugin_tracker', 'delete_tracker', array($hp->purify($tracker->name, CODENDI_PURIFIER_CONVERT_HTML))) . '">';
                        $html .= $GLOBALS['HTML']->getImage('ic/bin_closed.png', array('alt' => 'delete'));
                        $html .= '</a></div>';
                    }
                    $html .= '<a class="direct-link-to-tracker" href="'.TRACKER_BASE_URL.'/?tracker='. $tracker->id .'">';
                    $html .= $GLOBALS['HTML']->getImage('ic/clipboard-list.png', array('border' => 0, 'alt' => '', 'style="vertical-align:top;"')) .' ';
                    $html .= $hp->purify($tracker->name, CODENDI_PURIFIER_CONVERT_HTML);
                    $html .= '</a>';
                    
                    if ($tracker->userHasFullAccess()) {
                        
                        $stats = $tracker->getStats();
                        $html .= ' <span style="font-size:0.75em">( <strong>';
                        if ($tracker->hasSemanticsStatus() && $stats['nb_total']) {
                            $html .= (int)($stats['nb_open']) .' '.$GLOBALS['Language']->getText('plugin_tracker_index','open').' / ';
                        }
                        $html .= (int)($stats['nb_total']) .' '.$GLOBALS['Language']->getText('plugin_tracker_index','total');
                        $html .= '</strong> )</span>';
                        
                        $html .= '</dt>';
                        $html .= '<dd>'. $hp->purify($tracker->description, CODENDI_PURIFIER_CONVERT_HTML);
                        $html .= $tracker->fetchStats();
                        $html .= '</dd>';
                        
                    } else {
                        $html .= '<dd>'. $hp->purify($tracker->description, CODENDI_PURIFIER_CONVERT_HTML);
                        $html .= '</dd>';
                    }
                        

                }
            }
            if ($html) {
                echo '<table cellspacing="0" cellpadding="0" border="0"><tr><td><dl class="tracker_alltrackers">';
                echo $html;
                echo '</dl></td></tr></table>';
            }
            $this->displayFooter($project);
        }
    }
    
    protected function displayCSVImportOverview($project, $group_id, $user) {
        $hp = Codendi_HTMLPurifier::instance();
        $breadcrumbs = array();
        $toolbar = array();
        $this->displayHeader($project, $GLOBALS['Language']->getText('plugin_tracker', 'trackers'), $breadcrumbs, $toolbar);
                
        $html = '';
        
        $tf = TrackerFactory::instance();
        $trackers = $tf->getTrackersByGroupId($group_id);
        
        // Show all the fields currently available in the system
        echo '<table width="100%" border="0" cellspacing="1" cellpadding="2">';
        echo ' <tr class="boxtable">';
        echo '  <td class="boxtitle">&nbsp;</td>';
        echo '  <td class="boxtitle">';
        echo '   <div align="center"><b>'.$GLOBALS['Language']->getText('plugin_tracker_import_admin','art_data_import').'</b></div>';
        echo '  </td>';
        echo '  <td class="boxtitle">';
        echo '   <div align="center"><b>'.$GLOBALS['Language']->getText('plugin_tracker_import_admin','import_format').'</b></div>';
        echo '  </td>';
        echo ' </tr>';
        
        $cpt = 0;
        foreach ($trackers as $tracker) {
            if ($tracker->userIsAdmin($user)) {
                
                echo '<tr class="'.util_get_alt_row_color($cpt).'">';
                echo ' <td><b>'.$GLOBALS['Language']->getText('plugin_tracker_import_admin','tracker').': '. $hp->purify(SimpleSanitizer::unsanitize($tracker->getName()), CODENDI_PURIFIER_CONVERT_HTML) .'</b></td>';
                echo ' <td align="center"><a href="'.TRACKER_BASE_URL.'/?tracker='.(int)($tracker->getID()).'&func=admin-csvimport">'.$GLOBALS['Language']->getText('plugin_tracker_import_admin','import').'</a></td>';
                echo ' <td align="center"><a href="'.TRACKER_BASE_URL.'/?tracker='.(int)($tracker->getID()).'&func=csvimport-showformat">'.$GLOBALS['Language']->getText('plugin_tracker_import_admin','show_format').'</a></td>';
                echo '</tr>';
                
            }
        }
        echo '</table>';
        $this->displayFooter($project);
    }
    
    /**
     * Display a selectbox to switch to a tracker of:
     *  + any projects the user is member of
     *  + an additional project
     *
     * The additionnal project may be useful for example in the ArtifactLink selector,
     * To make sure that the project of the main artifact is included.
     *
     * @param User    $user            the user
     * @param string  $separator       the separator between the title and the selectbox (eg: '<br />' or ' ')
     * @param Project $include_project the project to include in the selectbox (null if no one)
     * @param Tracker $current_tracker the current tracker (default is null, aka no current tracker)
     *
     * @return string html
     */
    public function fetchTrackerSwitcher(User $user, $separator, Project $include_project = null, Tracker $current_tracker = null) {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        
        //Projects/trackers
        $projects = $user->getProjects(true);
        if ($include_project) {
            $found = false;
            foreach ($projects as $data) {
                if ($data['group_id'] == $include_project->getGroupId()) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $projects[] = array(
                    'group_id'   => $include_project->getGroupId(),
                    'group_name' => $include_project->getPublicName(),
                );
            }
        }
        
        $html .= '<strong>';
        if ($current_tracker) {
            $html .= $hp->purify($current_tracker->getProject()->getPublicName(), CODENDI_PURIFIER_CONVERT_HTML);
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_tracker', 'tracker_switcher');
        }
        $html .= '</strong>'. $separator;
        $html .= '<select id="tracker_select_tracker">';
        if (!$current_tracker) {
            $html .= '<option selected="selected">--</option>';
        }
        $factory = TrackerFactory::instance();
        foreach ($projects as $data) {
            if ($trackers = $factory->getTrackersByGroupId($data['group_id'])) {
                foreach ($trackers as $key => $v) {
                    if ( ! $v->userCanView($user)) {
                        unset($trackers[$key]);
                    }
                }
                if ($trackers) {
                    $html .= '<optgroup label="'. $hp->purify($data['group_name'], CODENDI_PURIFIER_CONVERT_HTML) .'">';
                    foreach ($trackers as $t) {
                        $selected = $current_tracker && $t->getId() == $current_tracker->getId() ? 'selected="selected"' : '';
                        $html .= '<option '. $selected .' value="'. $t->getId() .'">';
                        $html .= $hp->purify($t->getName(), CODENDI_PURIFIER_CONVERT_HTML);
                        $html .= '</option>';
                    }
                    $html .= '</optgroup>';
                }
            }
        }
        $html .= '</select>';
        return $html;
    }
    
    /**
     *  Display a select box for the tracker list for a group
     *
     *  @param int    $group_id the project id
     *  @param string $name     the select box name
     *  @param ?      $checked  the default value
     * 
     *  @return void
     */
    protected function trackersSelectBox($group_id, $name, $checked='xzxz') {
        $hp = Codendi_HTMLPurifier::instance();
        $tracker_names = array();
        $tracker_ids   = array();
        foreach ($this->getTrackerFactory()->getTrackersByGroupId($group_id) as $tracker) {
            $tracker_names[] =  $hp->purify($tracker->name, CODENDI_PURIFIER_CONVERT_HTML);
            $tracker_ids[] = $tracker->id;
        }
        return html_build_select_box_from_arrays($tracker_ids,$tracker_names, $name, $checked);
    }
    
    /**
     * Importing Tracker from a submitted XML file
     *  
     * @param Object $project     into which the tracker is imported
     * @param string $name        the name of the tracker given by the user
     * @param string $description the description of the tracker given by the user
     * @param string $itemnate    the short name of the tracker given by the user
     * @param string $filename    The xml tracker structure
     *
     * @return Tracker null if error
     */
    protected function importTracker($project, $name, $description, $itemname, $filename) {
        //TODO: add restrictions for the file
        return $this->getTrackerFactory()->createFromXML($filename, $project->group_id, $name, $description, $itemname, $this);
    }
    
    /**
     * Preview of the tracker before import using XSL transformation
     * 
     * @param string $filename The xml tracker structure
     *
     * @return void
     */
    protected function displayTrackerPreview($filename) {
        // inject xsl reference to the xml file
        $xml = DOMDocument::load($filename);
        $xslt = $xml->createProcessingInstruction('xml-stylesheet', 'type="text/xsl" href="resources/tracker_preview.xsl"');
        $xml->insertBefore($xslt, $xml->firstChild);
        header('Content-Type: text/xml');
        echo $xml->saveXML();
    }
    
    public function duplicate($from_project_id, $to_project_id, $ugroup_mapping) {
        $this->getTrackerFactory()->duplicate($from_project_id, $to_project_id, $ugroup_mapping);
    }

    /**
     * @return Tracker_URL
     */
    protected function getUrl() {
        return new Tracker_URL();
    }

    /**
     * @return TrackerFactory
     */
    protected function getTrackerFactory() {
        return TrackerFactory::instance();
    }
    protected function getTracker_FormElementFactory() {
        return Tracker_FormElementFactory::instance();
    }
    protected function getArtifactFactory() {
        return Tracker_ArtifactFactory::instance();
    }
    protected function getArtifactReportFactory() {
        return Tracker_ReportFactory::instance();
    }
    protected function getProject($group_id) {
        return ProjectManager::instance()->getProject($group_id);
    }
    
    /**
     * Check if user has permission to create a tracker or not
     *
     * @param int  $group_id The Id of the project where the user wants to create a tracker
     * @param User $user     The user to test (current user if not defined)
     *
     * @return boolean true if user has persission to create trackers, false otherwise
     */
    function userCanCreateTracker($group_id, $user = false) {
        if (!is_a($user, 'User')) {
            $um = UserManager::instance();
            $user = $um->getCurrentUser();
        }
        return $user->isMember($group_id, 'A');
    }
    
    function search($request, $current_user) {
        if ($request->exist('tracker')) {
            $tracker_id = $request->get('tracker');
            $tracker = $this->getTrackerFactory()->getTrackerById($tracker_id);
            if ($tracker) {
                if ($tracker->userCanView($current_user)) {
                    $tracker->displaySearch($this, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'perm_denied'));
                    $GLOBALS['HTML']->redirect(TRACKER_BASE_URL.'/?group_id='. $tracker->getGroupId());
                }
            }
        } else {
            
        }
        
    }

}
?>