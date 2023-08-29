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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\GlobalSVNPollution;
use Tuleap\SVNCore\SVNAuthenticationCacheInvalidator;

/**
 * Test for project delete system event
 */
//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class SystemEvent_PROJECT_DELETE_Test extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalSVNPollution;

    /**
     * Project delete Users fail
     *
     * @return Void
     */
    public function testProjectDeleteUsersFail(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_PROJECT_DELETE::class,
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
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $evt->injectDependencies(\Mockery::spy(SVNAuthenticationCacheInvalidator::class));

        // The project
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('usesSVN')->andReturns(true);
        $evt->shouldReceive('getProject')->with('142')->andReturns($project);

        //Remove users from project
        $evt->shouldReceive('removeProjectMembers')->andReturns(false);

        $evt->shouldReceive('deleteMembershipRequestNotificationEntries')->andReturns(true);

        //Cleanup ProjectUGroup binding
        $evt->shouldReceive('cleanupProjectUgroupsBinding')->andReturns(true);

        //Cleanup FRS
        $evt->shouldReceive('cleanupProjectFRS')->andReturns(true);

        //Delete all trackers
        $atf = \Mockery::spy(\ArtifactTypeFactory::class);
        $atf->shouldReceive('preDeleteAllProjectArtifactTypes')->andReturns(true);
        $evt->shouldReceive('getArtifactTypeFactory')->with($project)->andReturns($atf);

        // System
        $backendSystem = \Mockery::spy(\BackendSystem::class);
        $evt->shouldReceive('getBackend')->with('System')->andReturns($backendSystem);

        // Wiki attachments
        $wa = \Mockery::spy(\WikiAttachment::class);
        $wa->shouldReceive('deleteProjectAttachments')->once()->andReturns(true);
        $evt->shouldReceive('getWikiAttachment')->andReturns($wa);

        // SVN
        $backendSVN = \Mockery::spy(\BackendSVN::class);
        $backendSVN->shouldReceive('repositoryExists')->andReturns(true);
        $backendSVN->shouldReceive('archiveProjectSVN')->andReturns(true);
        $backendSVN->shouldReceive('setSVNApacheConfNeedUpdate')->once();
        $evt->shouldReceive('getBackend')->with('SVN')->andReturns($backendSVN);

        $evt->shouldReceive('done')->never();
        $evt->shouldReceive('error')->with("Could not remove project users")->once();

        $evt->shouldReceive('getEventManager')->andReturns(\Mockery::spy(EventManager::class));

        // Launch the event
        $this->assertFalse($evt->process());
    }

    /**
     * Project delete embership request
     *
     * @return Void
     */
    public function testProjectDeleteMembershipRequestNotificationUGroupFail(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_PROJECT_DELETE::class,
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
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $evt->injectDependencies(\Mockery::spy(SVNAuthenticationCacheInvalidator::class));

        // The project
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('usesSVN')->andReturns(true);
        $evt->shouldReceive('getProject')->with('142')->andReturns($project);

        //Remove users from project
        $evt->shouldReceive('removeProjectMembers')->andReturns(true);

        $evt->shouldReceive('deleteMembershipRequestNotificationEntries')->andReturns(false);

        //Cleanup ProjectUGroup binding
        $evt->shouldReceive('cleanupProjectUgroupsBinding')->andReturns(true);

        //Cleanup FRS
        $evt->shouldReceive('cleanupProjectFRS')->andReturns(true);

        //Delete all trackers
        $atf = \Mockery::spy(\ArtifactTypeFactory::class);
        $atf->shouldReceive('preDeleteAllProjectArtifactTypes')->andReturns(true);
        $evt->shouldReceive('getArtifactTypeFactory')->with($project)->andReturns($atf);

        // System
        $backendSystem = \Mockery::spy(\BackendSystem::class);
        $evt->shouldReceive('getBackend')->with('System')->andReturns($backendSystem);

        // Wiki attachments
        $wa = \Mockery::spy(\WikiAttachment::class);
        $wa->shouldReceive('deleteProjectAttachments')->once()->andReturns(true);
        $evt->shouldReceive('getWikiAttachment')->andReturns($wa);

        // SVN
        $backendSVN = \Mockery::spy(\BackendSVN::class);
        $backendSVN->shouldReceive('repositoryExists')->andReturns(true);
        $backendSVN->shouldReceive('archiveProjectSVN')->andReturns(true);
        $backendSVN->shouldReceive('setSVNApacheConfNeedUpdate')->once();
        $evt->shouldReceive('getBackend')->with('SVN')->andReturns($backendSVN);

        $evt->shouldReceive('done')->never();
        $evt->shouldReceive('error')->with("Could not remove membership request notification ugroups or message")->once();

        $evt->shouldReceive('getEventManager')->andReturns(\Mockery::spy(EventManager::class));

        // Launch the event
        $this->assertFalse($evt->process());
    }

    /**
     * Project delete FRS fail
     *
     * @return Void
     */
    public function testProjectDeleteFRSFail(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_PROJECT_DELETE::class,
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
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $evt->injectDependencies(\Mockery::spy(SVNAuthenticationCacheInvalidator::class));
        // The project
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('usesSVN')->andReturns(true);
        $evt->shouldReceive('getProject')->with('142')->andReturns($project);

        //Remove users from project
        $evt->shouldReceive('removeProjectMembers')->andReturns(true);

        $evt->shouldReceive('deleteMembershipRequestNotificationEntries')->andReturns(true);

        //Cleanup ProjectUGroup binding
        $evt->shouldReceive('cleanupProjectUgroupsBinding')->andReturns(true);

        //Cleanup FRS
        $evt->shouldReceive('cleanupProjectFRS')->andReturns(false);

        //Delete all trackers
        $atf = \Mockery::spy(\ArtifactTypeFactory::class);
        $atf->shouldReceive('preDeleteAllProjectArtifactTypes')->andReturns(true);
        $evt->shouldReceive('getArtifactTypeFactory')->with($project)->andReturns($atf);

        // System
        $backendSystem = \Mockery::spy(\BackendSystem::class);
        $evt->shouldReceive('getBackend')->with('System')->andReturns($backendSystem);

        // Wiki attachments
        $wa = \Mockery::spy(\WikiAttachment::class);
        $wa->shouldReceive('deleteProjectAttachments')->once()->andReturns(true);
        $evt->shouldReceive('getWikiAttachment')->andReturns($wa);

        // SVN
        $backendSVN = \Mockery::spy(\BackendSVN::class);
        $backendSVN->shouldReceive('repositoryExists')->andReturns(true);
        $backendSVN->shouldReceive('archiveProjectSVN')->andReturns(true);
        $backendSVN->shouldReceive('setSVNApacheConfNeedUpdate')->once();
        $evt->shouldReceive('getBackend')->with('SVN')->andReturns($backendSVN);

        $evt->shouldReceive('done')->never();
        $evt->shouldReceive('error')->with("Could not remove FRS items")->once();

        $evt->shouldReceive('getEventManager')->andReturns(\Mockery::spy(EventManager::class));

        // Launch the event
        $this->assertFalse($evt->process());
    }

    /**
     * Project delete Trackers fail
     *
     * @return Void
     */
    public function testProjectDeleteTrackersFail(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_PROJECT_DELETE::class,
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
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $evt->injectDependencies(\Mockery::spy(SVNAuthenticationCacheInvalidator::class));

        // The project
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('usesSVN')->andReturns(true);
        $evt->shouldReceive('getProject')->with('142')->andReturns($project);

        //Remove users from project
        $evt->shouldReceive('removeProjectMembers')->andReturns(true);

        $evt->shouldReceive('deleteMembershipRequestNotificationEntries')->andReturns(true);

        //Cleanup ProjectUGroup binding
        $evt->shouldReceive('cleanupProjectUgroupsBinding')->andReturns(true);

        //Cleanup FRS
        $evt->shouldReceive('cleanupProjectFRS')->andReturns(true);

        //Delete all trackers
        $atf = \Mockery::spy(\ArtifactTypeFactory::class);
        $atf->shouldReceive('preDeleteAllProjectArtifactTypes')->andReturns(false);
        $evt->shouldReceive('getArtifactTypeFactory')->with($project)->andReturns($atf);

        // System
        $backendSystem = \Mockery::spy(\BackendSystem::class);
        $evt->shouldReceive('getBackend')->with('System')->andReturns($backendSystem);

        // Wiki attachments
        $wa = \Mockery::spy(\WikiAttachment::class);
        $wa->shouldReceive('deleteProjectAttachments')->once()->andReturns(true);
        $evt->shouldReceive('getWikiAttachment')->andReturns($wa);

        // SVN
        $backendSVN = \Mockery::spy(\BackendSVN::class);
        $backendSVN->shouldReceive('repositoryExists')->andReturns(true);
        $backendSVN->shouldReceive('archiveProjectSVN')->andReturns(true);
        $backendSVN->shouldReceive('setSVNApacheConfNeedUpdate')->once();
        $evt->shouldReceive('getBackend')->with('SVN')->andReturns($backendSVN);

        $evt->shouldReceive('done')->never();
        $evt->shouldReceive('error')->with("Could not mark all trackers as deleted")->once();

        $evt->shouldReceive('getEventManager')->andReturns(\Mockery::spy(EventManager::class));

        // Launch the event
        $this->assertFalse($evt->process());
    }

    /**
     * Project delete Wiki attacments fail
     *
     * @return Void
     */
    public function testProjectDeleteWikiAttacmentsFail(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_PROJECT_DELETE::class,
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
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $evt->injectDependencies(\Mockery::spy(SVNAuthenticationCacheInvalidator::class));

        // The project
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('usesSVN')->andReturns(true);
        $evt->shouldReceive('getProject')->with('142')->andReturns($project);

        //Remove users from project
        $evt->shouldReceive('removeProjectMembers')->andReturns(true);

        $evt->shouldReceive('deleteMembershipRequestNotificationEntries')->andReturns(true);

        //Cleanup ProjectUGroup binding
        $evt->shouldReceive('cleanupProjectUgroupsBinding')->andReturns(true);

        //Cleanup FRS
        $evt->shouldReceive('cleanupProjectFRS')->andReturns(true);

        //Delete all trackers
        $atf = \Mockery::spy(\ArtifactTypeFactory::class);
        $atf->shouldReceive('preDeleteAllProjectArtifactTypes')->andReturns(true);
        $evt->shouldReceive('getArtifactTypeFactory')->with($project)->andReturns($atf);

        // System
        $backendSystem = \Mockery::spy(\BackendSystem::class);
        $evt->shouldReceive('getBackend')->with('System')->andReturns($backendSystem);

        // Wiki attachments
        $wa = \Mockery::spy(\WikiAttachment::class);
        $wa->shouldReceive('deleteProjectAttachments')->once()->andReturns(false);
        $evt->shouldReceive('getWikiAttachment')->andReturns($wa);

        // SVN
        $backendSVN = \Mockery::spy(\BackendSVN::class);
        $backendSVN->shouldReceive('repositoryExists')->andReturns(true);
        $backendSVN->shouldReceive('archiveProjectSVN')->andReturns(true);
        $backendSVN->shouldReceive('setSVNApacheConfNeedUpdate')->once();
        $evt->shouldReceive('getBackend')->with('SVN')->andReturns($backendSVN);

        $evt->shouldReceive('done')->never();
        $evt->shouldReceive('error')->with("Could not mark all wiki attachments as deleted")->once();

        $evt->shouldReceive('getEventManager')->andReturns(\Mockery::spy(EventManager::class));

        // Launch the event
        $this->assertFalse($evt->process());
    }

    /**
     * Project delete SVN fail
     *
     * @return Void
     */
    public function testProjectDeleteSVNFail(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_PROJECT_DELETE::class,
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
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $evt->injectDependencies(\Mockery::spy(SVNAuthenticationCacheInvalidator::class));

        // The project
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('usesSVN')->andReturns(true);
        $evt->shouldReceive('getProject')->with('142')->andReturns($project);

        //Remove users from project
        $evt->shouldReceive('removeProjectMembers')->andReturns(true);

        $evt->shouldReceive('deleteMembershipRequestNotificationEntries')->andReturns(true);

        //Cleanup ProjectUGroup binding
        $evt->shouldReceive('cleanupProjectUgroupsBinding')->andReturns(true);

        //Cleanup FRS
        $evt->shouldReceive('cleanupProjectFRS')->andReturns(true);

        //Delete all trackers
        $atf = \Mockery::spy(\ArtifactTypeFactory::class);
        $atf->shouldReceive('preDeleteAllProjectArtifactTypes')->andReturns(true);
        $evt->shouldReceive('getArtifactTypeFactory')->with($project)->andReturns($atf);

        // System
        $backendSystem = \Mockery::spy(\BackendSystem::class);
        $evt->shouldReceive('getBackend')->with('System')->andReturns($backendSystem);

        // Wiki attachments
        $wa = \Mockery::spy(\WikiAttachment::class);
        $wa->shouldReceive('deleteProjectAttachments')->once()->andReturns(true);
        $evt->shouldReceive('getWikiAttachment')->andReturns($wa);

        // SVN
        $backendSVN = \Mockery::spy(\BackendSVN::class);
        $backendSVN->shouldReceive('repositoryExists')->andReturns(true);
        $backendSVN->shouldReceive('archiveProjectSVN')->andReturns(false);
        $backendSVN->shouldReceive('setSVNApacheConfNeedUpdate')->never();
        $evt->shouldReceive('getBackend')->with('SVN')->andReturns($backendSVN);

        $evt->shouldReceive('done')->never();
        $evt->shouldReceive('error')->with("Could not archive project SVN repository")->once();

        $evt->shouldReceive('getEventManager')->andReturns(\Mockery::spy(EventManager::class));

        // Launch the event
        $this->assertFalse($evt->process());
    }

    /**
     * Project delete ProjectUGroup binding fail
     *
     * @return Void
     */
    public function testProjectDeleteUgroupBindingFail(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_PROJECT_DELETE::class,
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
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $evt->injectDependencies(\Mockery::spy(SVNAuthenticationCacheInvalidator::class));

        // The project
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('usesSVN')->andReturns(true);
        $evt->shouldReceive('getProject')->with('142')->andReturns($project);

        //Remove users from project
        $evt->shouldReceive('removeProjectMembers')->andReturns(true);

        $evt->shouldReceive('deleteMembershipRequestNotificationEntries')->andReturns(true);

        //Cleanup ProjectUGroup binding
        $evt->shouldReceive('cleanupProjectUgroupsBinding')->andReturns(false);

        //Cleanup FRS
        $evt->shouldReceive('cleanupProjectFRS')->andReturns(true);

        //Delete all trackers
        $atf = \Mockery::spy(\ArtifactTypeFactory::class);
        $atf->shouldReceive('preDeleteAllProjectArtifactTypes')->andReturns(true);
        $evt->shouldReceive('getArtifactTypeFactory')->with($project)->andReturns($atf);

        // System
        $backendSystem = \Mockery::spy(\BackendSystem::class);
        $evt->shouldReceive('getBackend')->with('System')->andReturns($backendSystem);

        // Wiki attachments
        $wa = \Mockery::spy(\WikiAttachment::class);
        $wa->shouldReceive('deleteProjectAttachments')->once()->andReturns(true);
        $evt->shouldReceive('getWikiAttachment')->andReturns($wa);

        // SVN
        $backendSVN = \Mockery::spy(\BackendSVN::class);
        $backendSVN->shouldReceive('repositoryExists')->andReturns(true);
        $backendSVN->shouldReceive('archiveProjectSVN')->andReturns(true);
        $backendSVN->shouldReceive('setSVNApacheConfNeedUpdate')->once();
        $evt->shouldReceive('getBackend')->with('SVN')->andReturns($backendSVN);

        $evt->shouldReceive('done')->never();
        $evt->shouldReceive('error')->with("Could not remove ugroups binding")->once();

        $evt->shouldReceive('getEventManager')->andReturns(\Mockery::spy(EventManager::class));

        // Launch the event
        $this->assertFalse($evt->process());
    }

    /**
     * Project delete Succeed
     *
     * @return Void
     */
    public function testProjectDeleteSucceed(): void
    {
        $now = (new DateTimeImmutable())->getTimestamp();

        $evt = \Mockery::mock(
            \SystemEvent_PROJECT_DELETE::class,
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
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $evt->injectDependencies(\Mockery::spy(SVNAuthenticationCacheInvalidator::class));

        // The project
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('usesSVN')->andReturns(true);
        $evt->shouldReceive('getProject')->with('142')->andReturns($project);

        //Remove users from project
        $evt->shouldReceive('removeProjectMembers')->andReturns(true);

        $evt->shouldReceive('deleteMembershipRequestNotificationEntries')->andReturns(true);

        //Cleanup ProjectUGroup binding
        $evt->shouldReceive('cleanupProjectUgroupsBinding')->andReturns(true);

        //Cleanup FRS
        $evt->shouldReceive('cleanupProjectFRS')->andReturns(true);

        //Delete all trackers
        $atf = \Mockery::spy(\ArtifactTypeFactory::class);
        $atf->shouldReceive('preDeleteAllProjectArtifactTypes')->andReturns(true);
        $evt->shouldReceive('getArtifactTypeFactory')->with($project)->andReturns($atf);

        // System
        $backendSystem = \Mockery::spy(\BackendSystem::class);
        $evt->shouldReceive('getBackend')->with('System')->andReturns($backendSystem);

        // Wiki attachments
        $wa = \Mockery::spy(\WikiAttachment::class);
        $wa->shouldReceive('deleteProjectAttachments')->once()->andReturns(true);
        $evt->shouldReceive('getWikiAttachment')->andReturns($wa);

        // SVN
        $backendSVN = \Mockery::spy(\BackendSVN::class);
        $backendSVN->shouldReceive('repositoryExists')->andReturns(true);
        $backendSVN->shouldReceive('archiveProjectSVN')->andReturns(true);
        $backendSVN->shouldReceive('setSVNApacheConfNeedUpdate')->once();
        $evt->shouldReceive('getBackend')->with('SVN')->andReturns($backendSVN);

        // Expect everything went OK
        $evt->shouldReceive('done')->once();
        $evt->shouldReceive('error')->never();

        $evt->shouldReceive('getEventManager')->andReturns(\Mockery::spy(EventManager::class));

        // Launch the event
        $this->assertTrue($evt->process());
    }
}
