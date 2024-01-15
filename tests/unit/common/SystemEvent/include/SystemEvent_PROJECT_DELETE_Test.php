<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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

use Tuleap\SVNCore\SVNAuthenticationCacheInvalidator;

/**
 * Test for project delete system event
 */
//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
final class SystemEvent_PROJECT_DELETE_Test extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * Project delete Users fail
     *
     * @return Void
     */
    public function testProjectDeleteUsersFail(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = $this->getMockBuilder(\SystemEvent_PROJECT_DELETE::class)
            ->setConstructorArgs(
                [
                    '1',
                    SystemEvent::TYPE_PROJECT_DELETE,
                    SystemEvent::OWNER_ROOT,
                    '142',
                    SystemEvent::PRIORITY_HIGH,
                    SystemEvent::STATUS_RUNNING,
                    $now,
                    $now,
                    $now,
                    '',
                ]
            )
            ->onlyMethods([
                'getProject',
                'removeProjectMembers',
                'deleteMembershipRequestNotificationEntries',
                'cleanupProjectUgroupsBinding',
                'cleanupProjectFRS',
                'getArtifactTypeFactory',
                'getBackend',
                'getWikiAttachment',
                'done',
                'error',
                'getEventManager',
            ])
            ->getMock();

        $svn_authentication_cache_invalidator = $this->createMock(SVNAuthenticationCacheInvalidator::class);
        $svn_authentication_cache_invalidator->method('invalidateProjectCache');
        $evt->injectDependencies($svn_authentication_cache_invalidator);

        // The project
        $project = $this->createMock(\Project::class);
        $project->method('usesSVN')->willReturn(true);
        $evt->method('getProject')->with('142')->willReturn($project);

        //Remove users from project
        $evt->method('removeProjectMembers')->willReturn(false);

        $evt->method('deleteMembershipRequestNotificationEntries')->willReturn(true);

        //Cleanup ProjectUGroup binding
        $evt->method('cleanupProjectUgroupsBinding')->willReturn(true);

        //Cleanup FRS
        $evt->method('cleanupProjectFRS')->willReturn(true);

        //Delete all trackers
        $atf = $this->createMock(\ArtifactTypeFactory::class);
        $atf->method('preDeleteAllProjectArtifactTypes')->willReturn(true);
        $evt->method('getArtifactTypeFactory')->with($project)->willReturn($atf);

        // System
        $backendSystem = $this->createMock(\BackendSystem::class);

        // Wiki attachments
        $wa = $this->createMock(\WikiAttachment::class);
        $wa->expects(self::once())->method('deleteProjectAttachments')->willReturn(true);
        $evt->method('getWikiAttachment')->willReturn($wa);

        $evt->method('getBackend')->willReturnMap([
            ['System', $backendSystem],
        ]);

        $evt->expects(self::never())->method('done');
        $evt->expects(self::once())->method('error')->with("Could not remove project users");

        $evt->method('getEventManager')->willReturn($this->createMock(EventManager::class));

        // Launch the event
        self::assertFalse($evt->process());
    }

    /**
     * Project delete embership request
     *
     * @return Void
     */
    public function testProjectDeleteMembershipRequestNotificationUGroupFail(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = $this->getMockBuilder(\SystemEvent_PROJECT_DELETE::class)
            ->setConstructorArgs(
                [
                    '1',
                    SystemEvent::TYPE_PROJECT_DELETE,
                    SystemEvent::OWNER_ROOT,
                    '142',
                    SystemEvent::PRIORITY_HIGH,
                    SystemEvent::STATUS_RUNNING,
                    $now,
                    $now,
                    $now,
                    '',
                ]
            )
            ->onlyMethods([
                'getProject',
                'removeProjectMembers',
                'deleteMembershipRequestNotificationEntries',
                'cleanupProjectUgroupsBinding',
                'cleanupProjectFRS',
                'getArtifactTypeFactory',
                'getBackend',
                'getWikiAttachment',
                'done',
                'error',
                'getEventManager',
            ])
            ->getMock();

        $svn_authentication_cache_invalidator = $this->createMock(SVNAuthenticationCacheInvalidator::class);
        $svn_authentication_cache_invalidator->method('invalidateProjectCache');
        $evt->injectDependencies($svn_authentication_cache_invalidator);

        // The project
        $project = $this->createMock(\Project::class);
        $project->method('usesSVN')->willReturn(true);
        $evt->method('getProject')->with('142')->willReturn($project);

        //Remove users from project
        $evt->method('removeProjectMembers')->willReturn(true);

        $evt->method('deleteMembershipRequestNotificationEntries')->willReturn(false);

        //Cleanup ProjectUGroup binding
        $evt->method('cleanupProjectUgroupsBinding')->willReturn(true);

        //Cleanup FRS
        $evt->method('cleanupProjectFRS')->willReturn(true);

        //Delete all trackers
        $atf = $this->createMock(\ArtifactTypeFactory::class);
        $atf->method('preDeleteAllProjectArtifactTypes')->willReturn(true);
        $evt->method('getArtifactTypeFactory')->with($project)->willReturn($atf);

        // System
        $backendSystem = $this->createMock(\BackendSystem::class);

        // Wiki attachments
        $wa = $this->createMock(\WikiAttachment::class);
        $wa->expects(self::once())->method('deleteProjectAttachments')->willReturn(true);
        $evt->method('getWikiAttachment')->willReturn($wa);

        $evt->method('getBackend')->willReturnMap([
            ['System', $backendSystem],
        ]);

        $evt->expects(self::never())->method('done');
        $evt->expects(self::once())->method('error')->with("Could not remove membership request notification ugroups or message");

        $evt->method('getEventManager')->willReturn($this->createMock(EventManager::class));

        // Launch the event
        self::assertFalse($evt->process());
    }

    /**
     * Project delete FRS fail
     *
     * @return Void
     */
    public function testProjectDeleteFRSFail(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = $this->getMockBuilder(\SystemEvent_PROJECT_DELETE::class)
            ->setConstructorArgs(
                [
                    '1',
                    SystemEvent::TYPE_PROJECT_DELETE,
                    SystemEvent::OWNER_ROOT,
                    '142',
                    SystemEvent::PRIORITY_HIGH,
                    SystemEvent::STATUS_RUNNING,
                    $now,
                    $now,
                    $now,
                    '',
                ]
            )
            ->onlyMethods([
                'getProject',
                'removeProjectMembers',
                'deleteMembershipRequestNotificationEntries',
                'cleanupProjectUgroupsBinding',
                'cleanupProjectFRS',
                'getArtifactTypeFactory',
                'getBackend',
                'getWikiAttachment',
                'done',
                'error',
                'getEventManager',
            ])
            ->getMock();

        $svn_authentication_cache_invalidator = $this->createMock(SVNAuthenticationCacheInvalidator::class);
        $svn_authentication_cache_invalidator->method('invalidateProjectCache');
        $evt->injectDependencies($svn_authentication_cache_invalidator);

        // The project
        $project = $this->createMock(\Project::class);
        $project->method('usesSVN')->willReturn(true);
        $evt->method('getProject')->with('142')->willReturn($project);

        //Remove users from project
        $evt->method('removeProjectMembers')->willReturn(true);

        $evt->method('deleteMembershipRequestNotificationEntries')->willReturn(true);

        //Cleanup ProjectUGroup binding
        $evt->method('cleanupProjectUgroupsBinding')->willReturn(true);

        //Cleanup FRS
        $evt->method('cleanupProjectFRS')->willReturn(false);

        //Delete all trackers
        $atf = $this->createMock(\ArtifactTypeFactory::class);
        $atf->method('preDeleteAllProjectArtifactTypes')->willReturn(true);
        $evt->method('getArtifactTypeFactory')->with($project)->willReturn($atf);

        // System
        $backendSystem = $this->createMock(\BackendSystem::class);

        // Wiki attachments
        $wa = $this->createMock(\WikiAttachment::class);
        $wa->expects(self::once())->method('deleteProjectAttachments')->willReturn(true);
        $evt->method('getWikiAttachment')->willReturn($wa);

        $evt->method('getBackend')->willReturnMap([
            ['System', $backendSystem],
        ]);

        $evt->expects(self::never())->method('done');
        $evt->expects(self::once())->method('error')->with("Could not remove FRS items");

        $evt->method('getEventManager')->willReturn($this->createMock(EventManager::class));

        // Launch the event
        self::assertFalse($evt->process());
    }

    /**
     * Project delete Trackers fail
     *
     * @return Void
     */
    public function testProjectDeleteTrackersFail(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = $this->getMockBuilder(\SystemEvent_PROJECT_DELETE::class)
            ->setConstructorArgs(
                [
                    '1',
                    SystemEvent::TYPE_PROJECT_DELETE,
                    SystemEvent::OWNER_ROOT,
                    '142',
                    SystemEvent::PRIORITY_HIGH,
                    SystemEvent::STATUS_RUNNING,
                    $now,
                    $now,
                    $now,
                    '',
                ]
            )
            ->onlyMethods([
                'getProject',
                'removeProjectMembers',
                'deleteMembershipRequestNotificationEntries',
                'cleanupProjectUgroupsBinding',
                'cleanupProjectFRS',
                'getArtifactTypeFactory',
                'getBackend',
                'getWikiAttachment',
                'done',
                'error',
                'getEventManager',
            ])
            ->getMock();

        $svn_authentication_cache_invalidator = $this->createMock(SVNAuthenticationCacheInvalidator::class);
        $svn_authentication_cache_invalidator->method('invalidateProjectCache');
        $evt->injectDependencies($svn_authentication_cache_invalidator);

        // The project
        $project = $this->createMock(\Project::class);
        $project->method('usesSVN')->willReturn(true);
        $evt->method('getProject')->with('142')->willReturn($project);

        //Remove users from project
        $evt->method('removeProjectMembers')->willReturn(true);

        $evt->method('deleteMembershipRequestNotificationEntries')->willReturn(true);

        //Cleanup ProjectUGroup binding
        $evt->method('cleanupProjectUgroupsBinding')->willReturn(true);

        //Cleanup FRS
        $evt->method('cleanupProjectFRS')->willReturn(true);

        //Delete all trackers
        $atf = $this->createMock(\ArtifactTypeFactory::class);
        $atf->method('preDeleteAllProjectArtifactTypes')->willReturn(false);
        $evt->method('getArtifactTypeFactory')->with($project)->willReturn($atf);

        // System
        $backendSystem = $this->createMock(\BackendSystem::class);

        // Wiki attachments
        $wa = $this->createMock(\WikiAttachment::class);
        $wa->expects(self::once())->method('deleteProjectAttachments')->willReturn(true);
        $evt->method('getWikiAttachment')->willReturn($wa);

        $evt->method('getBackend')->willReturnMap([
            ['System', $backendSystem],
        ]);

        $evt->expects(self::never())->method('done');
        $evt->expects(self::once())->method('error')->with("Could not mark all trackers as deleted");

        $evt->method('getEventManager')->willReturn($this->createMock(EventManager::class));

        // Launch the event
        self::assertFalse($evt->process());
    }

    /**
     * Project delete Wiki attacments fail
     *
     * @return Void
     */
    public function testProjectDeleteWikiAttacmentsFail(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = $this->getMockBuilder(\SystemEvent_PROJECT_DELETE::class)
            ->setConstructorArgs(
                [
                    '1',
                    SystemEvent::TYPE_PROJECT_DELETE,
                    SystemEvent::OWNER_ROOT,
                    '142',
                    SystemEvent::PRIORITY_HIGH,
                    SystemEvent::STATUS_RUNNING,
                    $now,
                    $now,
                    $now,
                    '',
                ]
            )
            ->onlyMethods([
                'getProject',
                'removeProjectMembers',
                'deleteMembershipRequestNotificationEntries',
                'cleanupProjectUgroupsBinding',
                'cleanupProjectFRS',
                'getArtifactTypeFactory',
                'getBackend',
                'getWikiAttachment',
                'done',
                'error',
                'getEventManager',
            ])
            ->getMock();

        $svn_authentication_cache_invalidator = $this->createMock(SVNAuthenticationCacheInvalidator::class);
        $svn_authentication_cache_invalidator->method('invalidateProjectCache');
        $evt->injectDependencies($svn_authentication_cache_invalidator);

        // The project
        $project = $this->createMock(\Project::class);
        $project->method('usesSVN')->willReturn(true);
        $evt->method('getProject')->with('142')->willReturn($project);

        //Remove users from project
        $evt->method('removeProjectMembers')->willReturn(true);

        $evt->method('deleteMembershipRequestNotificationEntries')->willReturn(true);

        //Cleanup ProjectUGroup binding
        $evt->method('cleanupProjectUgroupsBinding')->willReturn(true);

        //Cleanup FRS
        $evt->method('cleanupProjectFRS')->willReturn(true);

        //Delete all trackers
        $atf = $this->createMock(\ArtifactTypeFactory::class);
        $atf->method('preDeleteAllProjectArtifactTypes')->willReturn(true);
        $evt->method('getArtifactTypeFactory')->with($project)->willReturn($atf);

        // System
        $backendSystem = $this->createMock(\BackendSystem::class);

        // Wiki attachments
        $wa = $this->createMock(\WikiAttachment::class);
        $wa->expects(self::once())->method('deleteProjectAttachments')->willReturn(false);
        $evt->method('getWikiAttachment')->willReturn($wa);

        $evt->method('getBackend')->willReturnMap([
            ['System', $backendSystem],
        ]);

        $evt->expects(self::never())->method('done');
        $evt->expects(self::once())->method('error')->with("Could not mark all wiki attachments as deleted");

        $evt->method('getEventManager')->willReturn($this->createMock(EventManager::class));

        // Launch the event
        self::assertFalse($evt->process());
    }

    /**
     * Project delete ProjectUGroup binding fail
     *
     * @return Void
     */
    public function testProjectDeleteUgroupBindingFail(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = $this->getMockBuilder(\SystemEvent_PROJECT_DELETE::class)
            ->setConstructorArgs(
                [
                    '1',
                    SystemEvent::TYPE_PROJECT_DELETE,
                    SystemEvent::OWNER_ROOT,
                    '142',
                    SystemEvent::PRIORITY_HIGH,
                    SystemEvent::STATUS_RUNNING,
                    $now,
                    $now,
                    $now,
                    '',
                ]
            )
            ->onlyMethods([
                'getProject',
                'removeProjectMembers',
                'deleteMembershipRequestNotificationEntries',
                'cleanupProjectUgroupsBinding',
                'cleanupProjectFRS',
                'getArtifactTypeFactory',
                'getBackend',
                'getWikiAttachment',
                'done',
                'error',
                'getEventManager',
            ])
            ->getMock();

        $svn_authentication_cache_invalidator = $this->createMock(SVNAuthenticationCacheInvalidator::class);
        $svn_authentication_cache_invalidator->method('invalidateProjectCache');
        $evt->injectDependencies($svn_authentication_cache_invalidator);

        // The project
        $project = $this->createMock(\Project::class);
        $project->method('usesSVN')->willReturn(true);
        $evt->method('getProject')->with('142')->willReturn($project);

        //Remove users from project
        $evt->method('removeProjectMembers')->willReturn(true);

        $evt->method('deleteMembershipRequestNotificationEntries')->willReturn(true);

        //Cleanup ProjectUGroup binding
        $evt->method('cleanupProjectUgroupsBinding')->willReturn(false);

        //Cleanup FRS
        $evt->method('cleanupProjectFRS')->willReturn(true);

        //Delete all trackers
        $atf = $this->createMock(\ArtifactTypeFactory::class);
        $atf->method('preDeleteAllProjectArtifactTypes')->willReturn(true);
        $evt->method('getArtifactTypeFactory')->with($project)->willReturn($atf);

        // System
        $backendSystem = $this->createMock(\BackendSystem::class);

        // Wiki attachments
        $wa = $this->createMock(\WikiAttachment::class);
        $wa->expects(self::once())->method('deleteProjectAttachments')->willReturn(true);
        $evt->method('getWikiAttachment')->willReturn($wa);

        $evt->method('getBackend')->willReturnMap([
            ['System', $backendSystem],
        ]);

        $evt->expects(self::never())->method('done');
        $evt->expects(self::once())->method('error')->with("Could not remove ugroups binding");

        $evt->method('getEventManager')->willReturn($this->createMock(EventManager::class));

        // Launch the event
        self::assertFalse($evt->process());
    }

    /**
     * Project delete Succeed
     *
     * @return Void
     */
    public function testProjectDeleteSucceed(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = $this->getMockBuilder(\SystemEvent_PROJECT_DELETE::class)
            ->setConstructorArgs(
                [
                    '1',
                    SystemEvent::TYPE_PROJECT_DELETE,
                    SystemEvent::OWNER_ROOT,
                    '142',
                    SystemEvent::PRIORITY_HIGH,
                    SystemEvent::STATUS_RUNNING,
                    $now,
                    $now,
                    $now,
                    '',
                ]
            )
            ->onlyMethods([
                'getProject',
                'removeProjectMembers',
                'deleteMembershipRequestNotificationEntries',
                'cleanupProjectUgroupsBinding',
                'cleanupProjectFRS',
                'getArtifactTypeFactory',
                'getBackend',
                'getWikiAttachment',
                'done',
                'error',
                'getEventManager',
            ])
            ->getMock();

        $svn_authentication_cache_invalidator = $this->createMock(SVNAuthenticationCacheInvalidator::class);
        $svn_authentication_cache_invalidator->method('invalidateProjectCache');
        $evt->injectDependencies($svn_authentication_cache_invalidator);

        // The project
        $project = $this->createMock(\Project::class);
        $project->method('usesSVN')->willReturn(true);
        $evt->method('getProject')->with('142')->willReturn($project);

        //Remove users from project
        $evt->method('removeProjectMembers')->willReturn(true);

        $evt->method('deleteMembershipRequestNotificationEntries')->willReturn(true);

        //Cleanup ProjectUGroup binding
        $evt->method('cleanupProjectUgroupsBinding')->willReturn(true);

        //Cleanup FRS
        $evt->method('cleanupProjectFRS')->willReturn(true);

        //Delete all trackers
        $atf = $this->createMock(\ArtifactTypeFactory::class);
        $atf->method('preDeleteAllProjectArtifactTypes')->willReturn(true);
        $evt->method('getArtifactTypeFactory')->with($project)->willReturn($atf);

        // System
        $backendSystem = $this->createMock(\BackendSystem::class);

        // Wiki attachments
        $wa = $this->createMock(\WikiAttachment::class);
        $wa->expects(self::once())->method('deleteProjectAttachments')->willReturn(true);
        $evt->method('getWikiAttachment')->willReturn($wa);

        $evt->method('getBackend')->willReturnMap([
            ['System', $backendSystem],
        ]);

        // Expect everything went OK
        $evt->expects(self::once())->method('done');
        $evt->expects(self::never())->method('error');

        $evt->method('getEventManager')->willReturn($this->createMock(EventManager::class));

        // Launch the event
        self::assertTrue($evt->process());
    }
}
