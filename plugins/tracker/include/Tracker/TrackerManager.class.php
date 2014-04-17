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

require_once('common/reference/ReferenceManager.class.php');
require_once 'common/templating/TemplateRendererFactory.class.php';

class TrackerManager implements Tracker_IFetchTrackerSwitcher {
    
    /**
     * Check that the service is used and the plugin is allowed for project $project
     * if it is not the case then exit with an error
     * 
     * @param Project         $project
     * @param Codendi_Request $request
     * 
     * @return bool true if success. Otherwise the process terminates.
     */
    public function checkServiceEnabled(Project $project, Codendi_Request $request) {
        if ($project->usesService('plugin_tracker')) {
            return true;
        }
        
        header("HTTP/1.0 404 Not Found");
        if (!$request->isAjax()) {
            $GLOBALS['Response']->addFeedback('error', "The project doesn't use the 'tracker v5' service");
            $GLOBALS['HTML']->redirect('/projects/'. $project->getUnixName() .'/');
        }
        exit();
    }
    
    /**
     * Check that tracker can be accessed by user
     *
     * @param Tracker         $tracker
     * @param PFUser            $user
     * @param Codendi_Request $request
     *
     * @throws Tracker_CannotAccessTrackerException
     */
    public function checkUserCanAccessTracker($tracker, $user, Codendi_Request $request) {
        $this->checkServiceEnabled($tracker->getProject(), $request);
        
        if (!$tracker->isActive()) {
            throw new Tracker_CannotAccessTrackerException($GLOBALS['Language']->getText('plugin_tracker_common_type', 'tracker_not_exist'));
        }
        if (!$tracker->userCanView($user)) {
            throw new Tracker_CannotAccessTrackerException($GLOBALS['Language']->getText('plugin_tracker_common_type', 'no_view_permission'));
        }
    }
    
    /**
     * Propagate process dispatch to sub-tracker elements
     *
     * @param Tracker_Dispatchable_Interface $object
     * @param Codendi_Request                $request
     * @param PFUser                           $user
     */
    protected function processSubElement(Tracker_Dispatchable_Interface $object, Codendi_Request $request, PFUser $user) {
        // Tracker related check
        $this->checkUserCanAccessTracker($object->getTracker(), $user, $request);
        $GLOBALS['group_id'] = $object->getTracker()->getGroupId();

        // Need specific treatment for artifact
        // TODO: transfer in Tracker_Artifact::process
        if ($object instanceof Tracker_Artifact) {
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
        } else {
            $object->process($this, $request, $user);
        }
    }
    
