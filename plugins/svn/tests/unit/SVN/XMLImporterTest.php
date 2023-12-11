<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\SVN;

use BackendSVN;
use org\bovigo\vfs\vfsStream;
use Project;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\SVN\AccessControl\AccessFileHistoryCreator;
use Tuleap\SVN\Admin\MailNotificationManager;
use Tuleap\SVN\Migration\RepositoryCopier;
use Tuleap\SVN\Notifications\NotificationsEmailsBuilder;
use Tuleap\SVN\Repository\RepositoryCreator;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVN\Repository\RuleName;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class XMLImporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private string $arpath;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AccessFileHistoryCreator
     */
    private $access_file_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&MailNotificationManager
     */
    private $mail_notification_manager;
    private Project $project;
    private \PFUser $user;
    private LoggerInterface $logger;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&RuleName
     */
    private $rule_name;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&RepositoryCreator
     */
    private $repository_creator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&RepositoryCopier
     */
    private $repository_copier;
    private NotificationsEmailsBuilder $notification_emails_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&BackendSVN
     */
    private $backend_svn;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&RepositoryManager
     */
    private $repository_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AccessFileHistoryCreator
     */
    private $access_file_history_creator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&XMLUserChecker
     */
    private $xml_user_checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->arpath = vfsStream::setup()->url();

        $this->user_manager = $this->createMock(\UserManager::class);
        $this->logger       = new NullLogger();
        $this->user         = UserTestBuilder::aUser()->build();
        $this->project      = ProjectTestBuilder::aProject()->build();

        $this->rule_name                   = $this->createMock(RuleName::class);
        $this->access_file_history_creator = $this->createMock(\Tuleap\SVN\AccessControl\AccessFileHistoryCreator::class);
        $this->repository_manager          = $this->createMock(\Tuleap\SVN\Repository\RepositoryManager::class);
        $this->backend_svn                 = $this->createMock(\BackendSVN::class);
        $this->notification_emails_builder = new NotificationsEmailsBuilder();
        $this->repository_copier           = $this->createMock(\Tuleap\SVN\Migration\RepositoryCopier::class);
        $this->repository_creator          = $this->createMock(RepositoryCreator::class);
        $this->mail_notification_manager   = $this->createMock(MailNotificationManager::class);
        $this->access_file_manager         = $this->createMock(AccessFileHistoryCreator::class);
        $this->xml_user_checker            = $this->createMock(XMLUserChecker::class);
    }

    public function testItShouldDoNothingIfNoSvnNode(): void
    {
        $this->expectNotToPerformAssertions();

        $xml = new SimpleXMLElement('<project></project>');

        $importer = new XMLImporter(
            $xml,
            $this->arpath,
            $this->repository_creator,
            $this->backend_svn,
            $this->access_file_history_creator,
            $this->repository_manager,
            $this->user_manager,
            $this->notification_emails_builder,
            $this->repository_copier,
            $this->xml_user_checker
        );

        $importer->import(
            new ImportConfig(),
            $this->logger,
            $this->project,
            $this->access_file_manager,
            $this->mail_notification_manager,
            $this->rule_name,
            $this->user
        );
    }

    public function testXMLWithInvalidContentIsRejected(): void
    {
        $xml = new SimpleXMLElement('<project><svn><repository name="svn01" dump-file="svn.dump"/></svn></project>');

        $importer = new XMLImporter(
            $xml,
            $this->arpath,
            $this->repository_creator,
            $this->backend_svn,
            $this->access_file_history_creator,
            $this->repository_manager,
            $this->user_manager,
            $this->notification_emails_builder,
            $this->repository_copier,
            $this->xml_user_checker
        );

        $this->rule_name->expects(self::once())->method('isValid')->willReturn(false);
        $this->rule_name->expects(self::once())->method('getErrorMessage');

        $this->expectException(XMLImporterException::class);

        $importer->import(
            new ImportConfig(),
            $this->logger,
            $this->project,
            $this->access_file_manager,
            $this->mail_notification_manager,
            $this->rule_name,
            $this->user
        );
    }
}
