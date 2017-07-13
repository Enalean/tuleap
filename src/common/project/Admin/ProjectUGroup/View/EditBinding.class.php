<?php
/**
 * Copyright Enalean (c) 2011-2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class Project_Admin_UGroup_View_EditBinding extends Project_Admin_UGroup_View_Binding {

    public function __construct(ProjectUGroup $ugroup, UGroupBinding $ugroup_binding, $source_project_id) {
        parent::__construct($ugroup, $ugroup_binding);
        $this->source_project_id = $source_project_id;
    }

    public function getContent() {
        $currentProject = null;
        $currentSource  = $this->ugroup->getSourceGroup();
        if ($currentSource) {
            $currentProject = $currentSource->getProject();
            if ($currentProject && $currentProject->userIsAdmin()) {
                if (!$this->source_project_id) {
                    $this->source_project_id = $currentProject->getID();
                }
            }
        }

        $html = '<h3>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'current_binding').'</h3>';
        $html .= $this->getCurrentBindingHTML($currentProject, $currentSource);

        $html .= '<h3>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'edit_binding').'</h3>';
        $html .= '<form action="" method="post" class="form-inline">';
        $html .= '<label>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'source_project').'&nbsp;';
        $html .= $this->getProjectsSelect($this->ugroup->getProjectId(), $this->source_project_id).'</label>';
        $html .= '<noscript><input type="submit" value="Select Project"/></noscript></form>';

        $sourceProject = $this->project_manager->getProject($this->source_project_id);
        if ($this->source_project_id && $sourceProject->userIsAdmin()) {
            $html .= '<form action="" method="post" class="form-inline">';
            $html .= '<input type="hidden" name="source_project" value="'.$this->source_project_id.'" />';
            $html .= '<input type="hidden" name="action" value="add_binding" />';
            $html .= '<label>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'source_ugroup').'&nbsp;';
            $html .= $this->getUgroupSelect($this->source_project_id, $currentSource).'</label>&nbsp;';
            $html .= '<input class="btn" type="submit" value="'.$GLOBALS['Language']->getText('project_ugroup_binding', 'edit_binding').'"/></form>';
        }

        return $html;
    }

    /**
     * Get the HTML output for current binding
     *
     * @param Project $currentProject Project of the currently bound ugroup
     * @param ProjectUGroup  $currentSource  Currently bound ugroup
     *
     * @return String
     */
    private function getCurrentBindingHTML(Project $currentProject = null, ProjectUGroup $currentSource = null) {
        if ($currentSource) {
            $currentBindHTML = '';
            if ($currentSource && $currentProject->userIsAdmin()) {
                $purifier         = Codendi_HTMLPurifier::instance();
                $currentBindHTML .= $GLOBALS['Language']->getText(
                    'project_ugroup_binding', 'current_binded',
                    array(
                        '<a href="/project/admin/editugroup.php?group_id='.urlencode($currentProject->getID()).'&ugroup_id='
                            .urlencode($currentSource->getId()).'&func=edit" ><b>'.$purifier->purify($currentSource->getName()).'</b></a>',
                        '<a href="/projects/'. urlencode($currentProject->getUnixName()).'" ><b>'.$purifier->purify($currentProject->getUnconvertedPublicName()).'</b></a>'
                    )
                );
            }
            $currentBindHTML .= '<form action="" method="post"><input type="hidden" name="action" value="remove_binding" /><input type="submit" value="'
                .$GLOBALS['Language']->getText('project_ugroup_binding', 'remove_binding').'"/></form>';
        } else {
            $currentBindHTML = $GLOBALS['Language']->getText('project_ugroup_binding', 'no_binding');
        }
        return $currentBindHTML;
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
        $purifier = Codendi_HTMLPurifier::instance();

        $projects       = UserManager::instance()->getCurrentUser()->getProjects(true);
        $projectSelect  = '<select name="source_project" onchange="this.form.submit()" >';
        $projectSelect .= '<option value="" >'.$GLOBALS['Language']->getText('global', 'none').'</option>';
        foreach ($projects as $project) {
            if ($groupId != $project['group_id']) {
                $project = $this->project_manager->getProject($project['group_id']);
                if ($project->userIsAdmin()) {
                    $ugroupList = $this->getUgroupList($project->getID());
                    if (!empty($ugroupList)) {
                        $selected = '';
                        if ($sourceProject == $project->getID()) {
                            $selected = 'selected="selected"';
                        }
                        $projectSelect .= '<option value="'. $purifier->purify($project->getID()) .'" '.$selected.' >'. $purifier->purify($project->getUnconvertedPublicName()).'</option>';
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
     * @param ProjectUGroup  $currentSource Currently bound ugroup
     *
     * @return Array
     */
    private function getUgroupList($sourceProject, ProjectUGroup $currentSource = null) {
        $ugroupList = array();
        $selected   = false;
        $ugroups    = ugroup_db_get_existing_ugroups($sourceProject);
        while ($ugroup = db_fetch_array($ugroups)) {
            $userGroup  = new ProjectUGroup(array('ugroup_id' => $ugroup['ugroup_id']));
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
     * @param ProjectUGroup  $currentSource Currently bound ugroup
     *
     * @return String
     */
    private function getUgroupSelect($sourceProject, ProjectUGroup $currentSource = null) {
        $purifier = Codendi_HTMLPurifier::instance();

        $ugroupList = $this->getUgroupList($sourceProject, $currentSource);
        $ugroupSelect  = '<select name="source_ugroup" >';
        $ugroupSelect .= '<option value="" >'.$GLOBALS['Language']->getText('global', 'none').'</option>';
        foreach ($ugroupList as $ugroup) {
            $selected = '';
            if ($ugroup['selected']) {
                $selected = 'selected="selected"';
            }
            $ugroupSelect .= '<option value="'.$purifier->purify($ugroup['ugroup_id']).'" '.$selected.' >'.$purifier->purify($ugroup['name']).'</option>';
        }
        $ugroupSelect .= '</select>';
        return $ugroupSelect;
    }
}
