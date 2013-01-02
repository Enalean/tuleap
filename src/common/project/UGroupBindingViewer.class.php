<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

require_once('common/project/UGroupManager.class.php');
require_once('common/project/UGroupBinding.class.php');

/**
 * UGroup binding viewer
 */
class UGroupBindingViewer {

    /**
     * @var UGroupBinding
     */
    private $ugroupBinding;

    /**
     * @var ProjectManager
     */
    private $projectManager;

    /**
     * Constructor of the class
     *
     * @param UGroupBinding  $ugroupBinding  Ugroup binding instance
     * @param ProjectManager $projectManager ProjectManager instance
     *
     * @return Void
     */
    public function __construct(UGroupBinding $ugroupBinding, ProjectManager $projectManager) {
        $this->ugroupBinding  = $ugroupBinding;
        $this->projectManager = $projectManager;
    }

    /**
     * @deprecated
     * The form that will be displayed to add/edit user group binding
     *
     * @param Integer $groupId         Id of the current project
     * @param Integer $ugroupId        Id of the user group
     * @param Integer $sourceProjectId Id of the project from which the source ugroup may be taken
     *
     * @return String
     */
    public function getHTMLContent($groupId, $ugroupId, $sourceProjectId = null) {
        $currentProject = null;
        $currentSource  = null;
        $dar = $this->ugroupBinding->getUGroupManager()->getUgroupBindingSource($ugroupId);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row            = $dar->getRow();
            $currentSource  = $this->ugroupBinding->getUGroupManager()->getById($row['source_id']);
            $currentProject = $this->projectManager->getProject($row['group_id']);
            if ($currentProject && $currentProject->userIsAdmin()) {
                if (!$sourceProjectId) {
                    $sourceProjectId = $currentProject->getID();
                }
            }
        }
        $clones = $this->ugroupBinding->getUGroupsByBindingSource($ugroupId);
        $html = '<h3>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'current_binding').'</h3>';
        $html .= $this->getCurrentBindingHTML($currentProject, $currentSource);
        $html .= '<h3>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'binding_sources').'</h3>';
        $html .= $this->getClonesHTML($clones);

        $html .= '<h3>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'edit_binding').'</h3>';
        $html .= '<table>';
        $html .= '<tr><td>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'source_project').'</td><td><form action="" method="post">'.$this->getProjectsSelect($groupId, $sourceProjectId).'</td>';
        $html .= '<td><noscript><input type="submit" value="Select Project"/></noscript></form></td></tr>';

