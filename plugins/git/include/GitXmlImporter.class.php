<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

use Tuleap\XML\PHPCast;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Git\Permissions\FineGrainedUpdater;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpFineGrainedEnabler;

class GitXmlImporter
{

    const READ_TAG         = 'read';
    const WRITE_TAG        = 'write';
    const WPLUS_TAG        = 'wplus';
    const UGROUP_TAG       = 'ugroup';
    const FINE_GRAINED_TAG = 'fine_grained';

    const SERVICE_NAME = 'git';

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
     * @var UGroupManager
     */
    private $ugroup_manager;

    /**
     * @var System_Command
     */
    private $system_command;

    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var FineGrainedUpdater
     */
    private $fine_grained_updater;

    /**
     * @var RegexpFineGrainedRetriever
     */
    private $regexp_fine_grained_retriever;

    /**
     * @var RegexpFineGrainedEnabler
     */
    private $regexp_fine_grained_enabler;

    public function __construct(
        Logger $logger,
        GitRepositoryManager   $repository_manager,
        GitRepositoryFactory   $repository_factory,
        Git_Backend_Gitolite   $gitolite_backend,
        Git_SystemEventManager $system_event_manager,
        PermissionsManager $permissions_manager,
        UGroupManager $ugroup_manager,
        EventManager $event_manager,
        FineGrainedUpdater $fine_grained_updater,
        RegexpFineGrainedRetriever $regexp_fine_grained_retriever,
        RegexpFineGrainedEnabler $regexp_fine_grained_enabler
    ) {
        $this->logger                        = new WrapperLogger($logger, "GitXmlImporter");
        $this->permission_manager            = $permissions_manager;
        $this->repository_manager            = $repository_manager;
        $this->repository_factory            = $repository_factory;
        $this->gitolite_backend              = $gitolite_backend;
        $this->system_event_manager          = $system_event_manager;
        $this->ugroup_manager                = $ugroup_manager;
        $this->xml_validator                 = new XML_RNGValidator();
        $this->system_command                = new System_Command();
        $this->event_manager                 = $event_manager;
        $this->fine_grained_updater          = $fine_grained_updater;
        $this->regexp_fine_grained_retriever = $regexp_fine_grained_retriever;
        $this->regexp_fine_grained_enabler   = $regexp_fine_grained_enabler;
    }

    /**
     * Import one or multiple git repositories.
     * Returns true in case of success, false otherwise.
     * @var Project
     * @var SimpleXMLElement
     * @var String
     * @return boolean
     */
    public function import(ImportConfig $configuration, Project $project, PFUser $creator, SimpleXMLElement $xml_input, $extraction_path) {
        $xml_git = $xml_input->git;
        if(!$xml_git) {
            $this->logger->debug('No git node found into xml.');
            return true;
        }

        $nb_repo = count($xml_git->repository);
        $this->logger->debug("Found $nb_repo repository(ies) to import.");

        foreach($xml_git->repository as $repository) {
            $this->importRepository($configuration, $project, $creator, $repository, $extraction_path);
        }

        $this->importAdmins($project, $xml_git->{"ugroups-admin"});

        return true;
    }

    private function importAdmins(Project $project, SimpleXMLElement $admins_xmlnode) {
        $ugroup_ids = array();
        if(!empty($admins_xmlnode)) {
            $this->logger->debug($admins_xmlnode->count() . ' ugroups as admins.');
            $ugroup_ids = $this->getUgroupIdsForPermissions($project, $admins_xmlnode);
        }
        $ugroup_ids = $this->appendProjectAdminUGroup($ugroup_ids);
        $this->permission_manager->savePermissions($project, $project->getId(), Git::PERM_ADMIN, $ugroup_ids);
    }

    private function importRepository(ImportConfig $configuration, Project $project, PFUser $creator, SimpleXMLElement $repository_xmlnode, $extraction_path) {
        $repository_info = $repository_xmlnode->attributes();
        $this->logger->debug("Importing {$repository_info['name']} using {$repository_info['bundle-path']}");
        $description = isset($repository_info['description']) ? (string) $repository_info['description'] : GitRepository::DEFAULT_DESCRIPTION;
        $repository = $this->repository_factory->buildRepository($project, $repository_info['name'], $creator, $this->gitolite_backend, $description);
        $absolute_bundle_path = $extraction_path . '/' . $repository_info['bundle-path'];
        $extraction_path_arg = escapeshellarg($extraction_path);
        $this->system_command->exec("chmod 755 $extraction_path_arg");
        $this->repository_manager->createFromBundle($repository, $this->gitolite_backend, $absolute_bundle_path);
        if ($this->hasLegacyPermissions($repository_xmlnode)) {
            $this->importPermissions($project, $repository_xmlnode, $repository);
        } else {
            $this->importPermissions($project, $repository_xmlnode->permissions, $repository);
            $this->importReferences($configuration, $project, $repository_xmlnode->references, $repository);
        }
        $this->system_event_manager->queueProjectsConfigurationUpdate(array($project->getGroupId()));
    }

