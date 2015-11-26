<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once 'Project.class.php';
require_once 'Project_InvalidShortName_Exception.class.php';
require_once 'Project_InvalidFullName_Exception.class.php';
require_once 'Project_Creation_Exception.class.php';
require_once 'common/valid/Rule.class.php';

/**
 * Manage creation of a new project in the forge.
 * 
 * For now, mainly a wrapper for create_project method
 */
class ProjectCreator {
    /**
     * @var ProjectManager 
     */
    private $projectManager;
    
    /**
     * @var Rule_ProjectName
     */
    var $ruleShortName;
    
    /**
     * @var Rule_ProjectFullName
     */
    var $ruleFullName;

    public function __construct(ProjectManager $projectManager) {
        $this->ruleShortName  = new Rule_ProjectName();
        $this->ruleFullName   = new Rule_ProjectFullName();
        $this->projectManager = $projectManager;
    }

    /**
     * Build a new project
     *
     * @param Array $data project data
     * @return Project created
     */
    public function build($data){
        if (!$this->ruleShortName->isValid($data['project']['form_unix_name'])) {
            throw new Project_InvalidShortName_Exception($this->ruleShortName->getErrorMessage());
        }
        if (!$this->ruleFullName->isValid($data['project']['form_full_name'])) {
            throw new Project_InvalidFullName_Exception($this->ruleFullName->getErrorMessage());
        }

        $id = $this->create_project($data);
        if ($id) {
            return $this->projectManager->getProject($id);
        }
        throw new Project_Creation_Exception();
    }

    /**
     * Create a new project
     *
     * $data['project']['form_unix_name']
     * $data['project']['form_full_name']
     * $data['project']['form_short_description']
     * $data['project']['built_from_template']
     * $data['project']['is_test']
     * $data['project']['is_public']
     * $data['project']["form_".$descfieldsinfos[$i]["group_desc_id"]]
     * foreach($data['project']['trove'] as $root => $values);
     * $data['project']['services'][$arr['service_id']]['is_used'];
     * $data['project']['services'][$arr['service_id']]['server_id'];
     * 
     * @param String $shortName
     * @param String $publicName
     * @param Array $data
     * 
     * @return Project
     */
    public function create($shortName, $publicName, $data) {
        $data['project']['form_unix_name'] = $shortName;
        $data['project']['form_full_name'] = $publicName;

        return $this->build($data);
    }

    protected function create_project($data) {
        include_once 'www/project/create_project.php';
        return create_project($data);
    }

}

?>
