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

final class XMLRepositoryImporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalSVNPollution;

    private XMLRepositoryImporter $repository_importer;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PFUser
     */
    private $committer;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&RuleName
     */
    private $rule_name;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&MailNotificationManager
     */
    private $mail_notification_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AccessFileHistoryCreator
     */
    private $accessfile_history_creator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Project
     */
    private $project;
    private TestLogger $logger;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ImportConfig
     */
    private $configuration;
    /**
     * @var string
     */
    private $extraction_path;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&RepositoryCreator
     */
    private $repository_creator;
    /**
     * @var Backend&\PHPUnit\Framework\MockObject\MockObject
     */
    private $backend_svn;
    /**
     * @var Backend&\PHPUnit\Framework\MockObject\MockObject
     */
    private $backend_system;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AccessFileHistoryCreator
     */
    private $access_file_history_creator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&RepositoryManager
     */
    private $repository_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&NotificationsEmailsBuilder
     */
    private $notifications_emails_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&RepositoryCopier
     */
    private $repository_copier;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&XMLUserChecker
     */
    private $xml_user_checker;

    private function instantiateImporterWithXml(\SimpleXMLElement $xml): void
    {
        $this->extraction_path              = vfsStream::setup()->url();
        $this->repository_creator           = $this->createMock(RepositoryCreator::class);
        $this->backend_svn                  = $this->createMock(BackendSVN::class);
        $this->backend_system               = $this->createMock(BackendSystem::class);
        $this->access_file_history_creator  = $this->createMock(AccessFileHistoryCreator::class);
        $this->repository_manager           = $this->createMock(RepositoryManager::class);
        $this->user_manager                 = $this->createMock(UserManager::class);
        $this->notifications_emails_builder = $this->createMock(NotificationsEmailsBuilder::class);
        $this->repository_copier            = $this->createMock(RepositoryCopier::class);
        $this->xml_user_checker             = $this->createMock(XMLUserChecker::class);

        $this->repository_importer = new XMLRepositoryImporter(
            $xml,
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
        );

        $this->configuration              = $this->createMock(ImportConfig::class);
        $this->logger                     = new TestLogger();
        $this->project                    = $this->createMock(Project::class);
        $this->accessfile_history_creator = $this->createMock(AccessFileHistoryCreator::class);
        $this->mail_notification_manager  = $this->createMock(MailNotificationManager::class);
        $this->rule_name                  = $this->createMock(RuleName::class);
        $this->committer                  = $this->createMock(PFUser::class);
    }

    public function testItShouldFailsWhenRepositoryNameIsInvalid(): void
    {
        $xml = new SimpleXMLElement(
            '<repository name="invalid name is invalid" dump-file="svn.dump"/>'
        );
        $this->instantiateImporterWithXml($xml);

        $this->rule_name->method('isValid')->willReturn(false);
        $this->rule_name->expects(self::once())->method('getErrorMessage');

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

        $this->rule_name->method('isValid')->willReturn(true);

        $this->repository_creator->method('createWithoutUserAdminCheck')
            ->willThrowException(new RepositoryNameIsInvalidException());

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

        $this->rule_name->method('isValid')->willReturn(true);

        $this->repository_creator->expects(self::once())->method('createWithoutUserAdminCheck');

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

        $this->rule_name->method('isValid')->willReturn(true);

        $event = $this->createMock(SystemEvent_SVN_CREATE_REPOSITORY::class);
        $this->repository_creator->expects(self::once())
            ->method('createWithoutUserAdminCheck')
            ->willReturn($event);

        $event->expects(self::once())->method('injectDependencies');
        $event->expects(self::once())->method('process');
        $event->method('getStatus')->willReturn(SystemEvent::STATUS_ERROR);
        $event->expects(self::once())->method('getLog');

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

        $this->rule_name->method('isValid')->willReturn(true);

        $event = $this->createMock(SystemEvent_SVN_CREATE_REPOSITORY::class);
        $this->repository_creator->expects(self::once())
            ->method('createWithoutUserAdminCheck')
            ->willReturn($event);

        $event->expects(self::once())->method('injectDependencies');
        $event->expects(self::once())->method('process');
        $event->method('getStatus')->willReturn(SystemEvent::STATUS_DONE);
        $event->expects(self::once())->method('getLog');

        $this->project->method('getId')->willReturn(101);

        $this->backend_svn->expects(self::once())->method('setUserAndGroup');

        $this->notifications_emails_builder->expects(self::exactly(2))
            ->method('transformNotificationEmailsStringAsArray')
            ->willReturn([]);

        $this->mail_notification_manager->expects(self::exactly(2))->method('create');

        $this->xml_user_checker->expects(self::once())->method('currentUserIsHTTPUser')->willReturn(false);

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

        $this->rule_name->method('isValid')->willReturn(true);

        $event = $this->createMock(SystemEvent_SVN_CREATE_REPOSITORY::class);
        $this->repository_creator->expects(self::once())
            ->method('createWithoutUserAdminCheck')
            ->willReturn($event);

        $event->expects(self::once())->method('injectDependencies');
        $event->expects(self::once())->method('process');
        $event->method('getStatus')->willReturn(SystemEvent::STATUS_DONE);
        $event->expects(self::once())->method('getLog');

        $this->project->method('getId')->willReturn(101);

        $this->backend_svn->expects(self::once())->method('setUserAndGroup');

        $this->accessfile_history_creator->expects(self::once())->method('create')->willReturn(new CollectionOfSVNAccessFileFaults());

        $this->xml_user_checker->method('currentUserIsHTTPUser')->willReturn(false);

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