        $sourceProject = $this->projectManager->getProject($sourceProjectId);
        if ($sourceProjectId && $sourceProject->userIsAdmin()) {
            $html .= '<tr><td>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'source_ugroup').'</td>';
            $html .= '<td><form action="" method="post">';
            $html .= '<input type="hidden" name="source_project" value="'.$sourceProjectId.'" />';
            $html .= '<input type="hidden" name="action" value="add_binding" />'.$this->getUgroupSelect($sourceProjectId, $currentSource).'</td>';
            $html .= '<td><input type="submit" value="'.$GLOBALS['Language']->getText('project_ugroup_binding', 'edit_binding').'"/></form></td></tr>';
        }
        $html .= '</table>';
        return $html;
    }

    public function getUgtoupBindingPaneContent($groupId, $ugroupId, $sourceProjectId = null) {
        $currentProject = null;
        $currentSource  = null;
        $dar = $this->ugroupBinding->getUGroupManager()->getUgroupBindingSource($ugroupId);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row            = $dar->getRow();
            $currentSource  = $this->ugroupBinding->getUGroupManager()->getById($row['source_id']);
            $currentProject = $this->projectManager->getProject($row['group_id']);
            if ($currentProject && $currentProject->userIsAdmin()) {
                if (!$sourceProjectId) {
                    $sourceProjectId = $currentProject->getID();
                }
            }
        }

        $html = '<h3>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'current_binding').'</h3>';
        $html .= $this->getCurrentBindingHTML($currentProject, $currentSource);

        $html .= '<h3>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'edit_binding').'</h3>';
        $html .= '<table>';
        $html .= '<tr><td>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'source_project').'</td><td><form action="" method="post">'.$this->getProjectsSelect($groupId, $sourceProjectId).'</td>';
        $html .= '<td><noscript><input type="submit" value="Select Project"/></noscript></form></td></tr>';

        $sourceProject = $this->projectManager->getProject($sourceProjectId);
        if ($sourceProjectId && $sourceProject->userIsAdmin()) {
            $html .= '<tr><td>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'source_ugroup').'</td>';
            $html .= '<td><form action="" method="post">';
            $html .= '<input type="hidden" name="source_project" value="'.$sourceProjectId.'" />';
            $html .= '<input type="hidden" name="action" value="add_binding" />'.$this->getUgroupSelect($sourceProjectId, $currentSource).'</td>';
            $html .= '<td><input type="submit" value="'.$GLOBALS['Language']->getText('project_ugroup_binding', 'edit_binding').'"/></form></td></tr>';
        }
        $html .= '</table>';
        return $html;
    }

    public function getUsagePaneContent($groupId, $ugroupId) {
        $clones = $this->ugroupBinding->getUGroupsByBindingSource($ugroupId);
        $html .= '<h3>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'binding_sources').'</h3>';
        $html .= $this->getClonesHTML($clones);
        return $html;
    }

    /**
     * Get the HTML output for current binding
     *
     * @param Project $currentProject Project of the currently bound ugroup
     * @param UGroup  $currentSource  Currently bound ugroup
     *
     * @return String
     */
    private function getCurrentBindingHTML(Project $currentProject = null, UGroup $currentSource = null) {
        if ($currentSource) {
            $currentBindHTML = '';
            if ($currentSource && $currentProject->userIsAdmin()) {
                $currentBindHTML .= $GLOBALS['Language']->getText('project_ugroup_binding', 'current_binded', array('<a href="/project/admin/editugroup.php?group_id='.$currentProject->getID().'&ugroup_id='.$currentSource->getId().'&func=edit" ><b>'.$currentSource->getName().'</b></a>', '<a href="/projects/'.$currentProject->getUnixName().'" ><b>'.$currentProject->getPublicName().'</b></a>'));
            }
            $currentBindHTML .= '<form action="" method="post"><input type="hidden" name="action" value="remove_binding" /><input type="submit" value="'.$GLOBALS['Language']->getText('project_ugroup_binding', 'remove_binding').'"/></form>';
        } else {
            $currentBindHTML = $GLOBALS['Language']->getText('project_ugroup_binding', 'no_binding');
        }
        return $currentBindHTML;
    }

    /**
     * Get the HTML output for ugroups bound to the current one
     *
     * @param Array $clones List of ugroups bound to this one
     *
     * @return String
     */
    private function getClonesHTML($clones) {
        $clonesHTML = '<table>';
        if (!empty($clones)) {
            $clonesHTML .= html_build_list_table_top(array($GLOBALS['Language']->getText('project_reference', 'ref_scope_P'), $GLOBALS['Language']->getText('project_ugroup_binding', 'ugroup')), false, false, false);
            $count      = 0;
            $i          = 0;
            foreach ($clones as $cloneId => $clone) {
                $project = $this->projectManager->getProject($clone['group_id']);
                if ($project->userIsAdmin()) {
                    $clonesHTML .= '<tr class="'. html_get_alt_row_color(++$i) .'"><td><a href="/projects/'.$project->getUnixName().'" >'.$project->getPublicName().'</a></td><td><a href="/project/admin/ugroup.php?group_id='.$project->getID().'" >'.$clone['cloneName'].'</a></td></tr>';
                } else {
                    $count ++;
                }
            }
            if ($count) {
                $clonesHTML .= '<tr class="'. html_get_alt_row_color(++$i) .'" colspan="2" ><td>and '.$count.' other ugroups you\'re not allowed to administrate</td></tr>';
            }
        } else {
            $clonesHTML .= '<tr><td>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'not_source').'</td></tr>';
        }
        $clonesHTML .= '</table>';
        return $clonesHTML;
    }

    /**
     * Get the HTML select listing the source projects
     *
     * @param Integer $groupId       Id of the project
     * @param Integer $sourceProject Id of the current soucrce project
     *
     * @return String
     */
    private function getProjectsSelect($groupId, $sourceProject) {
        $projects       = UserManager::instance()->getCurrentUser()->getProjects(true);
        $projectSelect  = '<select name="source_project" onchange="this.form.submit()" >';
        $projectSelect .= '<option value="" >'.$GLOBALS['Language']->getText('global', 'none').'</option>';
        foreach ($projects as $project) {
            if ($groupId != $project['group_id']) {
                $project = $this->projectManager->getProject($project['group_id']);
                if ($project->userIsAdmin()) {
                    $ugroupList = $this->getUgroupList($project->getID());
                    if (!empty($ugroupList)) {
                        $selected = '';
                        if ($sourceProject == $project->getID()) {
                            $selected = 'selected="selected"';
                        }
                        $projectSelect .= '<option value="'.$project->getID().'" '.$selected.' >'.$project->getPublicName().'</option>';
                    }
                }
            }
        }
        $projectSelect .= '</select>';
        return $projectSelect;
    }

    /**
     * Get the list of source ugroups by project
     *
     * @param Integer $sourceProject Id of the current soucrce project
     * @param UGroup  $currentSource Currently bound ugroup
     *
     * @return Array
     */
    private function getUgroupList($sourceProject, UGroup $currentSource = null) {
        $ugroupList = array();
        $selected   = false;
        $ugroups    = ugroup_db_get_existing_ugroups($sourceProject);
        while ($ugroup = db_fetch_array($ugroups)) {
            $userGroup  = new UGroup(array('ugroup_id' => $ugroup['ugroup_id']));
            if (!$userGroup->isBound()) {
                if ($currentSource && $currentSource->getId() == $ugroup['ugroup_id']) {
                    $selected = true;
                }
                $ugroupList[] = array('ugroup_id' => $ugroup['ugroup_id'], 'name' => $ugroup['name'], 'selected' => $selected);
            }
        }
        return $ugroupList;
    }

    /**
     * Get the HTML select listing the source ugroups by project
     *
     * @param Integer $sourceProject Id of the current soucrce project
     * @param UGroup  $currentSource Currently bound ugroup
     *
     * @return String
     */
    private function getUgroupSelect($sourceProject, UGroup $currentSource = null) {
        $ugroupList = $this->getUgroupList($sourceProject, $currentSource);
        $ugroupSelect  = '<select name="source_ugroup" >';
        $ugroupSelect .= '<option value="" >'.$GLOBALS['Language']->getText('global', 'none').'</option>';
        foreach ($ugroupList as $ugroup) {
            $selected = '';
            if ($ugroup['selected']) {
                $selected = 'selected="selected"';
            }
            $ugroupSelect .= '<option value="'.$ugroup['ugroup_id'].'" '.$selected.' >'.$ugroup['name'].'</option>';
        }
        $ugroupSelect .= '</select>';
        return $ugroupSelect;
    }

}

?>