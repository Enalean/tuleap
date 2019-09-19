<?php
/**
 * Copyright (c) Enalean, 2013-2018. All Rights Reserved.
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

use Tuleap\SVN\SVNAuthenticationCacheInvalidator;

Mock::generatePartial(
    'SystemEvent_PROJECT_DELETE',
    'SystemEvent_PROJECT_DELETE_TestVersion',
    array('getProject',
                            'getBackend',
                            'done',
                            'removeProjectMembers',
                            'getArtifactTypeFactory',
                            'getFRSFileFactory',
                            'cleanupProjectFRS',
                            'error',
                            'getWikiAttachment',
                            'deleteMembershipRequestNotificationEntries',
                            'deleteProjectMailingLists',
                            'getEventManager',
    'cleanupProjectUgroupsBinding')
);

Mock::generate('BackendSystem');
Mock::generate('Project');
Mock::generate('BackendSVN');
Mock::generate('BackendCVS');
Mock::generate('BackendMailingList');
Mock::generate('BackendAliases');
Mock::generate('ArtifactTypeFactory');
Mock::generate('WikiAttachment');

/**
 * Test for project delete system event
 */
class SystemEvent_PROJECT_DELETE_Test extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('grpdir_prefix', '/tmp');
    }

    function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    /**
     * Project delete Users fail
     *
     * @return Void
     */
    public function testProjectDeleteUsersFail()
    {
        $evt = new SystemEvent_PROJECT_DELETE_TestVersion();
        $evt->__construct('1', SystemEvent::TYPE_PROJECT_DELETE, SystemEvent::OWNER_ROOT, '142', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');
        $evt->injectDependencies(\Mockery::spy(SVNAuthenticationCacheInvalidator::class));

        // The project
        $project = new MockProject();
        $project->setReturnValue('usesCVS', true);
        $project->setReturnValue('usesSVN', true);
        $evt->setReturnValue('getProject', $project, array('142'));

        //Remove users from project
        $evt->setReturnValue('removeProjectMembers', false);

        $evt->setReturnValue('deleteMembershipRequestNotificationEntries', true);

        //Cleanup ProjectUGroup binding
        $evt->setReturnValue('cleanupProjectUgroupsBinding', true);

        //Cleanup FRS
        $evt->setReturnValue('cleanupProjectFRS', true);

        //Delete all trackers
        $atf = new  MockArtifactTypeFactory();
        $atf->setReturnValue('preDeleteAllProjectArtifactTypes', true);
        $evt->setReturnValue('getArtifactTypeFactory', $atf, array($project));

        // System
        $backendSystem = new MockBackendSystem();
        $backendSystem->setReturnValue('projectHomeExists', true);
        $backendSystem->setReturnValue('archiveProjectHome', true);
        $backendSystem->setReturnValue('archiveProjectFtp', true);
        $backendSystem->expectOnce('setNeedRefreshGroupCache');
        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // Wiki attachments
        $wa = new MockWikiAttachment();
        $wa->setReturnValue('deleteProjectAttachments', true);
        $wa->expectOnce('deleteProjectAttachments');
        $evt->setReturnValue('getWikiAttachment', $wa);

        // CVS
        $backendCVS = new MockBackendCVS();
        $backendCVS->setReturnValue('repositoryExists', true);
        $backendCVS->setReturnValue('archiveProjectCVS', true);
        $backendCVS->expectOnce('setCVSRootListNeedUpdate');
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // SVN
        $backendSVN = new MockBackendSVN();
        $backendSVN->setReturnValue('repositoryExists', true);
        $backendSVN->setReturnValue('archiveProjectSVN', true);
        $backendSVN->expectOnce('setSVNApacheConfNeedUpdate');
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // MailingList
        $backendMailingList = new MockBackendMailingList();
        $backendMailingList->setReturnValue('deleteProjectMailingLists', true);
        $backendMailingList->expectOnce('deleteProjectMailingLists');
        $evt->setReturnValue('getBackend', $backendMailingList, array('MailingList'));

        // Aliases
        $backendAliases = new MockBackendAliases();
        $backendAliases->expectOnce('setNeedUpdateMailAliases');
        $evt->setReturnValue('getBackend', $backendAliases, array('Aliases'));

        $evt->expectNever('done');
        $evt->expectOnce('error', array("Could not remove project users"));

        $evt->setReturnValue('getEventManager', \Mockery::spy(EventManager::class));

        // Launch the event
        $this->assertFalse($evt->process());
    }

    /**
     * Project delete embership request
     *
     * @return Void
     */
    public function testProjectDeleteMembershipRequestNotificationUGroupFail()
    {
        $evt = new SystemEvent_PROJECT_DELETE_TestVersion();
        $evt->__construct('1', SystemEvent::TYPE_PROJECT_DELETE, SystemEvent::OWNER_ROOT, '142', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');
        $evt->injectDependencies(\Mockery::spy(SVNAuthenticationCacheInvalidator::class));

        // The project
        $project = new MockProject();
        $project->setReturnValue('usesCVS', true);
        $project->setReturnValue('usesSVN', true);
        $evt->setReturnValue('getProject', $project, array('142'));

        //Remove users from project
        $evt->setReturnValue('removeProjectMembers', true);

        $evt->setReturnValue('deleteMembershipRequestNotificationEntries', false);

        //Cleanup ProjectUGroup binding
        $evt->setReturnValue('cleanupProjectUgroupsBinding', true);

        //Cleanup FRS
        $evt->setReturnValue('cleanupProjectFRS', true);

        //Delete all trackers
        $atf = new  MockArtifactTypeFactory();
        $atf->setReturnValue('preDeleteAllProjectArtifactTypes', true);
        $evt->setReturnValue('getArtifactTypeFactory', $atf, array($project));

        // System
        $backendSystem = new MockBackendSystem();
        $backendSystem->setReturnValue('projectHomeExists', true);
        $backendSystem->setReturnValue('archiveProjectHome', true);
        $backendSystem->setReturnValue('archiveProjectFtp', true);
        $backendSystem->expectOnce('setNeedRefreshGroupCache');
        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // Wiki attachments
        $wa = new MockWikiAttachment();
        $wa->setReturnValue('deleteProjectAttachments', true);
        $wa->expectOnce('deleteProjectAttachments');
        $evt->setReturnValue('getWikiAttachment', $wa);

        // CVS
        $backendCVS = new MockBackendCVS();
        $backendCVS->setReturnValue('repositoryExists', true);
        $backendCVS->setReturnValue('archiveProjectCVS', true);
        $backendCVS->expectOnce('setCVSRootListNeedUpdate');
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // SVN
        $backendSVN = new MockBackendSVN();
        $backendSVN->setReturnValue('repositoryExists', true);
        $backendSVN->setReturnValue('archiveProjectSVN', true);
        $backendSVN->expectOnce('setSVNApacheConfNeedUpdate');
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // MailingList
        $backendMailingList = new MockBackendMailingList();
        $backendMailingList->setReturnValue('deleteProjectMailingLists', true);
        $backendMailingList->expectOnce('deleteProjectMailingLists');
        $evt->setReturnValue('getBackend', $backendMailingList, array('MailingList'));

        // Aliases
        $backendAliases = new MockBackendAliases();
        $backendAliases->expectOnce('setNeedUpdateMailAliases');
        $evt->setReturnValue('getBackend', $backendAliases, array('Aliases'));

        $evt->expectNever('done');
        $evt->expectOnce('error', array("Could not remove membership request notification ugroups or message"));

        $evt->setReturnValue('getEventManager', \Mockery::spy(EventManager::class));

        // Launch the event
        $this->assertFalse($evt->process());
    }

    /**
     * Project delete FRS fail
     *
     * @return Void
     */
    public function testProjectDeleteFRSFail()
    {
        $evt = new SystemEvent_PROJECT_DELETE_TestVersion();
        $evt->__construct('1', SystemEvent::TYPE_PROJECT_DELETE, SystemEvent::OWNER_ROOT, '142', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');
        $evt->injectDependencies(\Mockery::spy(SVNAuthenticationCacheInvalidator::class));
        // The project
        $project = new MockProject();
        $project->setReturnValue('usesCVS', true);
        $project->setReturnValue('usesSVN', true);
        $evt->setReturnValue('getProject', $project, array('142'));

        //Remove users from project
        $evt->setReturnValue('removeProjectMembers', true);

        $evt->setReturnValue('deleteMembershipRequestNotificationEntries', true);

        //Cleanup ProjectUGroup binding
        $evt->setReturnValue('cleanupProjectUgroupsBinding', true);

        //Cleanup FRS
        $evt->setReturnValue('cleanupProjectFRS', false);

        //Delete all trackers
        $atf = new  MockArtifactTypeFactory();
        $atf->setReturnValue('preDeleteAllProjectArtifactTypes', true);
        $evt->setReturnValue('getArtifactTypeFactory', $atf, array($project));

        // System
        $backendSystem = new MockBackendSystem();
        $backendSystem->setReturnValue('projectHomeExists', true);
        $backendSystem->setReturnValue('archiveProjectHome', true);
        $backendSystem->setReturnValue('archiveProjectFtp', true);
        $backendSystem->expectOnce('setNeedRefreshGroupCache');
        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // Wiki attachments
        $wa = new MockWikiAttachment();
        $wa->setReturnValue('deleteProjectAttachments', true);
        $wa->expectOnce('deleteProjectAttachments');
        $evt->setReturnValue('getWikiAttachment', $wa);

        // CVS
        $backendCVS = new MockBackendCVS();
        $backendCVS->setReturnValue('repositoryExists', true);
        $backendCVS->setReturnValue('archiveProjectCVS', true);
        $backendCVS->expectOnce('setCVSRootListNeedUpdate');
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // SVN
        $backendSVN = new MockBackendSVN();
        $backendSVN->setReturnValue('repositoryExists', true);
        $backendSVN->setReturnValue('archiveProjectSVN', true);
        $backendSVN->expectOnce('setSVNApacheConfNeedUpdate');
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // MailingList
        $backendMailingList = new MockBackendMailingList();
        $backendMailingList->setReturnValue('deleteProjectMailingLists', true);
        $backendMailingList->expectOnce('deleteProjectMailingLists');
        $evt->setReturnValue('getBackend', $backendMailingList, array('MailingList'));

        // Aliases
        $backendAliases = new MockBackendAliases();
        $backendAliases->expectOnce('setNeedUpdateMailAliases');
        $evt->setReturnValue('getBackend', $backendAliases, array('Aliases'));

        $evt->expectNever('done');
        $evt->expectOnce('error', array("Could not remove FRS items"));

        $evt->setReturnValue('getEventManager', \Mockery::spy(EventManager::class));

        // Launch the event
        $this->assertFalse($evt->process());
    }

    /**
     * Project delete Trackers fail
     *
     * @return Void
     */
    public function testProjectDeleteTrackersFail()
    {
        $evt = new SystemEvent_PROJECT_DELETE_TestVersion();
        $evt->__construct('1', SystemEvent::TYPE_PROJECT_DELETE, SystemEvent::OWNER_ROOT, '142', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');
        $evt->injectDependencies(\Mockery::spy(SVNAuthenticationCacheInvalidator::class));

        // The project
        $project = new MockProject();
        $project->setReturnValue('usesCVS', true);
        $project->setReturnValue('usesSVN', true);
        $evt->setReturnValue('getProject', $project, array('142'));

        //Remove users from project
        $evt->setReturnValue('removeProjectMembers', true);

        $evt->setReturnValue('deleteMembershipRequestNotificationEntries', true);

        //Cleanup ProjectUGroup binding
        $evt->setReturnValue('cleanupProjectUgroupsBinding', true);

        //Cleanup FRS
        $evt->setReturnValue('cleanupProjectFRS', true);

        //Delete all trackers
        $atf = new  MockArtifactTypeFactory();
        $atf->setReturnValue('preDeleteAllProjectArtifactTypes', false);
        $evt->setReturnValue('getArtifactTypeFactory', $atf, array($project));

        // System
        $backendSystem = new MockBackendSystem();
        $backendSystem->setReturnValue('projectHomeExists', true);
        $backendSystem->setReturnValue('archiveProjectHome', true);
        $backendSystem->setReturnValue('archiveProjectFtp', true);
        $backendSystem->expectOnce('setNeedRefreshGroupCache');
        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // Wiki attachments
        $wa = new MockWikiAttachment();
        $wa->setReturnValue('deleteProjectAttachments', true);
        $wa->expectOnce('deleteProjectAttachments');
        $evt->setReturnValue('getWikiAttachment', $wa);

        // CVS
        $backendCVS = new MockBackendCVS();
        $backendCVS->setReturnValue('repositoryExists', true);
        $backendCVS->setReturnValue('archiveProjectCVS', true);
        $backendCVS->expectOnce('setCVSRootListNeedUpdate');
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // SVN
        $backendSVN = new MockBackendSVN();
        $backendSVN->setReturnValue('repositoryExists', true);
        $backendSVN->setReturnValue('archiveProjectSVN', true);
        $backendSVN->expectOnce('setSVNApacheConfNeedUpdate');
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // MailingList
        $backendMailingList = new MockBackendMailingList();
        $backendMailingList->setReturnValue('deleteProjectMailingLists', true);
        $backendMailingList->expectOnce('deleteProjectMailingLists');
        $evt->setReturnValue('getBackend', $backendMailingList, array('MailingList'));

        // Aliases
        $backendAliases = new MockBackendAliases();
        $backendAliases->expectOnce('setNeedUpdateMailAliases');
        $evt->setReturnValue('getBackend', $backendAliases, array('Aliases'));

        $evt->expectNever('done');
        $evt->expectOnce('error', array("Could not mark all trackers as deleted"));

        $evt->setReturnValue('getEventManager', \Mockery::spy(EventManager::class));

        // Launch the event
        $this->assertFalse($evt->process());
    }

    /**
     * Project delete Home fail
     *
     * @return Void
     */
    public function testProjectDeleteProjectHomeFail()
    {
        $evt = new SystemEvent_PROJECT_DELETE_TestVersion();
        $evt->__construct('1', SystemEvent::TYPE_PROJECT_DELETE, SystemEvent::OWNER_ROOT, '142', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');
        $evt->injectDependencies(\Mockery::spy(SVNAuthenticationCacheInvalidator::class));

        // The project
        $project = new MockProject();
        $project->setReturnValue('usesCVS', true);
        $project->setReturnValue('usesSVN', true);
        $evt->setReturnValue('getProject', $project, array('142'));

        //Remove users from project
        $evt->setReturnValue('removeProjectMembers', true);

        $evt->setReturnValue('deleteMembershipRequestNotificationEntries', true);

        //Cleanup ProjectUGroup binding
        $evt->setReturnValue('cleanupProjectUgroupsBinding', true);

        //Cleanup FRS
        $evt->setReturnValue('cleanupProjectFRS', true);

        //Delete all trackers
        $atf = new  MockArtifactTypeFactory();
        $atf->setReturnValue('preDeleteAllProjectArtifactTypes', true);
        $evt->setReturnValue('getArtifactTypeFactory', $atf, array($project));

        // System
        $backendSystem = new MockBackendSystem();
        $backendSystem->setReturnValue('projectHomeExists', true);
        $backendSystem->setReturnValue('archiveProjectHome', false);
        $backendSystem->setReturnValue('archiveProjectFtp', true);
        $backendSystem->expectNever('setNeedRefreshGroupCache');
        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // Wiki attachments
        $wa = new MockWikiAttachment();
        $wa->setReturnValue('deleteProjectAttachments', true);
        $wa->expectOnce('deleteProjectAttachments');
        $evt->setReturnValue('getWikiAttachment', $wa);

        // CVS
        $backendCVS = new MockBackendCVS();
        $backendCVS->setReturnValue('repositoryExists', true);
        $backendCVS->setReturnValue('archiveProjectCVS', true);
        $backendCVS->expectOnce('setCVSRootListNeedUpdate');
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // SVN
        $backendSVN = new MockBackendSVN();
        $backendSVN->setReturnValue('repositoryExists', true);
        $backendSVN->setReturnValue('archiveProjectSVN', true);
        $backendSVN->expectOnce('setSVNApacheConfNeedUpdate');
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // MailingList
        $backendMailingList = new MockBackendMailingList();
        $backendMailingList->setReturnValue('deleteProjectMailingLists', true);
        $backendMailingList->expectOnce('deleteProjectMailingLists');
        $evt->setReturnValue('getBackend', $backendMailingList, array('MailingList'));

        // Aliases
        $backendAliases = new MockBackendAliases();
        $backendAliases->expectOnce('setNeedUpdateMailAliases');
        $evt->setReturnValue('getBackend', $backendAliases, array('Aliases'));

        $evt->expectNever('done');
        $evt->expectOnce('error', array("Could not archive project home"));

        $evt->setReturnValue('getEventManager', \Mockery::spy(EventManager::class));

        // Launch the event
        $this->assertFalse($evt->process());
    }

    /**
     * Project delete Public FTP fail
     *
     * @return Void
     */
    public function testProjectDeletePublicFtpFail()
    {
        $evt = new SystemEvent_PROJECT_DELETE_TestVersion();
        $evt->__construct('1', SystemEvent::TYPE_PROJECT_DELETE, SystemEvent::OWNER_ROOT, '142', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');
        $evt->injectDependencies(\Mockery::spy(SVNAuthenticationCacheInvalidator::class));

        // The project
        $project = new MockProject();
        $project->setReturnValue('usesCVS', true);
        $project->setReturnValue('usesSVN', true);
        $evt->setReturnValue('getProject', $project, array('142'));

        //Remove users from project
        $evt->setReturnValue('removeProjectMembers', true);

        $evt->setReturnValue('deleteMembershipRequestNotificationEntries', true);

        //Cleanup ProjectUGroup binding
        $evt->setReturnValue('cleanupProjectUgroupsBinding', true);

        //Cleanup FRS
        $evt->setReturnValue('cleanupProjectFRS', true);

        //Delete all trackers
        $atf = new  MockArtifactTypeFactory();
        $atf->setReturnValue('preDeleteAllProjectArtifactTypes', true);
        $evt->setReturnValue('getArtifactTypeFactory', $atf, array($project));

        // System
        $backendSystem = new MockBackendSystem();
        $backendSystem->setReturnValue('projectHomeExists', true);
        $backendSystem->setReturnValue('archiveProjectHome', true);
        $backendSystem->setReturnValue('archiveProjectFtp', false);
        $backendSystem->expectOnce('setNeedRefreshGroupCache');
        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // Wiki attachments
        $wa = new MockWikiAttachment();
        $wa->setReturnValue('deleteProjectAttachments', true);
        $wa->expectOnce('deleteProjectAttachments');
        $evt->setReturnValue('getWikiAttachment', $wa);

        // CVS
        $backendCVS = new MockBackendCVS();
        $backendCVS->setReturnValue('repositoryExists', true);
        $backendCVS->setReturnValue('archiveProjectCVS', true);
        $backendCVS->expectOnce('setCVSRootListNeedUpdate');
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // SVN
        $backendSVN = new MockBackendSVN();
        $backendSVN->setReturnValue('repositoryExists', true);
        $backendSVN->setReturnValue('archiveProjectSVN', true);
        $backendSVN->expectOnce('setSVNApacheConfNeedUpdate');
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // MailingList
        $backendMailingList = new MockBackendMailingList();
        $backendMailingList->setReturnValue('deleteProjectMailingLists', true);
        $backendMailingList->expectOnce('deleteProjectMailingLists');
        $evt->setReturnValue('getBackend', $backendMailingList, array('MailingList'));

        // Aliases
        $backendAliases = new MockBackendAliases();
        $backendAliases->expectOnce('setNeedUpdateMailAliases');
        $evt->setReturnValue('getBackend', $backendAliases, array('Aliases'));

        $evt->expectNever('done');
        $evt->expectOnce('error', array("Could not archive project public ftp"));

        $evt->setReturnValue('getEventManager', \Mockery::spy(EventManager::class));

        // Launch the event
        $this->assertFalse($evt->process());
    }

    /**
     * Project delete Wiki attacments fail
     *
     * @return Void
     */
    public function testProjectDeleteWikiAttacmentsFail()
    {
        $evt = new SystemEvent_PROJECT_DELETE_TestVersion();
        $evt->__construct('1', SystemEvent::TYPE_PROJECT_DELETE, SystemEvent::OWNER_ROOT, '142', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');
        $evt->injectDependencies(\Mockery::spy(SVNAuthenticationCacheInvalidator::class));

        // The project
        $project = new MockProject();
        $project->setReturnValue('usesCVS', true);
        $project->setReturnValue('usesSVN', true);
        $evt->setReturnValue('getProject', $project, array('142'));

        //Remove users from project
        $evt->setReturnValue('removeProjectMembers', true);

        $evt->setReturnValue('deleteMembershipRequestNotificationEntries', true);

        //Cleanup ProjectUGroup binding
        $evt->setReturnValue('cleanupProjectUgroupsBinding', true);

        //Cleanup FRS
        $evt->setReturnValue('cleanupProjectFRS', true);

        //Delete all trackers
        $atf = new  MockArtifactTypeFactory();
        $atf->setReturnValue('preDeleteAllProjectArtifactTypes', true);
        $evt->setReturnValue('getArtifactTypeFactory', $atf, array($project));

        // System
        $backendSystem = new MockBackendSystem();
        $backendSystem->setReturnValue('projectHomeExists', true);
        $backendSystem->setReturnValue('archiveProjectHome', true);
        $backendSystem->setReturnValue('archiveProjectFtp', true);
        $backendSystem->expectOnce('setNeedRefreshGroupCache');
        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // Wiki attachments
        $wa = new MockWikiAttachment();
        $wa->setReturnValue('deleteProjectAttachments', false);
        $wa->expectOnce('deleteProjectAttachments');
        $evt->setReturnValue('getWikiAttachment', $wa);

        // CVS
        $backendCVS = new MockBackendCVS();
        $backendCVS->setReturnValue('repositoryExists', true);
        $backendCVS->setReturnValue('archiveProjectCVS', true);
        $backendCVS->expectOnce('setCVSRootListNeedUpdate');
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // SVN
        $backendSVN = new MockBackendSVN();
        $backendSVN->setReturnValue('repositoryExists', true);
        $backendSVN->setReturnValue('archiveProjectSVN', true);
        $backendSVN->expectOnce('setSVNApacheConfNeedUpdate');
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // MailingList
        $backendMailingList = new MockBackendMailingList();
        $backendMailingList->setReturnValue('deleteProjectMailingLists', true);
        $backendMailingList->expectOnce('deleteProjectMailingLists');
        $evt->setReturnValue('getBackend', $backendMailingList, array('MailingList'));

        // Aliases
        $backendAliases = new MockBackendAliases();
        $backendAliases->expectOnce('setNeedUpdateMailAliases');
        $evt->setReturnValue('getBackend', $backendAliases, array('Aliases'));

        $evt->expectNever('done');
        $evt->expectOnce('error', array("Could not mark all wiki attachments as deleted"));

        $evt->setReturnValue('getEventManager', \Mockery::spy(EventManager::class));

        // Launch the event
        $this->assertFalse($evt->process());
    }

    /**
     * Project delete CVS fail
     *
     * @return Void
     */
    public function testProjectDeleteCVSFail()
    {
        $evt = new SystemEvent_PROJECT_DELETE_TestVersion();
        $evt->__construct('1', SystemEvent::TYPE_PROJECT_DELETE, SystemEvent::OWNER_ROOT, '142', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');
        $evt->injectDependencies(\Mockery::spy(SVNAuthenticationCacheInvalidator::class));

        // The project
        $project = new MockProject();
        $project->setReturnValue('usesCVS', true);
        $project->setReturnValue('usesSVN', true);
        $evt->setReturnValue('getProject', $project, array('142'));

        //Remove users from project
        $evt->setReturnValue('removeProjectMembers', true);

        $evt->setReturnValue('deleteMembershipRequestNotificationEntries', true);

        //Cleanup ProjectUGroup binding
        $evt->setReturnValue('cleanupProjectUgroupsBinding', true);

        //Cleanup FRS
        $evt->setReturnValue('cleanupProjectFRS', true);

        //Delete all trackers
        $atf = new  MockArtifactTypeFactory();
        $atf->setReturnValue('preDeleteAllProjectArtifactTypes', true);
        $evt->setReturnValue('getArtifactTypeFactory', $atf, array($project));

        // System
        $backendSystem = new MockBackendSystem();
        $backendSystem->setReturnValue('projectHomeExists', true);
        $backendSystem->setReturnValue('archiveProjectHome', true);
        $backendSystem->setReturnValue('archiveProjectFtp', true);
        $backendSystem->expectOnce('setNeedRefreshGroupCache');
        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // Wiki attachments
        $wa = new MockWikiAttachment();
        $wa->setReturnValue('deleteProjectAttachments', true);
        $wa->expectOnce('deleteProjectAttachments');
        $evt->setReturnValue('getWikiAttachment', $wa);

        // CVS
        $backendCVS = new MockBackendCVS();
        $backendCVS->setReturnValue('repositoryExists', true);
        $backendCVS->setReturnValue('archiveProjectCVS', false);
        $backendCVS->expectNever('setCVSRootListNeedUpdate');
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // SVN
        $backendSVN = new MockBackendSVN();
        $backendSVN->setReturnValue('repositoryExists', true);
        $backendSVN->setReturnValue('archiveProjectSVN', true);
        $backendSVN->expectOnce('setSVNApacheConfNeedUpdate');
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // MailingList
        $backendMailingList = new MockBackendMailingList();
        $backendMailingList->setReturnValue('deleteProjectMailingLists', true);
        $backendMailingList->expectOnce('deleteProjectMailingLists');
        $evt->setReturnValue('getBackend', $backendMailingList, array('MailingList'));

        // Aliases
        $backendAliases = new MockBackendAliases();
        $backendAliases->expectOnce('setNeedUpdateMailAliases');
        $evt->setReturnValue('getBackend', $backendAliases, array('Aliases'));

        $evt->expectNever('done');
        $evt->expectOnce('error', array("Could not archive project CVS repository"));

        $evt->setReturnValue('getEventManager', \Mockery::spy(EventManager::class));

        // Launch the event
        $this->assertFalse($evt->process());
    }

    /**
     * Project delete SVN fail
     *
     * @return Void
     */
    public function testProjectDeleteSVNFail()
    {
        $evt = new SystemEvent_PROJECT_DELETE_TestVersion();
        $evt->__construct('1', SystemEvent::TYPE_PROJECT_DELETE, SystemEvent::OWNER_ROOT, '142', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');
        $evt->injectDependencies(\Mockery::spy(SVNAuthenticationCacheInvalidator::class));

        // The project
        $project = new MockProject();
        $project->setReturnValue('usesCVS', true);
        $project->setReturnValue('usesSVN', true);
        $evt->setReturnValue('getProject', $project, array('142'));

        //Remove users from project
        $evt->setReturnValue('removeProjectMembers', true);

        $evt->setReturnValue('deleteMembershipRequestNotificationEntries', true);

        //Cleanup ProjectUGroup binding
        $evt->setReturnValue('cleanupProjectUgroupsBinding', true);

        //Cleanup FRS
        $evt->setReturnValue('cleanupProjectFRS', true);

        //Delete all trackers
        $atf = new  MockArtifactTypeFactory();
        $atf->setReturnValue('preDeleteAllProjectArtifactTypes', true);
        $evt->setReturnValue('getArtifactTypeFactory', $atf, array($project));

        // System
        $backendSystem = new MockBackendSystem();
        $backendSystem->setReturnValue('projectHomeExists', true);
        $backendSystem->setReturnValue('archiveProjectHome', true);
        $backendSystem->setReturnValue('archiveProjectFtp', true);
        $backendSystem->expectOnce('setNeedRefreshGroupCache');
        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // Wiki attachments
        $wa = new MockWikiAttachment();
        $wa->setReturnValue('deleteProjectAttachments', true);
        $wa->expectOnce('deleteProjectAttachments');
        $evt->setReturnValue('getWikiAttachment', $wa);

        // CVS
        $backendCVS = new MockBackendCVS();
        $backendCVS->setReturnValue('repositoryExists', true);
        $backendCVS->setReturnValue('archiveProjectCVS', true);
        $backendCVS->expectOnce('setCVSRootListNeedUpdate');
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // SVN
        $backendSVN = new MockBackendSVN();
        $backendSVN->setReturnValue('repositoryExists', true);
        $backendSVN->setReturnValue('archiveProjectSVN', false);
        $backendSVN->expectNever('setSVNApacheConfNeedUpdate');
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // MailingList
        $backendMailingList = new MockBackendMailingList();
        $backendMailingList->setReturnValue('deleteProjectMailingLists', true);
        $backendMailingList->expectOnce('deleteProjectMailingLists');
        $evt->setReturnValue('getBackend', $backendMailingList, array('MailingList'));

        // Aliases
        $backendAliases = new MockBackendAliases();
        $backendAliases->expectOnce('setNeedUpdateMailAliases');
        $evt->setReturnValue('getBackend', $backendAliases, array('Aliases'));

        $evt->expectNever('done');
        $evt->expectOnce('error', array("Could not archive project SVN repository"));

        $evt->setReturnValue('getEventManager', \Mockery::spy(EventManager::class));

        // Launch the event
        $this->assertFalse($evt->process());
    }

    /**
     * Project delete Mailing list fail
     *
     * @return Void
     */
    public function testProjectDeleteMailingListFail()
    {
        $evt = new SystemEvent_PROJECT_DELETE_TestVersion();
        $evt->__construct('1', SystemEvent::TYPE_PROJECT_DELETE, SystemEvent::OWNER_ROOT, '142', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');
        $evt->injectDependencies(\Mockery::spy(SVNAuthenticationCacheInvalidator::class));

        // The project
        $project = new MockProject();
        $project->setReturnValue('usesCVS', true);
        $project->setReturnValue('usesSVN', true);
        $evt->setReturnValue('getProject', $project, array('142'));

        //Remove users from project
        $evt->setReturnValue('removeProjectMembers', true);

        $evt->setReturnValue('deleteMembershipRequestNotificationEntries', true);

        //Cleanup ProjectUGroup binding
        $evt->setReturnValue('cleanupProjectUgroupsBinding', true);

        //Cleanup FRS
        $evt->setReturnValue('cleanupProjectFRS', true);

        //Delete all trackers
        $atf = new  MockArtifactTypeFactory();
        $atf->setReturnValue('preDeleteAllProjectArtifactTypes', true);
        $evt->setReturnValue('getArtifactTypeFactory', $atf, array($project));

        // System
        $backendSystem = new MockBackendSystem();
        $backendSystem->setReturnValue('projectHomeExists', true);
        $backendSystem->setReturnValue('archiveProjectHome', true);
        $backendSystem->setReturnValue('archiveProjectFtp', true);
        $backendSystem->expectOnce('setNeedRefreshGroupCache');
        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // Wiki attachments
        $wa = new MockWikiAttachment();
        $wa->setReturnValue('deleteProjectAttachments', true);
        $wa->expectOnce('deleteProjectAttachments');
        $evt->setReturnValue('getWikiAttachment', $wa);

        // CVS
        $backendCVS = new MockBackendCVS();
        $backendCVS->setReturnValue('repositoryExists', true);
        $backendCVS->setReturnValue('archiveProjectCVS', true);
        $backendCVS->expectOnce('setCVSRootListNeedUpdate');
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // SVN
        $backendSVN = new MockBackendSVN();
        $backendSVN->setReturnValue('repositoryExists', true);
        $backendSVN->setReturnValue('archiveProjectSVN', true);
        $backendSVN->expectOnce('setSVNApacheConfNeedUpdate');
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // MailingList
        $backendMailingList = new MockBackendMailingList();
        $backendMailingList->setReturnValue('deleteProjectMailingLists', false);
        $backendMailingList->expectOnce('deleteProjectMailingLists');
        $evt->setReturnValue('getBackend', $backendMailingList, array('MailingList'));

        // Aliases
        $backendAliases = new MockBackendAliases();
        $backendAliases->expectNever('setNeedUpdateMailAliases');
        $evt->setReturnValue('getBackend', $backendAliases, array('Aliases'));

        $evt->expectNever('done');
        $evt->expectOnce('error', array("Could not archive project mailing lists"));

        $evt->setReturnValue('getEventManager', \Mockery::spy(EventManager::class));

        // Launch the event
        $this->assertFalse($evt->process());
    }

    /**
     * Project delete ProjectUGroup binding fail
     *
     * @return Void
     */
    public function testProjectDeleteUgroupBindingFail()
    {
        $evt = new SystemEvent_PROJECT_DELETE_TestVersion();
        $evt->__construct('1', SystemEvent::TYPE_PROJECT_DELETE, SystemEvent::OWNER_ROOT, '142', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');
        $evt->injectDependencies(\Mockery::spy(SVNAuthenticationCacheInvalidator::class));

        // The project
        $project = new MockProject();
        $project->setReturnValue('usesCVS', true);
        $project->setReturnValue('usesSVN', true);
        $evt->setReturnValue('getProject', $project, array('142'));

        //Remove users from project
        $evt->setReturnValue('removeProjectMembers', true);

        $evt->setReturnValue('deleteMembershipRequestNotificationEntries', true);

        //Cleanup ProjectUGroup binding
        $evt->setReturnValue('cleanupProjectUgroupsBinding', false);

        //Cleanup FRS
        $evt->setReturnValue('cleanupProjectFRS', true);

        //Delete all trackers
        $atf = new  MockArtifactTypeFactory();
        $atf->setReturnValue('preDeleteAllProjectArtifactTypes', true);
        $evt->setReturnValue('getArtifactTypeFactory', $atf, array($project));

        // System
        $backendSystem = new MockBackendSystem();
        $backendSystem->setReturnValue('projectHomeExists', true);
        $backendSystem->setReturnValue('archiveProjectHome', true);
        $backendSystem->setReturnValue('archiveProjectFtp', true);
        $backendSystem->expectOnce('setNeedRefreshGroupCache');
        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // Wiki attachments
        $wa = new MockWikiAttachment();
        $wa->setReturnValue('deleteProjectAttachments', true);
        $wa->expectOnce('deleteProjectAttachments');
        $evt->setReturnValue('getWikiAttachment', $wa);

        // CVS
        $backendCVS = new MockBackendCVS();
        $backendCVS->setReturnValue('repositoryExists', true);
        $backendCVS->setReturnValue('archiveProjectCVS', true);
        $backendCVS->expectOnce('setCVSRootListNeedUpdate');
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // SVN
        $backendSVN = new MockBackendSVN();
        $backendSVN->setReturnValue('repositoryExists', true);
        $backendSVN->setReturnValue('archiveProjectSVN', true);
        $backendSVN->expectOnce('setSVNApacheConfNeedUpdate');
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // MailingList
        $backendMailingList = new MockBackendMailingList();
        $backendMailingList->setReturnValue('deleteProjectMailingLists', true);
        $backendMailingList->expectOnce('deleteProjectMailingLists');
        $evt->setReturnValue('getBackend', $backendMailingList, array('MailingList'));

        // Aliases
        $backendAliases = new MockBackendAliases();
        $backendAliases->expectOnce('setNeedUpdateMailAliases');
        $evt->setReturnValue('getBackend', $backendAliases, array('Aliases'));

        $evt->expectNever('done');
        $evt->expectOnce('error', array("Could not remove ugroups binding"));

        $evt->setReturnValue('getEventManager', \Mockery::spy(EventManager::class));

        // Launch the event
        $this->assertFalse($evt->process());
    }

    /**
     * Project delete Succeed
     *
     * @return Void
     */
    public function testProjectDeleteSucceed()
    {
        $evt = new SystemEvent_PROJECT_DELETE_TestVersion();
        $evt->__construct('1', SystemEvent::TYPE_PROJECT_DELETE, SystemEvent::OWNER_ROOT, '142', SystemEvent::PRIORITY_HIGH, SystemEvent::STATUS_RUNNING, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'], '');
        $evt->injectDependencies(\Mockery::spy(SVNAuthenticationCacheInvalidator::class));

        // The project
        $project = new MockProject();
        $project->setReturnValue('usesCVS', true);
        $project->setReturnValue('usesSVN', true);
        $evt->setReturnValue('getProject', $project, array('142'));

        //Remove users from project
        $evt->setReturnValue('removeProjectMembers', true);

        $evt->setReturnValue('deleteMembershipRequestNotificationEntries', true);

        //Cleanup ProjectUGroup binding
        $evt->setReturnValue('cleanupProjectUgroupsBinding', true);

        //Cleanup FRS
        $evt->setReturnValue('cleanupProjectFRS', true);

        //Delete all trackers
        $atf = new  MockArtifactTypeFactory();
        $atf->setReturnValue('preDeleteAllProjectArtifactTypes', true);
        $evt->setReturnValue('getArtifactTypeFactory', $atf, array($project));

        // System
        $backendSystem = new MockBackendSystem();
        $backendSystem->setReturnValue('projectHomeExists', true);
        $backendSystem->setReturnValue('archiveProjectHome', true);
        $backendSystem->setReturnValue('archiveProjectFtp', true);
        $backendSystem->expectOnce('setNeedRefreshGroupCache');
        $evt->setReturnValue('getBackend', $backendSystem, array('System'));

        // Wiki attachments
        $wa = new MockWikiAttachment();
        $wa->setReturnValue('deleteProjectAttachments', true);
        $wa->expectOnce('deleteProjectAttachments');
        $evt->setReturnValue('getWikiAttachment', $wa);

        // CVS
        $backendCVS = new MockBackendCVS();
        $backendCVS->setReturnValue('repositoryExists', true);
        $backendCVS->setReturnValue('archiveProjectCVS', true);
        $backendCVS->expectOnce('setCVSRootListNeedUpdate');
        $evt->setReturnValue('getBackend', $backendCVS, array('CVS'));

        // SVN
        $backendSVN = new MockBackendSVN();
        $backendSVN->setReturnValue('repositoryExists', true);
        $backendSVN->setReturnValue('archiveProjectSVN', true);
        $backendSVN->expectOnce('setSVNApacheConfNeedUpdate');
        $evt->setReturnValue('getBackend', $backendSVN, array('SVN'));

        // MailingList
        $backendMailingList = new MockBackendMailingList();
        $backendMailingList->setReturnValue('deleteProjectMailingLists', true);
        $backendMailingList->expectOnce('deleteProjectMailingLists');
        $evt->setReturnValue('getBackend', $backendMailingList, array('MailingList'));

        // Aliases
        $backendAliases = new MockBackendAliases();
        $backendAliases->expectOnce('setNeedUpdateMailAliases');
        $evt->setReturnValue('getBackend', $backendAliases, array('Aliases'));

        // Expect everything went OK
        $evt->expectOnce('done');
        $evt->expectNever('error');

        $evt->setReturnValue('getEventManager', \Mockery::spy(EventManager::class));

        // Launch the event
        $this->assertTrue($evt->process());
    }
}
