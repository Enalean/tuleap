<?php
/**
 * Copyright (c) Enalean, 2014 - 2016. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace DataInit;

use \PFUser;
use \Project;
use ReferenceManager;
use Tuleap\Project\UgroupDuplicator;

class ProjectHelper {

    /**
     * @var \ProjectCreator
     */
    private $project_creator;

    /**
     * @var \ProjectXMLImporter
     */
    private $xml_importer;

    public function __construct() {
        $ugroup_duplicator  = new UgroupDuplicator(new UGroupDao(), new UGroupManager());
        $reference_manager  = new ReferenceManager();

        $this->project_creator = new \ProjectCreator(
            \ProjectManager::instance(),
            $reference_manager,
            $ugroup_duplicator,
            false,
            false
        );

        $user_manager       = \UserManager::instance();
        $this->xml_importer = new \ProjectXMLImporter(
            \EventManager::instance(),
            \ProjectManager::instance(),
            $user_manager,
            new \XML_RNGValidator(),
            new \UGroupManager(),
            new \XMLImportHelper($user_manager),
            \ServiceManager::instance(),
            new \ProjectXMLImporterLogger(),
            $ugroup_duplicator
        );
    }

    /**
     * Instantiates a project with user, groups, admins ...
     *
     * @param string $project_short_name
     * @param string $project_long_name
     * @param string $is_public
     * @param array  $project_members
     * @param array  $project_admins
     */
    public function createProject(
        $project_short_name,
        $project_long_name,
        $is_public,
        array $project_members,
        array $project_admins
    ) {

        $first_admin = array_shift($project_admins);
        \UserManager::instance()->setCurrentUser($first_admin);

        $project = $this->project_creator->create($project_short_name, $project_long_name, array(
            'project' => array(
                'form_short_description' => '',
                'is_test'                => false,
                'is_public'              => $is_public,
                'services'               => $this->getServices(),
                'built_from_template'    => 100,
            )
        ));

        \ProjectManager::instance()->activate($project);

        foreach ($project_members as $project_member) {
            var_dump('Add member '.$project_member->getUserName());
            $this->addMembersToProject($project, $project_member);
        }

        foreach ($project_admins as $project_admin) {
            var_dump('Add admin '.$project_member->getUserName());
            $this->addAdminToProject($project, $project_admin);
        }
        var_dump('Project created');
        return $project;
    }

    private function getServices() {
        $services = array();
        $template = \ProjectManager::instance()->getProject(100);
        foreach ($template->getServices() as $key => $service) {
            $is_used = $service->isActive() && $service->isUsed();
            $services[$service->getId()]['is_used'] = $is_used;
        }
        return $services;
    }

    public function importTemplateInProject(Project $project, PFUser $user, $template_path) {
        \UserManager::instance()->forceLogin($user->getUserName());
        var_dump('Import Template');
        $this->xml_importer->import($project->getID(), $template_path);
        var_dump('Template imported');
    }

    private function addMembersToProject(Project $project, PFUser $user) {
        account_add_user_to_group($project->getId(), $user->getUnixName());
        \UserManager::clearInstance();
    }

    private function addAdminToProject(Project $project, PFUser $user) {
       $this->user_permissions_dao->addUserAsProjectAdmin($project, $user);
    }
}
