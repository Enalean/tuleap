<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\SVN;

use Backend;
use BackendSVN;
use BackendSystem;
use ColinODell\PsrTestLogger\TestLogger;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PFUser;
use Project;
use Psr\Log\LogLevel;
use SimpleXMLElement;
use SystemEvent;
use Tuleap\GlobalSVNPollution;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\SVN\AccessControl\AccessFileHistoryCreator;
use Tuleap\SVN\Admin\MailNotificationManager;
use Tuleap\SVN\Events\SystemEvent_SVN_CREATE_REPOSITORY;
use Tuleap\SVN\Migration\RepositoryCopier;
use Tuleap\SVN\Notifications\NotificationsEmailsBuilder;
use Tuleap\SVN\Repository\Exception\RepositoryNameIsInvalidException;
use Tuleap\SVN\Repository\RepositoryCreator;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVN\Repository\RuleName;
use Tuleap\SVNCore\CollectionOfSVNAccessFileFaults;
use UserManager;

class XMLRepositoryImporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalSVNPollution;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|XMLRepositoryImporter
     */
    private $repository_importer;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PFUser
     */
    private $committer;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|RuleName
     */
    private $rule_name;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|MailNotificationManager
     */
    private $mail_notification_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AccessFileHistoryCreator
     */
    private $accessfile_history_creator;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Project
     */
    private $project;
    private TestLogger $logger;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ImportConfig
     */
    private $configuration;
    /**
     * @var string
     */
    private $extraction_path;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|RepositoryCreator
     */
    private $repository_creator;
    /**
     * @var Backend|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $backend_svn;
    /**
     * @var Backend|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $backend_system;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AccessFileHistoryCreator
     */
    private $access_file_history_creator;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|RepositoryManager
     */
    private $repository_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|NotificationsEmailsBuilder
     */
    private $notifications_emails_builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|RepositoryCopier
     */
    private $repository_copier;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|SimpleXMLElement
     */
    private $xml_repo;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|XMLUserChecker
     */
    private $xml_user_checker;

    private function instantiateImporterWithXml(\SimpleXMLElement $xml)
    {
        $this->xml_repo                     = $xml;
        $this->extraction_path              = vfsStream::setup()->url();
        $this->repository_creator           = \Mockery::mock(RepositoryCreator::class);
        $this->backend_svn                  = \Mockery::mock(BackendSVN::class);
        $this->backend_system               = \Mockery::mock(BackendSystem::class);
        $this->access_file_history_creator  = \Mockery::mock(AccessFileHistoryCreator::class);
        $this->repository_manager           = \Mockery::mock(RepositoryManager::class);
        $this->user_manager                 = \Mockery::mock(UserManager::class);
        $this->notifications_emails_builder = \Mockery::mock(NotificationsEmailsBuilder::class);
        $this->repository_copier            = \Mockery::mock(RepositoryCopier::class);
        $this->xml_user_checker             = \Mockery::mock(XMLUserChecker::class);

        $this->repository_importer = \Mockery::mock(
            XMLRepositoryImporter::class,
            [
                $this->xml_repo,
                $this->extraction_path,
                $this->repository_creator,
                $this->backend_svn,
                $this->backend_system,
                $this->access_file_history_creator,
                $this->repository_manager,
                $this->user_manager,
                $this->notifications_emails_builder,
                $this->repository_copier,
                $this->xml_user_checker,
            ]
        )->makePartial()->shouldAllowMockingProtectedMethods();

        $this->configuration              = \Mockery::mock(ImportConfig::class);
        $this->logger                     = new TestLogger();
        $this->project                    = \Mockery::mock(Project::class);
        $this->accessfile_history_creator = \Mockery::mock(AccessFileHistoryCreator::class);
        $this->mail_notification_manager  = \Mockery::mock(MailNotificationManager::class);
        $this->rule_name                  = \Mockery::mock(RuleName::class);
        $this->committer                  = \Mockery::mock(PFUser::class);
    }

    public function testItShouldFailsWhenRepositoryNameIsInvalid(): void
    {
        $xml = new SimpleXMLElement(
            '<repository name="invalid name is invalid" dump-file="svn.dump"/>'
        );
        $this->instantiateImporterWithXml($xml);

        $this->rule_name->shouldReceive('isValid')->andReturnFalse();
        $this->rule_name->shouldReceive('getErrorMessage')->once();

        $this->expectException(XMLImporterException::class);

        $this->repository_importer->import(
            $this->configuration,
            $this->logger,
            $this->project,
            $this->accessfile_history_creator,
            $this->mail_notification_manager,
            $this->rule_name,
            $this->committer
        );
    }

    public function testItShouldFailsWhenRepositoryNameIsAlreadyExist(): void
    {
        $xml = new SimpleXMLElement(
            '<repository name="valid-name" dump-file="svn.dump"/>'
        );
        $this->instantiateImporterWithXml($xml);

        $this->rule_name->shouldReceive('isValid')->andReturnTrue();

        $this->repository_creator->shouldReceive('createWithoutUserAdminCheck')
            ->andThrows(RepositoryNameIsInvalidException::class);

        $this->expectException(XMLImporterException::class);

        $this->repository_importer->import(
            $this->configuration,
            $this->logger,
            $this->project,
            $this->accessfile_history_creator,
            $this->mail_notification_manager,
            $this->rule_name,
            $this->committer
        );
    }

    public function testItShouldFailsWhenSystemEventFails(): void
    {
        $xml = new SimpleXMLElement(
            '<repository name="valid-name" dump-file="svn.dump"/>'
        );
        $this->instantiateImporterWithXml($xml);

        $this->rule_name->shouldReceive('isValid')->andReturnTrue();

        $this->repository_creator->shouldReceive('createWithoutUserAdminCheck')->once();

        $this->expectException(XMLImporterException::class);

        $this->repository_importer->import(
            $this->configuration,
            $this->logger,
            $this->project,
            $this->accessfile_history_creator,
            $this->mail_notification_manager,
            $this->rule_name,
            $this->committer
        );
    }

    public function testIShouldThrowANExceptionIfEventFails(): void
    {
        $xml = new SimpleXMLElement(
            '<repository name="valid-name" dump-file="svn.dump"/>'
        );
        $this->instantiateImporterWithXml($xml);

        $this->rule_name->shouldReceive('isValid')->andReturnTrue();

        $event = \Mockery::mock(SystemEvent_SVN_CREATE_REPOSITORY::class);
        $this->repository_creator->shouldReceive('createWithoutUserAdminCheck')
            ->once()
            ->andReturn($event);

        $event->shouldReceive('injectDependencies')->once();
        $event->shouldReceive('process')->once();
        $event->shouldReceive('getStatus')->andReturn(SystemEvent::STATUS_ERROR);
        $event->shouldReceive('getLog')->once();

        $this->expectException(XMLImporterException::class);

        $this->repository_importer->import(
            $this->configuration,
            $this->logger,
            $this->project,
            $this->accessfile_history_creator,
            $this->mail_notification_manager,
            $this->rule_name,
            $this->committer
        );

        self::assertTrue($this->logger->hasInfoRecords());
        self::assertTrue($this->logger->hasErrorRecords());
    }

    public function testItShouldImportNotifications(): void
    {
        $xml = <<<XML
                    <repository name="svn">
                        <notification path="/trunk" emails="test1@domain1, test2@domain2"/>
                        <notification path="/tags" emails="tags@domain3"/>
                    </repository>
        XML;

        $this->instantiateImporterWithXml(new SimpleXMLElement($xml));

        $this->rule_name->shouldReceive('isValid')->andReturnTrue();

        $event = \Mockery::mock(SystemEvent_SVN_CREATE_REPOSITORY::class);
        $this->repository_creator->shouldReceive('createWithoutUserAdminCheck')
            ->once()
            ->andReturn($event);

        $event->shouldReceive('injectDependencies')->once();
        $event->shouldReceive('process')->once();
        $event->shouldReceive('getStatus')->andReturn(SystemEvent::STATUS_DONE);
        $event->shouldReceive('getLog')->once();

        $this->project->shouldReceive('getId')->andReturn(101);

        $this->backend_svn->shouldReceive('setUserAndGroup')->once();

        $this->notifications_emails_builder->shouldReceive('transformNotificationEmailsStringAsArray')->twice(
        )->andReturn([]);
        $this->mail_notification_manager->shouldReceive('create')->twice();

        $this->xml_user_checker->shouldReceive('currentUserIsHTTPUser')->once()->andReturnFalse();

        $this->repository_importer->import(
            $this->configuration,
            $this->logger,
            $this->project,
            $this->accessfile_history_creator,
            $this->mail_notification_manager,
            $this->rule_name,
            $this->committer
        );

        self::assertTrue($this->logger->hasInfoRecords());
        self::assertTrue($this->logger->hasDebugRecords());
    }

    public function testItShouldImportSvnAccessFile(): void
    {
        $access_file = "[groups]\nmembers = usernameTOTO123\n\n\n[/]\n* = r\n@members = rw\n";
        $xml         = <<<XML
                <repository name="svn01">
                    <access-file>$access_file</access-file>
                </repository>
        XML;

        $this->instantiateImporterWithXml(new SimpleXMLElement($xml));

        $this->rule_name->shouldReceive('isValid')->andReturnTrue();

        $event = \Mockery::mock(SystemEvent_SVN_CREATE_REPOSITORY::class);
        $this->repository_creator->shouldReceive('createWithoutUserAdminCheck')
            ->once()
            ->andReturn($event);

        $event->shouldReceive('injectDependencies')->once();
        $event->shouldReceive('process')->once();
        $event->shouldReceive('getStatus')->andReturn(SystemEvent::STATUS_DONE);
        $event->shouldReceive('getLog')->once();

        $this->project->shouldReceive('getId')->andReturn(101);

        $this->backend_svn->shouldReceive('setUserAndGroup')->once();

        $this->accessfile_history_creator->shouldReceive('create')->once()->andReturn(new CollectionOfSVNAccessFileFaults());

        $this->xml_user_checker->shouldReceive('currentUserIsHTTPUser')->andReturnFalse();

        $this->repository_importer->import(
            $this->configuration,
            $this->logger,
            $this->project,
            $this->accessfile_history_creator,
            $this->mail_notification_manager,
            $this->rule_name,
            $this->committer
        );

        self::assertCount(2, $this->logger->recordsByLevel[LogLevel::INFO]);
    }
}