    /**
     * Controler
     *
     * @param Codendi_Request $request The request
     * @param PFUser            $user    The user that execute the request
     *
     * @return void
     */
    public function process($request, $user) {
        $url    = $this->getUrl();
        
        try {
            $object = $url->getDispatchableFromRequest($request, $user);
            $this->processSubElement($object, $request, $user);
        } catch (Tracker_ResourceDoesntExistException $e) {
             exit_error($GLOBALS['Language']->getText('global', 'error'), $e->getMessage());
        } catch (Tracker_CannotAccessTrackerException $e) {
            if (! $request->isAjax()) {
                $GLOBALS['Response']->addFeedback('error', $e->getMessage());
                $this->displayAllTrackers($object->getTracker()->getProject(), $user);
            } else {
                $GLOBALS['Response']->send401UnauthorizedHeader();
            }
        } catch (Tracker_NoMachingResourceException $e) {

            //show, admin all trackers
            if ((int)$request->get('group_id')) {
                $group_id = (int)$request->get('group_id');
                if ($project = $this->getProject($group_id)) {
                    if ($this->checkServiceEnabled($project, $request)) {
                        switch($request->get('func')) {
                            case 'docreate':
                                if ($this->userCanCreateTracker($group_id)) {
                                    if ($request->exist('preview_xml') && $request->get('preview_xml')) {
                                        //todo: check that a file is uploaded
                                        $this->displayTrackerPreview($_FILES["file"]["tmp_name"]);
                                    } else {
                                        $this->doCreateTracker($project, $request);
                                    }
                                } else {
                                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?group_id='. $group_id);
                                }
                                break;
                            case 'create':
                                if ($this->userCanCreateTracker($group_id)) {
                                    $this->displayCreateTracker($project, $request);
                                } else {
                                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?group_id='. $group_id);
                                }
                                break;
                            case 'check_ugroup_consistency':
                                $tracker = $this->getTrackerFactory()->getTrackerByid($request->get('template_tracker_id'));
                                if (! $tracker) {
                                    return;
                                }

                                $checker = new Tracker_UgroupPermissionsConsistencyChecker(
                                    new Tracker_UgroupPermissionsGoldenRetriever(
                                        new Tracker_PermissionsDao(),
                                        new UGroupManager()
                                    ),
                                    new UGroupManager(),
                                    new Tracker_UgroupPermissionsConsistencyMessenger()
                                );
                                $checker->checkConsistency($tracker, $project);
                                break;
                            case 'csvimportoverview':
                                $this->displayCSVImportOverview($project, $group_id, $user);
                                break;
                            case 'cross-search':
                                $controller = $this->getCrossSearchController($request);
                                $controller->search($user);
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
     * @return Tracker_CrossSearch_SearchController 
     */
    protected function getCrossSearchController(Codendi_Request $request) {
        return new Tracker_CrossSearch_SearchController(
            $request,
            ProjectManager::instance(),
            $GLOBALS['Response'],
            $this->getCrossSearchViewBuilder($request->get('group_id'), $request->getCurrentUser())
        );
    }
    
    public function displayHeader($project, $title, $breadcrumbs, $toolbar, array $params) {
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
            $service->displayHeader($title, $breadcrumbs, $toolbar, $params);
        }
    }
    
    public function displayFooter($project) {
        if ($service = $project->getService('plugin_tracker')) {
            $service->displayFooter();
        }
    }
    
    public function doCreateTracker(Project $project, Codendi_Request $request) {
        $is_error    = false;
        $new_tracker = null;
        
        $name          = trim($request->get('name'));
        $description   = trim($request->get('description'));
        $itemname      = trim($request->get('itemname'));
        $atid_template = $request->getValidated('atid_template', 'uint', 0);
        
        // First try XML
        if ($request->existAndNonEmpty('create_mode') && $request->get('create_mode') == 'xml') {
            $vFile = new Valid_File('tracker_new_xml_file');
            $vFile->required();
            if ($request->validFile($vFile)) {
                $new_tracker = $this->importTracker($project, $name, $description, $itemname, $_FILES["tracker_new_xml_file"]["tmp_name"]);
            }
        } else if ($request->existAndNonEmpty('create_mode') && $request->get('create_mode') == 'tv3') {
            $atid = $request->get('tracker_new_tv3');
            $new_tracker = $this->getTrackerFactory()->createFromTV3($atid, $project, $name, $description, $itemname);
        } else {
            // Otherwise tries duplicate
            $duplicate = $this->getTrackerFactory()->create($project->getId(), -1, $atid_template, $name, $description, $itemname);
            $new_tracker = $duplicate['tracker'];
        }

        if ($new_tracker) {
            $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?group_id='. $project->group_id .'&tracker='. $new_tracker->id);
        } else {
            $tracker_template = $this->getTrackerFactory()->getTrackerById($atid_template);
            $this->displayCreateTracker($project, $request, $name, $description, $itemname, $tracker_template);
        }
    }
    
    /**
     * Display tracker creation interface
     *
     * @param Project $project
     * @param String $name
     * @param String $description
     * @param String $itemname
     * @param Tracker      $tracker_template
     */
    public function displayCreateTracker(Project $project,
                                         Codendi_Request $request,
                                         $name = '', 
                                         $description = '', 
                                         $itemname = '',
                                         Tracker $tracker_template = null) {
        global $Language;
        $breadcrumbs = array(
            array(
                'title' => $GLOBALS['Language']->getText('plugin_tracker_index', 'create_new_tracker'),
                'url'   => TRACKER_BASE_URL.'/?group_id='. $project->group_id .'&amp;func=create'
            )
        );
        $toolbar = array();
        $params  = array();
        $this->displayHeader($project, 'Trackers', $breadcrumbs, $toolbar, $params);
        
        $hp = Codendi_HTMLPurifier::instance();

        echo '<h2>'.$Language->getText('plugin_tracker_include_type','create_tracker').'</h2>';
        
        echo '<form name="form_create" method="post" enctype="multipart/form-data" id="tracker_create_new">
          <input type="hidden" name="group_id" value="'.$project->getId().'">
          <input type="hidden" name="func" value="docreate">
          
          <table>
          <tr valign="top"><td style="padding-right:2em; border-right: 1px solid #eee;">';
          
        echo '<p>'.$Language->getText('plugin_tracker_include_type','choose_creation').'</p>';

        $create_mode = $request->get('create_mode');
        $this->displayCreateTrackerFromTemplate($create_mode, $project, $tracker_template);
        $this->displayCreateTrackerFromXML($create_mode, $project);
        $this->displayCreateTrackerFromTV3($create_mode, $project, $request->get('tracker_new_tv3'));

        echo '</td><td style="padding-left:2em;">';

        echo '<p>'. $Language->getText('plugin_tracker_include_type','create_tracker_fill_name') .'</p>
          <p>
              <label for="newtracker_name"><b>'. $Language->getText('plugin_tracker_include_artifact','name').'</b>: <font color="red">*</font></label><br />
              <input type="text" name="name" id="newtracker_name" value="'. $hp->purify($name, CODENDI_PURIFIER_CONVERT_HTML) .'" required="required" />
          </p>
          <p>
              <label for="newtracker_description"><b>'.$Language->getText('plugin_tracker_include_artifact','desc').'</b>: <font color="red">*</font><br />
              <textarea id="newtracker_description" name="description" rows="3" cols="50" required="required">'. $hp->purify($description, CODENDI_PURIFIER_CONVERT_HTML) .'</textarea>
          </p>
          <p>
              <label for="newtracker_itemname"><b>'.$Language->getText('plugin_tracker_include_type','short_name').'</b>: <font color="red">*</font></label><br />
              <input type="text" id="newtracker_itemname" name="itemname" value="'. $hp->purify($itemname, CODENDI_PURIFIER_CONVERT_HTML) .'" required="required" /><br />
              <span style="color:#999;">'.$Language->getText('plugin_tracker_include_type','avoid_spaces').'</span>
          </p>';
        
        echo '<div id="check_consistency_feedback"></div>';
        echo '<input type="submit" name="Create" value="'.$Language->getText('global','btn_create').'" id="create_new_tracker_btn" class="btn">';

        echo '</td></tr></table></form>';

        $this->displayFooter($project);
    }

    /**
     * 
     *
     */
    function displayCreateTrackerFromTemplate($requested_create_mode, Project $project, Tracker $tracker_template = null) {
        $hp = Codendi_HTMLPurifier::instance();

        $GLOBALS['Response']->includeFooterJavascriptFile(TRACKER_BASE_URL.'/scripts/TrackerTemplateSelector.js');
        $GLOBALS['Response']->includeFooterJavascriptFile(TRACKER_BASE_URL.'/scripts/TrackerCheckUgroupConsistency.js');
        
        $js = '';
        $trackers = $this->getTrackerFactory()->getTrackersByGroupId(100);
        foreach ($trackers as $tracker) {
            $js .= '<option value="'.$tracker->getId().'">'. $hp->purify($tracker->getName()) .'</option>';
        }
        $js = "codendi.tracker.defaultTemplates = '". $hp->purify($js, CODENDI_PURIFIER_JS_QUOTE) ."';";
        $GLOBALS['Response']->includeFooterJavascriptSnippet($js);
        
        $gf = new GroupFactory();
        $radio = $this->getCreateTrackerRadio('gallery', $requested_create_mode);
        echo '<h3><label>'. $radio . $GLOBALS['Language']->getText('plugin_tracker_include_type','from_tmpl').'</label></h3>';

        //
        echo '<div class="tracker_create_mode">';
        echo '<noscript>Project Id: <input type="text" name="group_id_template" value=""><br/>Tracker Id: <input type="text" name="atid_template" value=""></noscript>';

        echo '<table>';

        echo '<tr>';
        echo '<th align="left">'.$GLOBALS['Language']->getText('plugin_tracker_include_type', 'tmpl_src_prj').'</th>';
        echo '<th align="left">'.$GLOBALS['Language']->getText('plugin_tracker_include_type', 'tmpl_src_trk').'</th>';
        echo '</tr>';

        echo '<tr>';
        echo '<td valign="top">';

        $group_id_template = 100;
        $atid_template     = -1;
        if ($tracker_template) {
            $group_id_template = $tracker_template->getProject()->getID();
            $atid_template     = $tracker_template->getId();
        }
        $selectedHtml = 'selected="selected"';

        echo '<select name="group_id_template" size="15" id="tracker_new_project_list" autocomplete="off">';

        echo '<option value="100" '.($group_id_template == 100 ? $selectedHtml : '').'>'.$GLOBALS['Language']->getText('plugin_tracker_include_type', 'tmpl_src_prj_default').'</option>';

        echo '<optgroup label="'.$GLOBALS['Language']->getText('plugin_tracker_include_type', 'tmpl_src_prj_my').'">';
        $project_selected = false;
        $results = $gf->getMemberGroups();
        while ($row = db_fetch_array($results)) {
            $selected = '';
            if ($group_id_template == $row['group_id']) {
                $selected = $selectedHtml;
                $project_selected = true;
            }
            echo '<option value="'.$hp->purify($row['group_id']).'" '.($group_id_template == $row['group_id'] ? $selectedHtml : '').'>'.$hp->purify(util_unconvert_htmlspecialchars($row['group_name'])).'</option>';
        }
        echo '</optgroup>';

        $hide  = 'style="display:none;"';
        $other = '';
        if ($tracker_template && !$project_selected) {
            $hide = '';
            $other .= '<option value="'. (int)$tracker_template->getProject()->getID() .'" '. $selectedHtml .'>';
            $other .= $hp->purify(util_unconvert_htmlspecialchars($tracker_template->getProject()->getPublicName()), CODENDI_PURIFIER_CONVERT_HTML);
            $other .= '</option>';
        }
        echo '<optgroup id="tracker_new_other" '. $hide .' label="'.$GLOBALS['Language']->getText('plugin_tracker_include_type', 'tmpl_src_prj_other').'">';
        echo $other;
        echo '</optgroup>';

        echo '</select>';

        echo '<br/>'.$GLOBALS['Language']->getText('plugin_tracker_include_type', 'tmpl_src_autocomplete_desc').'<br /><input type="text" name="tracker_new_prjname" id="tracker_new_prjname" value="'.$GLOBALS['Language']->getText('plugin_tracker_include_type', 'tmpl_src_autocomplete_hint').'" />';

        echo '</td>';

        echo '<td valign="top">';
        echo '<select name="atid_template" size="15" id="tracker_list_trackers_from_project">';
        $trackers = $this->getTrackerFactory()->getTrackersByGroupId($group_id_template);
        if (count($trackers) > 0) {
            foreach ($trackers as $tracker) {
                echo '<option value="'.$tracker->getId().'" '.($atid_template == $tracker->getId() ? $selectedHtml : '').'>'. $hp->purify($tracker->getName()) .'</option>';
            }
        } else {
            echo '<option>'.$GLOBALS['Language']->getText('plugin_tracker_include_type', 'tmpl_src_no_trk').'</option>';
        }
        echo '</select>';

        echo '</td>';
        echo '</tr>';
        echo '</table>';

        echo '</div>';
    }
    
    /**
     * 
     */
    function displayCreateTrackerFromXML($requested_create_mode, Project $project) {
        $radio = $this->getCreateTrackerRadio('xml', $requested_create_mode);
        echo '<h3><label>'. $radio . $GLOBALS['Language']->getText('plugin_tracker_include_type','from_xml').'</label></h3>
              <div class="tracker_create_mode">
                <p>'.$GLOBALS['Language']->getText('plugin_tracker_include_type','from_xml_desc', TRACKER_BASE_URL.'/resources/templates/').'</p>
                <input type="file" name="tracker_new_xml_file" id="tracker_new_xml_file" />
                
              </div>';
    }

    private function displayCreateTrackerFromTV3($requested_create_mode, Project $project, $requested_template_id) {
        $hp   = Codendi_HTMLPurifier::instance();
        $html = '';
        if ($project->usesService('tracker')) {
            require_once 'common/tracker/ArtifactTypeFactory.class.php';
            $atf         = new ArtifactTypeFactory($project);
            $trackers_v3 = $atf->getArtifactTypes();
            if ($trackers_v3) {
                $radio = $this->getCreateTrackerRadio('tv3', $requested_create_mode);
                $html .= '<h3><label>'. $radio . $GLOBALS['Language']->getText('plugin_tracker_include_type','from_tv3').'</label></h3>';
                $html .= '<div class="tracker_create_mode">';
                $checked = $requested_template_id ? '' : 'checked="checked"';
                foreach ($trackers_v3 as $tracker_v3) {
                    $html .= '<p>';
                    $html .= '<label>';
                    if ($requested_template_id == $tracker_v3->getID()) {
                        $checked = 'checked="checked"';
                    }
                    $html .= '<input type="radio" name="tracker_new_tv3" value="'. $tracker_v3->getID() .'" '. $checked .' />';
                    $html .= $hp->purify(SimpleSanitizer::unsanitize($tracker_v3->getName()), CODENDI_PURIFIER_CONVERT_HTML);
                    $html .= '</label>';
                    $html .= '</p>';
                    $checked = '';
                }
                $html .= '</div>';
            }
        }
        echo $html;
    }

    public function getCreateTrackerRadio($create_mode, $requested_create_mode) {
        $checked = '';
        if (! $requested_create_mode) {
            $requested_create_mode = 'gallery';
        }
        if ($create_mode == $requested_create_mode) {
            $checked = 'checked="checked"';
        }
        return '<input type="radio" name="create_mode" value="'. $create_mode .'" '. $checked .' />';
    }

    public function displayTrackerHomeNav(Project $project) {
        $presenter = new Tracker_HomeNavPresenter($project);
        $renderer  = TemplateRendererFactory::build()->getRenderer(dirname(__FILE__).'/../../templates');
        
        $renderer->renderToPage('tracker-home-nav', $presenter);
    }
    
    /**
     * Display all trackers of project $project that $user is able to see
     *
     * @param Project $project The project
     * @param PFUser    $user    The user
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
            $http_content = '';
            foreach ($trackers as $tracker) {
                if ($tracker->userCanView($user)) {
                    $http_content .= '<option value="'.$tracker->getId().'">'.$hp->purify($tracker->getName()).'</option>';
                }
            }
            if ($http_content) {
                echo $http_content;
            } else {
                echo '<option>'. $GLOBALS['Language']->getText('plugin_tracker_include_type', 'tmpl_src_no_trk') .'</option>';
            }
            echo $html;
        } else {
            $params = array();

            $this->displayHeader($project, $GLOBALS['Language']->getText('plugin_tracker', 'trackers'), $breadcrumbs, $toolbar, $params);
            $this->displayTrackerHomeNav($project);
            
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
                    $html .= '<div class="tracker_homepage_info">';
                    $html .= '<a class="direct-link-to-tracker" href="'.TRACKER_BASE_URL.'/?tracker='. $tracker->id .'">';
                    $html .= '<i class="icon-circle tracker_color_info '. $tracker->getColor() .'"></i>';
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
                        
                    $html .= '</div>';

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
        $toolbar     = array();
        $params      = array();
        $this->displayHeader($project, $GLOBALS['Language']->getText('plugin_tracker', 'trackers'), $breadcrumbs, $toolbar, $params);
                
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
    
    public function fetchTrackerSwitcher(PFUser $user, $separator, Project $include_project = null, Tracker $current_tracker = null) {
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
        if (($xml_element = simplexml_load_file($filename)) !== false) {
            return $this->getTrackerFactory()->createFromXML($xml_element, $project->group_id, $name, $description, $itemname, $this);
        }
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
    
    /**
     * On project creation, copy template trackers to destination project
     *
     * @param Integer $from_project_id
     * @param Integer $to_project_id
     * @param Array   $ugroup_mapping
     */
    public function duplicate($from_project_id, $to_project_id, $ugroup_mapping) {
        $this->getTrackerFactory()->duplicate($from_project_id, $to_project_id, $ugroup_mapping);
        $this->duplicateReferences($from_project_id);
    }

    /**
     * On project creation, copy all 'plugin_tracker_artifact' references not attached to a tracker
     *
     * @param Integer $from_project_id
     */
    protected function duplicateReferences($from_project_id) {
        // Index by shortname
        foreach ($this->getTrackerFactory()->getTrackersByGroupId($from_project_id) as $tracker) {
            $trackers[$tracker->getItemName()] = $tracker;
        }
        
        // Loop over references
        $refMgr     = $this->getReferenceManager();
        $references = $refMgr->getReferencesByGroupId($from_project_id);
        foreach ($references as $reference) {
            if (!isset($trackers[$reference->getKeyword()])) {
                $refMgr->createReference($reference);
            }
        }
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
     * @return ReferenceManager
     */
    protected function getReferenceManager() {
        return ReferenceManager::instance();
    }
    
    /**
     * Check if user has permission to create a tracker or not
     *
     * @param int  $group_id The Id of the project where the user wants to create a tracker
     * @param PFUser $user     The user to test (current user if not defined)
     *
     * @return boolean true if user has persission to create trackers, false otherwise
     */
    public function userCanCreateTracker($group_id, $user = false) {
        if (!($user instanceof PFUser)) {
            $um = UserManager::instance();
            $user = $um->getCurrentUser();
        }
        return $user->isMember($group_id, 'A');
    }

    public function userCanAdminAllProjectTrackers($user = null) {
        if (! $user instanceof PFUser) {
            $um = UserManager::instance();
            $user = $um->getCurrentUser();
        }

        $permission = new User_ForgeUserGroupPermission_TrackerAdminAllProjects();
        $user       = UserManager::instance()->getCurrentUser();
        $forge_ugroup_permissions_manager = new User_ForgeUserGroupPermissionsManager(
            new User_ForgeUserGroupPermissionsDao()
        );

        return $forge_ugroup_permissions_manager->doesUserHavePermission($user, $permission);
    }
    
    public function search($request, $current_user) {
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
        }
    }

    /**
     * Mark as deleted all trackers of a given project
     *
     * @param Integer $groupId The project id
     *
     * @return Boolean
     */
    public function deleteProjectTrackers($groupId) {
        $delete_status = true;
        $trackers = $this->getTrackerFactory()->getTrackersByGroupId($groupId);
        if (!empty($trackers)) {
            foreach ($trackers as $tracker) {
                if (!$this->getTrackerFactory()->markAsDeleted($tracker->getId())) {
                    $delete_status = false;
                }
            }
        }
        return $delete_status;
    }

    public function getCrossSearch(array $art_link_column_field_ids) {
        $hierarchy_factory    = new Tracker_HierarchyFactory(new Tracker_Hierarchy_Dao(), $this->getTrackerFactory(), $this->getArtifactFactory());
        $shared_field_factory = new Tracker_CrossSearch_SharedFieldFactory();
        $dao                  = new Tracker_CrossSearch_SearchDao();
        $search               = new Tracker_CrossSearch_Search($shared_field_factory, $dao, $hierarchy_factory, $art_link_column_field_ids);
        return $search;
    }
    
    public function getCrossSearchViewBuilder($group_id, PFUser $user) {
        $form_element_factory    = Tracker_FormElementFactory::instance();
        $planning_trackers       = $this->getPlanningTrackers($group_id, $user);
        $art_link_field_ids      = $form_element_factory->getArtifactLinkFieldsOfTrackers($planning_trackers);
        $criteria_builder        = $this->getCriteriaBuilder($user, $planning_trackers);
        
        return new Tracker_CrossSearch_SearchViewBuilder(
            $form_element_factory, 
            $this->getTrackerFactory(), 
            $this->getCrossSearch($art_link_field_ids), 
            $criteria_builder
        );
    }

    /**
     * Return the 'Planning' tracker (tracker we should be able to use artifacts to perform search.
     * 
     * This method hard code dependency with agile dashboard and planning stuff.
     * It should be renamed later on when planning definition is clearly defined.
     * 
     * @param Integer $group_id
     * 
     * @return Array of Integer
     */
    private function getPlanningTrackers($group_id, PFUser $user) {
        $trackers = array();
        @include_once dirname(__FILE__).'/../../../agiledashboard/include/Planning/PlanningFactory.class.php';
        if (class_exists('PlanningFactory')) {
            $planning_factory = new PlanningFactory(new PlanningDao(), $this->getTrackerFactory(), Tracker_FormElementFactory::instance());
            $trackers = $planning_factory->getPlanningTrackers($group_id, $user);
        }
        return $trackers;
    }

    public function getCriteriaBuilder($user, $trackers) {
        $artifact_factory        = Tracker_ArtifactFactory::instance();
        $semantic_title_factory  = Tracker_Semantic_TitleFactory::instance();
        $semantic_status_factory = Tracker_Semantic_StatusFactory::instance();
        $tracker_factory         = TrackerFactory::instance();
        $semantic_value_factory  = new Tracker_CrossSearch_SemanticValueFactory($artifact_factory, $semantic_title_factory, $semantic_status_factory, $tracker_factory);

        return new Tracker_CrossSearch_CriteriaBuilder(Tracker_FormElementFactory::instance(), $semantic_value_factory, $trackers);

    }

    /**
     * Get all trackers having at least on active date reminder
     *
     * @return Array
     */
    protected function getTrackersHavingDateReminders() {
        $trackers = array();
        $dao = new Tracker_DateReminderDao();
        $dar = $dao->getTrackersHavingDateReminders();
        if ($dar && !$dar->isError()) {
            foreach ($dar as $row) {
                $trackers[] = $this->getTrackerFactory()->getTrackerById($row['tracker_id']);
            }
        }
        return $trackers;
    }

    /**
     * Send Date reminder
     *
     * @return Void
     */
    public function sendDateReminder() {
        $logger = new BackendLogger();
        $logger->debug("[TDR] Start processing date reminders");
        $trackers       = $this->getTrackersHavingDateReminders();
        foreach ($trackers as $tracker) {
            $logger->debug("[TDR] Processing date reminders for tracker ".$tracker->getProject()->getUnixName()." / ".$tracker->getItemName()." (id: ".$tracker->getId().")");
            $dateReminderManager = new Tracker_DateReminderManager($tracker);
            $dateReminderManager->process();
        }
        $logger->debug("[TDR] End processing date reminders");
    }

    public function exportToXml($group_id, SimpleXMLElement $xml_content) {
        $xml_field_id = 0;
        foreach ($this->getTrackerFactory()->getTrackersByGroupId($group_id) as $tracker) {
            if ($tracker->isActive()) {
                $child = $xml_content->addChild('tracker');
                $tracker->exportToXML($child, $xml_field_id);
            }
        }
        return $xml_content;
    }
}

?>
