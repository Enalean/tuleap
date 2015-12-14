<?php
/**
 * Copyright (c) Sogilis, 2015. All Rights Reserved.
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

class GitXmlImporter {

    const READ_TAG  = 'READ';
    const WRITE_TAG = 'WRITE';
    const WPLUS_TAG = 'WPLUS';

    const UGROUPID = 'ugroup-id';

    /**
     * @var Logger
    */
    private $logger;

    /**
     * @var PermissionsManager
     */
    private $permission_manager;

    /**
     * @var GitRepositoryManager
     */
    private $repository_manager;

    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;

    /**
     * @var Git_Backend_Gitolite
     */
    private $gitolite_backend;

    /**
     * @var Git_SystemEventManager
     */
    private $system_event_manager;

    /**
     * @var XML_RNGValidator
     */
    private $xml_validator;

    /**
     * @var System_Command
     */
    private $system_command;

    public function __construct(
        Logger $logger,
        GitRepositoryManager   $repository_manager,
        GitRepositoryFactory   $repository_factory,
        Git_Backend_Gitolite   $gitolite_backend,
        Git_SystemEventManager $system_event_manager,
        PermissionsManager $permissions_manager)
    {
        $this->logger = new WrapperLogger($logger, "GitXmlImporter");
        $this->permission_manager = $permissions_manager;
        $this->repository_manager = $repository_manager;
        $this->repository_factory = $repository_factory;
        $this->gitolite_backend = $gitolite_backend;
        $this->system_event_manager = $system_event_manager;
        $this->xml_validator = new XML_RNGValidator();
        $this->system_command = new System_Command();
    }

    /**
     * Import one or multiple git repositories.
     * Returns true in case of success, false otherwise.
     * @var Project
     * @var SimpleXMLElement
     * @var String
     * @return boolean
     */
    public function import(Project $project, PFUser $creator, SimpleXMLElement $xml_input, $extraction_path) {
        $xml_git = $xml_input->git;
        if(!$xml_git) {
            $this->logger->debug('No git node found into xml.');
            return true;
        }

        $rng_path = realpath(dirname(__FILE__).'/../../../src/common/xml/resources/git.rng');
        $this->xml_validator->validate($xml_git, $rng_path);
        $this->logger->debug("XML tag <git/> is valid");

        $this->logger->debug("Found {$xml_git->count()} repository(ies) to import.");

        foreach($xml_git->children() as $repository) {
            $this->importRepository($project, $creator, $repository, $extraction_path);
        }
        return true;
    }

    private function importRepository(Project $project, PFUser $creator, SimpleXMLElement $repository_xmlnode, $extraction_path) {
        $repository_info = $repository_xmlnode->attributes();
        $this->logger->debug("Importing {$repository_info['name']} using {$repository_info['bundle-path']}");
        $description = isset($repository_info['description']) ? (string) $repository_info['description'] : GitRepository::DEFAULT_DESCRIPTION;
        $repository = $this->repository_factory->buildRepository($project, $repository_info['name'], $creator, $this->gitolite_backend, $description);
        $absolute_bundle_path = $extraction_path . '/' . $repository_info['bundle-path'];
        $extraction_path_arg = escapeshellarg($extraction_path);
        $this->system_command->exec("chmod 755 $extraction_path_arg");
        $this->repository_manager->createFromBundle($repository, $this->gitolite_backend, $absolute_bundle_path);
        $this->importPermissions($project, $repository_xmlnode->children(), $repository);
        $this->system_event_manager->queueProjectsConfigurationUpdate(array($project->getGroupId()));
    }

    private function importPermissions(Project $project, SimpleXMLElement $permission_xmlnodes, GitRepository $repository) {
        foreach($permission_xmlnodes as $permission_xmlnode) {
            $permission_type = null;
            switch($permission_xmlnode->getName()) {
                case self::READ_TAG:
                    $permission_type = Git::PERM_READ;
                    break;
                case self::WRITE_TAG:
                    $permission_type = Git::PERM_WRITE;
                    break;
                case self::WPLUS_TAG:
                    $permission_type = Git::PERM_WPLUS;
                    break;
                default:
                    $this->logger->debug('Unknown node found ' . $permission_xmlnode->getName());
            }
            if(isset($permission_type)) {
                $this->importPermission($project, $permission_xmlnode, $permission_type, $repository);
            }
        }
    }

    private function importPermission(Project $project, SimpleXMLElement $permission_xmlnode, $permission_type, GitRepository $repository) {
        $ugroup_ids = array();
        foreach($permission_xmlnode->children() as $group) {
            if($group->getName() === self::UGROUPID) {
                array_push($ugroup_ids, (string)$group);
            }
        }
        if(!empty($ugroup_ids)) {
            $this->permission_manager->savePermissions($project, $repository->getId(), $permission_type, $ugroup_ids);
        }
    }
}