    private function hasLegacyPermissions(SimpleXMLElement $repository_xmlnode)
    {
        if ($repository_xmlnode->count() === 0) {
            return false;
        }
        $children    = $repository_xmlnode->children();
        $first_child = $children[0];

        switch ($first_child->getName()) {
            case self::READ_TAG:
            case self::WRITE_TAG:
            case self::WPLUS_TAG:
                return true;
                break;
            default:
                return false;
        }
    }

    private function importPermissions(
        Project $project,
        SimpleXMLElement $permission_xmlnodes,
        GitRepository $repository
    ) {
        if (empty($permission_xmlnodes)) {
            return;
        }

        foreach ($permission_xmlnodes->children() as $permission_xmlnode) {
            $permission_type = null;
            switch ($permission_xmlnode->getName()) {
                case self::READ_TAG:
                    $permission_type = Git::PERM_READ;
                    break;
                case self::WRITE_TAG:
                    $permission_type = Git::PERM_WRITE;
                    break;
                case self::WPLUS_TAG:
                    $permission_type = Git::PERM_WPLUS;
                    break;
                case self::FINE_GRAINED_TAG:
                    $this->importFineGrainedPermissions($repository, $permission_xmlnode);
                    break;
                default:
                    $this->logger->debug('Unknown node found ' . $permission_xmlnode->getName());
                    continue;
            }

            if (isset($permission_type)) {
                $this->importPermission($project, $permission_xmlnode, $permission_type, $repository);
            }
        }
    }

    private function importFineGrainedPermissions(GitRepository $repository, SimpleXMLElement $fine_grained_xmlnode)
    {
        $fine_grained_permissions_enabled = PHPCast::toBoolean($fine_grained_xmlnode['enabled']);
        $this->logger->debug('Fine grained permissions enabled ' . $fine_grained_permissions_enabled);

        if ($fine_grained_permissions_enabled) {
            $this->fine_grained_updater->enableRepository($repository);
        }

        if ($this->regexp_fine_grained_retriever->areRegexpActivatedAtSiteLevel()) {
            $regexp_permissions_enabled = PHPCast::toBoolean($fine_grained_xmlnode['use_regexp']);
            $this->logger->debug('Regexp permissions enabled in repository ' . $regexp_permissions_enabled);

            if ($regexp_permissions_enabled) {
                $this->regexp_fine_grained_enabler->enableForRepository($repository);
            }
        } else {
            $this->logger->debug('Regexp permissions disabled at site level');
        }
    }

    private function importPermission(Project $project, SimpleXMLElement $permission_xmlnode, $permission_type, GitRepository $repository) {
        $ugroup_ids = $this->getUgroupIdsForPermissions($project, $permission_xmlnode);

        if(!empty($ugroup_ids)) {
            $this->permission_manager->savePermissions($project, $repository->getId(), $permission_type, $ugroup_ids);
        }
    }

    private function getUgroupIdsForPermissions(Project $project, SimpleXMLElement $permission_xmlnode) {
        $ugroup_ids = array();
        foreach($permission_xmlnode->children() as $ugroup) {
            if($ugroup->getName() === self::UGROUP_TAG) {
                $ugroup_name = (string)$ugroup;
                $ugroup = $this->ugroup_manager->getUGroupByName($project, $ugroup_name);
                if($ugroup === null) {
                    $this->logger->error("Could not find any ugroup named $ugroup_name");
                    throw new GitXmlImporterUGroupNotFoundException($ugroup_name);
                }

                array_push($ugroup_ids, $ugroup->getId());
            }
        }
        return $ugroup_ids;
    }

    /**
     * Append the project administrator ugroup id to the given array
     * @return array
     */
    private function appendProjectAdminUGroup(array $ugroup_ids) {
        $ugroup_ids[] = ProjectUGroup::PROJECT_ADMIN;
        return $ugroup_ids;
    }

    private function importReferences(ImportConfig $configuration, Project $project, SimpleXMLElement $xml_references, GitRepository $repository)
    {
        $this->event_manager->processEvent(
            Event::IMPORT_COMPAT_REF_XML,
            array(
                'logger'         => $this->logger,
                'created_refs'   => array(
                    'repository' => $repository,
                ),
                'service_name'   => self::SERVICE_NAME,
                'xml_content'    => $xml_references,
                'project'        => $project,
                'configuration'  => $configuration,
            )
        );
    }
}
