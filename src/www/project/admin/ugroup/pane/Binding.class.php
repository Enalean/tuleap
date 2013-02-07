<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
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

class Project_Admin_UGroup_Pane_Binding extends Project_Admin_UGroup_Pane {
    const IDENTIFIER = 'binding';

    /**
     * @var UGroupBinding
     */
    private $ugroup_binding;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(UGroup $ugroup, UGroupBinding $ugroup_binding) {
        parent::__construct($ugroup);
        $this->ugroup_binding = $ugroup_binding;
        $this->project_manager = ProjectManager::instance();
    }

    public function getContent() {
        $content = '';
        $clones = $this->ugroup_binding->getUGroupsByBindingSource($this->ugroup->getId());
        $content .= $this->getAdd();
        $content .= '<h3>'.$GLOBALS['Language']->getText('project_ugroup_binding', 'binding_sources').'</h3>';
        $content .= $this->getClonesHTML($clones);
        return $content;
    }

    private function getAdd() {
        $content = '';
        $urlAdd     = '/project/admin/editugroup.php?group_id='.$this->ugroup->getProjectId().'&ugroup_id='.$this->ugroup->getId().'&func=edit&pane=ugroup_binding';
        $linkAdd    = '<br/><a href="'.$urlAdd.'">- '.$GLOBALS['Language']->getText('project_ugroup_binding', 'edit_binding_title').'</a><br/>';
        if ($binding = $this->displayUgroupBinding()) {
            $content .= $binding;
        } else {
            $GLOBALS['Response']->redirect('/project/admin/editugroup.php?group_id='.$this->ugroup->getProjectId().'&ugroup_id='.$this->ugroup->getId().'&func=edit&pane=ugroup_binding');
        }
        $content .= $linkAdd;
        return $content;
    }

    /**
     * Display the binding pane content
     * 
     * @return String
     */
    private function displayUgroupBinding() {
        $html = '';
        $ugroupUpdateUsersAllowed = !$this->ugroup->isBound();
        if ($ugroupUpdateUsersAllowed) {
            $em = EventManager::instance();
            $em->processEvent('ugroup_table_row', array('row' => array('group_id' => $this->ugroup->getProjectId(), 'ugroup_id' => $this->ugroup->getId()), 'html' => &$html));
        }
        return $html;
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
            foreach ($clones as $clone) {
                $project = $this->project_manager->getProject($clone['group_id']);
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

    public function getIdentifier() {
        return self::IDENTIFIER;
    }

    public function getTitle() {
        return $GLOBALS['Language']->getText('project_admin_utils', 'ugroup_binding');
    }

    public function getUrl() {
        return '/project/admin/editugroup.php?group_id='.$this->ugroup->getProjectId().'&ugroup_id='.$this->ugroup->getId().'&func=edit&pane='.self::IDENTIFIER;
    }
}

?>
