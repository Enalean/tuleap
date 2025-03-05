<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git;

use EventManager;
use Exception;
use ForgeConfig;
use Git;
use Git_LogDao;
use GitPermissionsManager;
use GitRepository;
use GitRepositoryFactory;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Git\DefaultBranch\RetrieveRepositoryDefaultBranch;
use Tuleap\Git\Repository\Settings\ArtifactClosure\VerifyArtifactClosureIsAllowed;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\GitBundle;
use Tuleap\GlobalLanguageMock;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Result;
use Tuleap\Project\XML\ArchiveException;
use Tuleap\Project\XML\Export\ZipArchive;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;
use UserManager;
use UserXMLExportedCollection;
use UserXMLExporter;
use XML_RNGValidator;
use XML_SimpleXMLCDATAFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitXMLExporterTest extends TestCase
{
    use TemporaryTestDirectory;
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    private const REPOSITORY_ID        = 101;
    private const EMPTY_REPOSITORY_ID  = 102;
    private const FORKED_REPOSITORY_IP = 103;

    private UserManager&MockObject $user_manager;
    private Git_LogDao&MockObject $git_log_dao;
    private GitXmlExporter $xml_exporter;
    private SimpleXMLElement $xml_tree;
    private ZipArchive $zip;
    private GitPermissionsManager&MockObject $permission_manager;

    protected function setUp(): void
    {
        $export_folder = $this->getTmpDir();

        if (! is_dir($this->getTmpDir() . '/export')) {
            mkdir($this->getTmpDir() . '/export');
        }
        touch($this->getTmpDir() . '/export/MyRepo.bundle');

        $GLOBALS['Language']->method('getText')->willReturn('projects-admins');

        $this->permission_manager = $this->createMock(GitPermissionsManager::class);
        $this->permission_manager->method('getCurrentGitAdminUgroups')->willReturn([4, 5]);

        $ugroup_manager = $this->createMock(UGroupManager::class);
        $ugroup         = ProjectUGroupTestBuilder::aCustomUserGroup(154)->withName('custom')->build();
        $ugroup_manager->method('getUGroup')->willReturn($ugroup);

        $repository_factory = $this->createMock(GitRepositoryFactory::class);
        $repository         = $this->createMock(GitRepository::class);
        $repository->method('getId')->willReturn(self::REPOSITORY_ID);
        $repository->method('getName')->willReturn('MyRepo');
        $repository->method('getDescription')->willReturn('Repository description');
        $repository->method('getFullPath')->willReturn($export_folder);
        $repository->method('getParent')->willReturn(null);
        $repository->method('isInitialized')->willReturn(true);

        $forked_repository = GitRepositoryTestBuilder::aForkOf($repository)
            ->withId(self::FORKED_REPOSITORY_IP)
            ->withName('MyForkedRepo')
            ->build();

        $empty_repository = $this->createMock(GitRepository::class);
        $empty_repository->method('getId')->willReturn(self::EMPTY_REPOSITORY_ID);
        $empty_repository->method('getName')->willReturn('Empty');
        $empty_repository->method('getDescription')->willReturn('Empty repository');
        $empty_repository->method('getFullPath')->willReturn($export_folder);
        $empty_repository->method('getParent')->willReturn(null);
        $empty_repository->method('isInitialized')->willReturn(false);

        $repository_factory->method('getAllRepositories')->willReturn([$repository, $forked_repository, $empty_repository]);

        $this->user_manager = $this->createMock(UserManager::class);
        $event_manager      = $this->createMock(EventManager::class);
        $this->git_log_dao  = $this->createMock(Git_LogDao::class);

        $closure_verifier = $this->createMock(VerifyArtifactClosureIsAllowed::class);
        $closure_verifier->method('isArtifactClosureAllowed')->willReturnCallback(static fn(int $repository_id) => match ($repository_id) {
            self::REPOSITORY_ID        => true,
            self::EMPTY_REPOSITORY_ID,
            self::FORKED_REPOSITORY_IP => false,
            default                    => throw new Exception('Unable to find the repository ' . $repository_id),
        });
        $default_branch_retriever = $this->createMock(RetrieveRepositoryDefaultBranch::class);
        $default_branch_retriever->method('getRepositoryDefaultBranch')
            ->willReturnCallback(fn(GitRepository $repo) => match ((int) $repo->getId()) {
                (int) $repository->getId()        => Result::ok('main'),
                (int) $forked_repository->getId() => Result::ok('master'),
                default                           => Result::err(Fault::fromMessage('Default branch not found')),
            });
        $git_bundle = $this->createMock(GitBundle::class);
        $git_bundle->method('dumpRepository');
        $this->xml_exporter = new GitXmlExporter(
            ProjectTestBuilder::aProject()->build(),
            $this->permission_manager,
            $ugroup_manager,
            $repository_factory,
            new NullLogger(),
            $git_bundle,
            $this->git_log_dao,
            $this->user_manager,
            new UserXMLExporter(
                $this->user_manager,
                new UserXMLExportedCollection(new XML_RNGValidator(), new XML_SimpleXMLCDATAFactory())
            ),
            $event_manager,
            $closure_verifier,
            $default_branch_retriever,
        );

        $event_manager->expects(self::once())->method('processEvent');

        $data           = '<?xml version="1.0" encoding="UTF-8"?>
                 <projects />';
        $this->xml_tree = new SimpleXMLElement($data);

        $this->zip = new ZipArchive($export_folder . '/archive.zip');

        ForgeConfig::set('tmp_dir', $export_folder);
    }

    protected function tearDown(): void
    {
        try {
            $this->zip->close();
        } catch (ArchiveException) {
        }
        unlink($this->getTmpDir() . '/export/MyRepo.bundle');
        rmdir($this->getTmpDir() . '/export');
    }

    public function testItExportGitRepositories(): void
    {
        $this->git_log_dao->method('getLastPushForRepository');
        $this->permission_manager->method('getRepositoryGlobalPermissions');
        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, '');

        self::assertCount(2, $this->xml_tree->git->repository);

        $exported_repositories = $this->xml_tree->git[0];
        $repository            = $exported_repositories->repository;
        $attrs                 = $repository->attributes();

        self::assertEquals('MyRepo', $attrs['name']);
        self::assertEquals('1', $attrs['allow_artifact_closure']);
        self::assertEquals('main', (string) $attrs['default_branch']);
        self::assertEquals('Repository description', $attrs['description']);
        self::assertEquals('export/repository-101.bundle', $attrs['bundle-path']);

        $repository_02 = $exported_repositories->repository[1];
        $attrs_02      = $repository_02->attributes();


        self::assertEquals('Empty', $attrs_02['name']);
        self::assertEquals('0', $attrs_02['allow_artifact_closure']);
        self::assertNull($attrs_02['default_branch']);
        self::assertEquals('Empty repository', $attrs_02['description']);
        self::assertEquals('', $attrs_02['bundle-path']);
    }

    public function testItExportsUGroupsAdmins(): void
    {
        $this->git_log_dao->method('getLastPushForRepository');
        $this->permission_manager->method('getRepositoryGlobalPermissions');
        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, '');

        $ugroups_admin = $this->xml_tree->git->{'ugroups-admin'}->ugroup;

        self::assertEquals('projects-admins', (string) $ugroups_admin[0]);
        self::assertEquals('custom', (string) $ugroups_admin[1]);
    }

    public function testItExportRepositoryPermissions(): void
    {
        $this->git_log_dao->method('getLastPushForRepository');
        $this->permission_manager->method('getRepositoryGlobalPermissions')->willReturn([
            Git::PERM_READ  => [3, 5],
            Git::PERM_WRITE => [3],
            Git::PERM_WPLUS => [5],
        ]);

        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, '');

        $readers = $this->xml_tree->git->repository->read->ugroup;
        self::assertEquals('projects-admins', (string) $readers[0]);
        self::assertEquals('custom', (string) $readers[1]);

        $writers = $this->xml_tree->git->repository->write->ugroup;
        self::assertEquals('projects-admins', (string) $writers[0]);

        $wplus = $this->xml_tree->git->repository->wplus->ugroup;
        self::assertEquals('custom', (string) $wplus[0]);
    }

    public function testItDoesNotCreateWritePermissionIfRepositoryDontHaveCustomWritePermission(): void
    {
        $this->git_log_dao->method('getLastPushForRepository');
        $this->permission_manager->method('getRepositoryGlobalPermissions')->willReturn([
            Git::PERM_READ  => [3, 5],
            Git::PERM_WPLUS => [5],
        ]);

        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, '');

        $readers = $this->xml_tree->git->repository->read->ugroup;
        self::assertEquals('projects-admins', (string) $readers[0]);
        self::assertEquals('custom', (string) $readers[1]);

        $writers = $this->xml_tree->git->repository->write->ugroup;
        self::assertEmpty((string) $writers);

        $wplus = $this->xml_tree->git->repository->wplus->ugroup;
        self::assertEquals('custom', (string) $wplus[0]);
    }

    public function testItDoesNotCreateWplusPermissionIfRepositoryDontHaveCustomWplusPermission(): void
    {
        $this->git_log_dao->method('getLastPushForRepository');
        $this->permission_manager->method('getRepositoryGlobalPermissions')->willReturn([
            Git::PERM_READ  => [3, 5],
            Git::PERM_WRITE => [3],
        ]);

        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, '');

        $readers = $this->xml_tree->git->repository->read->ugroup;
        self::assertEquals('projects-admins', (string) $readers[0]);
        self::assertEquals('custom', (string) $readers[1]);

        $writers = $this->xml_tree->git->repository->write->ugroup;
        self::assertEquals('projects-admins', (string) $writers[0]);

        $wplus = $this->xml_tree->git->repository->wplus->ugroup;
        self::assertEmpty((string) $wplus);
    }

    public function testItExportGitLastPushDateData(): void
    {
        $this->git_log_dao->method('getLastPushForRepository')->willReturn([
            'repository_id'  => 2,
            'user_id'        => 102,
            'push_date'      => 1527145976,
            'commits_number' => 1,
            'refname'        => 'refs/heads/master',
            'operation_type' => 'create',
            'refname_type'   => 'branch',
        ]);
        $this->permission_manager->method('getRepositoryGlobalPermissions');

        $this->user_manager->method('getUserById')->willReturn(new PFUser(['user_name' => 'my user name', 'language_id' => 'en']));

        $this->xml_exporter->exportToXml($this->xml_tree, $this->zip, '');

        self::assertCount(2, $this->xml_tree->git->repository);

        $exported_repository = $this->xml_tree->git->repository[0];
        $last_push_date      = $exported_repository->{'last-push-date'};
        $attrs               = $last_push_date->attributes();
        self::assertEquals('my user name', (string) $last_push_date->user);
        self::assertEquals('1527145976', (string) $attrs['push_date']);
    }
}
