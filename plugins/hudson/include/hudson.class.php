<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 * 
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 *
 * hudson controler
 */

require_once('common/mvc/Controler.class.php');
require_once('hudsonViews.class.php');
require_once('hudsonActions.class.php');
/**
 * hudson */
class hudson extends Controler {
    
    private $themePath;
    
    function hudson() {
        $p = PluginFactory::instance()->getPluginByName('hudson');
        $this->themePath = $p->getThemePath();
    }
    
    function getThemePath() {
        return $this->themePath;
    }
    function getIconsPath() {
        return $this->themePath . "/images/ic/";
    }
    
    function request() {
        $request =& HTTPRequest::instance();
        $vgi = new Valid_GroupId();
        $vgi->required();
        if ($request->valid($vgi)) {
            $group_id = $request->get('group_id');
            $project = project_get_object($group_id);
            if ($project->usesService('hudson')) {
                
                switch($request->get('action')) {
                    case 'add_job':
                        if (user_ismember($group_id,'A')) {
                            if ( $request->exist('hudson_job_url') && trim($request->get('hudson_job_url') != '') ) {
                                $this->action = 'addJob';
                            } else {
                                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson','job_url_missing'));
                            }
                            $this->view = 'projectOverview';
                        } else {
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
                            $this->view = 'projectOverview';
                        }
                        break;
                    case 'edit_job':
                        if (user_ismember($group_id,'A')) {
                            if ($request->exist('job_id')) {
                                $this->view = 'editJob';
                            } else {
                                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson','job_id_missing'));
                            }
                        } else {
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
                            $this->view = 'projectOverview';
                        }
                        break;
                    case 'update_job':
                        if (user_ismember($group_id,'A')) {
                            if ($request->exist('job_id')) {
                                if ($request->exist('new_hudson_job_url') && $request->get('new_hudson_job_url') != '') {
                                    $this->action = 'updateJob';
                                } else {
                                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson','job_url_missing'));
                                }
                            } else {
                                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson','job_id_missing'));
                            }
                            $this->view = 'projectOverview';
                        } else {
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
                            $this->view = 'projectOverview';
                        }
                        break;
                    case 'delete_job':
                        if (user_ismember($group_id,'A')) {
                            if ($request->exist('job_id')) {
                                $this->action = 'deleteJob';
                            } else {
                                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson','job_id_missing'));
                            }
                            $this->view = 'projectOverview';
                        } else {
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
                            $this->view = 'projectOverview';
                        }
                        break;
                    case "view_job":
                        $this->view = 'job_details';
                        break;
                    case "view_build":
                        $this->view = 'build_number';
                        break;
                    case "view_last_build":
                        $this->view = 'last_build';
                        break;
                    case "view_last_test_result":
                        $this->view = 'last_test_result';
                        break;
                    case "view_test_trend":
                        $this->view = 'test_trend';
                        break;
                    default:
                        $this->view = 'projectOverview';
                        break;
                }
                
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson','service_not_used'));
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson','group_id_missing'));
        }
    }
}

?>