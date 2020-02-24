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

use Backend;
use Mockery;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Project;
use SimpleXMLElement;
use Tuleap\GlobalSVNPollution;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\SVN\AccessControl\AccessFileHistoryCreator;
use Tuleap\SVN\Admin\MailNotificationManager;
use Tuleap\SVN\Migration\RepositoryCopier;
use Tuleap\SVN\Notifications\NotificationsEmailsBuilder;
use Tuleap\SVN\Repository\RepositoryCreator;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVN\Repository\RuleName;

final class XMLImporterTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, GlobalSVNPollution;

    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    private $arpath;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|AccessFileHistoryCreator
     */
    private $access_file_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|MailNotificationManager
     */
    private $mail_notification_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PFUser
     */
    private $user;
    /**
     * @var \Logger|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $logger;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|RuleName
     */
    private $rule_name;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|RepositoryCreator
     */
    private $repository_creator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|RepositoryCopier
     */
    private $repository_copier;
    /**
     * @var NotificationsEmailsBuilder
     */
    private $notification_emails_builder;
    /**
     * @var \BackendSystem|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $backend_system;
    /**
     * @var Backend
     */
    private $backend_svn;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|RepositoryManager
     */
    private $repository_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|AccessFileHistoryCreator
     */
    private $access_file_history_creator;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|XMLUserChecker
     */
    private $xml_user_checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->arpath = vfsStream::setup()->url();

        $this->user_manager = \Mockery::spy(\UserManager::class);
        $this->logger       = \Mockery::spy(\Psr\Log\LoggerInterface::class);
        $this->user         = \Mockery::spy(\PFUser::class);
        $this->project      = Mockery::mock(Project::class);

        $this->rule_name                   = Mockery::mock(RuleName::class);
        $this->access_file_history_creator = \Mockery::spy(\Tuleap\SVN\AccessControl\AccessFileHistoryCreator::class);
        $this->repository_manager          = \Mockery::spy(\Tuleap\SVN\Repository\RepositoryManager::class);
        $this->backend_svn                 = Mockery::mock(\BackendSVN::class);
        $this->backend_system              = \Mockery::spy(\BackendSystem::class);
        $this->notification_emails_builder = new NotificationsEmailsBuilder();
        $this->repository_copier           = \Mockery::spy(\Tuleap\SVN\Migration\RepositoryCopier::class);
        $this->repository_creator          = Mockery::mock(RepositoryCreator::class);
        $this->mail_notification_manager   = Mockery::mock(MailNotificationManager::class);
        $this->access_file_manager         = Mockery::mock(AccessFileHistoryCreator::class);
        $this->xml_user_checker            = Mockery::mock(XMLUserChecker::class);
    }

    public function testItShouldDoNothingIfNoSvnNode(): void
    {
        $xml = new SimpleXMLElement('<project></project>');

        $importer = new XMLImporter(
            $xml,
            $this->arpath,
            $this->repository_creator,
            $this->backend_svn,
            $this->backend_system,
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

        $this->addToAssertionCount(1);
    }

    public function testXMLWithInvalidContentIsRejected(): void
    {
        $xml = new SimpleXMLElement('<project><svn><repository name="svn01" dump-file="svn.dump"/></svn></project>');

        $importer = new XMLImporter(
            $xml,
            $this->arpath,
            $this->repository_creator,
            $this->backend_svn,
            $this->backend_system,
            $this->access_file_history_creator,
            $this->repository_manager,
            $this->user_manager,
            $this->notification_emails_builder,
            $this->repository_copier,
            $this->xml_user_checker
        );

        $this->rule_name->shouldReceive('isValid')->once()->andReturnFalse();
        $this->rule_name->shouldReceive('getErrorMessage')->once();

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
