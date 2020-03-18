<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Git\Events\XMLImportExternalContentEvent;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedPermissionSaver;
use Tuleap\Git\Permissions\FineGrainedUpdater;
use Tuleap\Git\Permissions\RegexpFineGrainedEnabler;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Git\XmlUgroupRetriever;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\XML\PHPCast;
use User\XML\Import\IFindUserFromXMLReference;

class GitXmlImporter
{

    public const READ_TAG         = 'read';
    public const WRITE_TAG        = 'write';
    public const WPLUS_TAG        = 'wplus';
    public const UGROUP_TAG       = 'ugroup';
    public const FINE_GRAINED_TAG = 'fine_grained';
    public const TAG_PATTERN      = 'tag';
    public const BRANCH_PATTERN   = 'branch';

    public const SERVICE_NAME = 'git';

    /**
     * @var \Psr\Log\LoggerInterface
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

    /**
     * @var FineGrainedPermissionFactory
     */

    private $fine_grained_factory;
    /**
     * @var FineGrainedPermissionSaver
     */
    private $fine_grained_saver;

    /**
     * @var XmlUgroupRetriever
     */
    private $xml_ugroup_retriever;

    /**
     * @var GitDao
     */
    private $git_dao;
    /**
     * @var \User\XML\Import\IFindUserFromXMLReference
     */
    private $user_finder;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        GitRepositoryManager $repository_manager,
        GitRepositoryFactory $repository_factory,
        Git_Backend_Gitolite $gitolite_backend,
        Git_SystemEventManager $system_event_manager,
        PermissionsManager $permissions_manager,
        EventManager $event_manager,
        FineGrainedUpdater $fine_grained_updater,
        RegexpFineGrainedRetriever $regexp_fine_grained_retriever,
        RegexpFineGrainedEnabler $regexp_fine_grained_enabler,
        FineGrainedPermissionFactory $fine_grained_factory,
        FineGrainedPermissionSaver $fine_grained_saver,
        XmlUgroupRetriever $xml_ugroup_retriever,
        GitDao $git_dao,
        IFindUserFromXMLReference $user_finder
    ) {
        $this->logger                        = $logger;
        $this->permission_manager            = $permissions_manager;
        $this->repository_manager            = $repository_manager;
        $this->repository_factory            = $repository_factory;
        $this->gitolite_backend              = $gitolite_backend;
        $this->system_event_manager          = $system_event_manager;
        $this->event_manager                 = $event_manager;
        $this->fine_grained_updater          = $fine_grained_updater;
        $this->regexp_fine_grained_retriever = $regexp_fine_grained_retriever;
        $this->regexp_fine_grained_enabler   = $regexp_fine_grained_enabler;
        $this->fine_grained_factory          = $fine_grained_factory;
        $this->fine_grained_saver            = $fine_grained_saver;
        $this->xml_ugroup_retriever          = $xml_ugroup_retriever;
        $this->git_dao                       = $git_dao;
        $this->user_finder                   = $user_finder;
    }

    /**
     * Import one or multiple git repositories.
     * Returns true in case of success, false otherwise.
     * @var Project
     * @var SimpleXMLElement
     * @var String
     * @return bool
     */
    public function import(
        ImportConfig $configuration,
        Project $project,
        PFUser $creator,
        SimpleXMLElement $xml_input,
        $extraction_path
    ) {
        $xml_git = $xml_input->git;
        if (!$xml_git) {
            $this->logger->debug('No git node found into xml.');
            return true;
        }

        $nb_repo = count($xml_git->repository);
        $this->logger->debug("Found $nb_repo repository(ies) to import.");

        foreach ($xml_git->repository as $repository) {
            $this->importRepository($configuration, $project, $creator, $repository, $extraction_path);
        }

        $this->importAdmins($project, $xml_git->{"ugroups-admin"});
        $this->importExternalContent($project, $xml_git);

        return true;
    }

    private function importExternalContent(Project $project, SimpleXMLElement $xml_git): void
    {
        $this->event_manager->processEvent(
            new XMLImportExternalContentEvent(
                $project,
                $xml_git,
                $this->logger
            )
        );
    }

    private function importAdmins(Project $project, SimpleXMLElement $admins_xmlnode)
    {
        $ugroup_ids = array();
        if (!empty($admins_xmlnode)) {
            $this->logger->debug($admins_xmlnode->count() . ' ugroups as admins.');
            $ugroup_ids = $this->xml_ugroup_retriever->getUgroupIdsForPermissionNode($project, $admins_xmlnode);
        }

        $ugroup_ids = $this->appendProjectAdminUGroup($ugroup_ids);

        $this->permission_manager->savePermissions($project, $project->getId(), Git::PERM_ADMIN, $ugroup_ids);
    }

    private function importRepository(
        ImportConfig $configuration,
        Project $project,
        PFUser $creator,
        SimpleXMLElement $repository_xmlnode,
        $extraction_path
    ) {
        $repository_info = $repository_xmlnode->attributes();
        assert($repository_info !== null);
        $this->logger->debug("Importing {$repository_info['name']} using {$repository_info['bundle-path']}");
        $description = isset($repository_info['description']) ? (string) $repository_info['description'] : GitRepository::DEFAULT_DESCRIPTION;
        $repository = $this->repository_factory->buildRepository($project, $repository_info['name'], $creator, $this->gitolite_backend, $description);
        if (trim((string) $repository_info['bundle-path']) !== '') {
            $this->repository_manager->createFromBundle(
                $repository,
                $this->gitolite_backend,
                $extraction_path,
                (string) $repository_info['bundle-path']
            );
        } else {
            $this->repository_manager->create($repository, $this->gitolite_backend, array());
        }
        if ($this->hasLegacyPermissions($repository_xmlnode)) {
            $this->importPermissions($project, $repository_xmlnode, $repository);
        } else {
            $this->importPermissions($project, $repository_xmlnode->permissions, $repository);
            $this->importReferences($configuration, $project, $repository_xmlnode->references, $repository);
        }

        $this->importLastPushDate($repository_xmlnode, $repository);
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
                    break;
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
            $this->logger->warning('Regexp permissions disabled at site level');
        }

        $this->importPatterns($repository, $fine_grained_xmlnode);
    }

    private function importPatterns(GitRepository $repository, SimpleXMLElement $fine_grained_xmlnode)
    {
        foreach ($fine_grained_xmlnode->children() as $pattern_node) {
            $pattern_value = (string) $pattern_node['value'];
            $pattern_type  = (string) $pattern_node['type'];

            $this->logger->debug("Importing $pattern_type pattern $pattern_value");

            $permission_representation = $this->fine_grained_factory->getFineGrainedPermissionFromXML(
                $repository,
                $pattern_node
            );

            if (! $permission_representation) {
                $this->logger->warning("The $pattern_type pattern $pattern_value is not valid, skipping.");
                continue;
            } elseif ($pattern_type === self::BRANCH_PATTERN) {
                $this->fine_grained_saver->saveBranchPermission($permission_representation);
            } elseif ($pattern_type === self::TAG_PATTERN) {
                $this->fine_grained_saver->saveTagPermission($permission_representation);
            } else {
                $this->logger->warning("Unknown type $pattern_type, skipping.");
                continue;
            }
        }
    }

    private function importPermission(
        Project $project,
        SimpleXMLElement $permission_xmlnode,
        $permission_type,
        GitRepository $repository
    ) {
        $ugroup_ids = $this->xml_ugroup_retriever->getUgroupIdsForPermissionNode($project, $permission_xmlnode);

        if (!empty($ugroup_ids)) {
            $this->permission_manager->savePermissions($project, $repository->getId(), $permission_type, $ugroup_ids);
        }
    }

    /**
     * Append the project administrator ugroup id to the given array
     * @return array
     */
    private function appendProjectAdminUGroup(array $ugroup_ids)
    {
        $ugroup_ids[] = ProjectUGroup::PROJECT_ADMIN;
        return $ugroup_ids;
    }

    private function importReferences(
        ImportConfig $configuration,
        Project $project,
        SimpleXMLElement $xml_references,
        GitRepository $repository
    ) {
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

    private function importLastPushDate(SimpleXMLElement $repository_xmlnode, GitRepository $repository)
    {
        if (! $repository_xmlnode->{"last-push-date"}) {
            return;
        }

        $push_informations = $repository_xmlnode->{"last-push-date"};

        $this->git_dao->logGitPush(
            $repository->getId(),
            $this->user_finder->getUser($push_informations->user)->getId(),
            (int) $push_informations['push_date'],
            (int) $push_informations['commits_number'],
            $push_informations['refname'],
            $push_informations['operation_type'],
            $push_informations['refname_type']
        );
    }
}
