<?php
/**
 * Copyright (c) Enalean 2018 - Present. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 *
 *
 */

use Tuleap\SVNCore\SVNAuthenticationCacheInvalidator;

/**
* System Event classes
*
*/
class SystemEvent_PROJECT_DELETE extends SystemEvent
{
    /**
     * @var SVNAuthenticationCacheInvalidator
     */
    private $svn_authentication_cache_invalidator;

    public function injectDependencies(
        SVNAuthenticationCacheInvalidator $svn_authentication_cache_invalidator,
    ) {
        $this->svn_authentication_cache_invalidator = $svn_authentication_cache_invalidator;
    }

    /**
     * Verbalize the parameters so they are readable and much user friendly in
     * notifications
     *
     * @param bool $with_link true if you want links to entities. The returned
     * string will be html instead of plain/text
     *
     * @return string
     */
    public function verbalizeParameters($with_link)
    {
        $txt  = '';
        $txt .= 'project: ' . $this->verbalizeProjectId($this->getIdFromParam($this->parameters), $with_link);
        return $txt;
    }

    /**
     * Process stored event
     *
     * @return bool
     */
    public function process()
    {
        // Check parameters
        $groupId = $this->getIdFromParam($this->parameters);

        $deleteState = true;

        if ($project = $this->getProject($groupId)) {
            if (! $this->removeProjectMembers($project)) {
                $this->error("Could not remove project users");
                $deleteState = false;
            }

            if (! $this->deleteMembershipRequestNotificationEntries($groupId)) {
                $this->error("Could not remove membership request notification ugroups or message");
                $deleteState = false;
            }

            if (! $this->cleanupProjectUgroupsBinding($groupId)) {
                $this->error("Could not remove ugroups binding");
                $deleteState = false;
            }

            if (! $this->cleanupProjectFRS($groupId)) {
                $this->error("Could not remove FRS items");
                $deleteState = false;
            }

            // Mark all project trackers as deleted
            $atf = $this->getArtifactTypeFactory($project);
            if (! $atf->preDeleteAllProjectArtifactTypes()) {
                $this->error("Could not mark all trackers as deleted");
                    $deleteState = false;
            }

            if (ForgeConfig::areUnixGroupsAvailableOnSystem()) {
                $backendSystem = $this->getBackend('System');
                if ($backendSystem->projectHomeExists($project)) {
                    if (! $backendSystem->archiveProjectHome($groupId)) {
                        $this->error("Could not archive project home");
                        $deleteState = false;
                    } else {
                        // Need to update system group cache
                        $backendSystem->setNeedRefreshGroupCache();
                    }
                }

                // Archive public ftp
                if (! $backendSystem->archiveProjectFtp($groupId)) {
                    $this->error("Could not archive project public ftp");
                    $deleteState = false;
                }
            }

            // Mark Wiki attachments as deleted
            $wa = $this->getWikiAttachment($groupId);
            if (! $wa->deleteProjectAttachments()) {
                $this->error("Could not mark all wiki attachments as deleted");
                $deleteState = false;
            }

            $backendSVN = $this->getBackend('SVN');
            if ($backendSVN->repositoryExists($project)) {
                if (! $backendSVN->archiveProjectSVN($groupId)) {
                    $this->error("Could not archive project SVN repository");
                    $deleteState = false;
                } else {
                    $backendSVN->setSVNApacheConfNeedUpdate();
                }
            }
            $this->svn_authentication_cache_invalidator->invalidateProjectCache($project);

            if ($deleteState) {
                $this->done();
            }
        }
        return $deleteState;
    }

     /**
     * Remove all users from a given project.
     *
     * @param Project $project Project to be deleted
     *
     * @return bool
     */
    protected function removeProjectMembers($project)
    {
        $pm = $this->getProjectManager();
        return $pm->removeProjectMembers($project);
    }

     /**
     * Deletes ugroups assigned to recieve membership request notification
     * And the message set from a given project.
     *
     * @param int $groupId Id of the project to be deleted
     *
     * @return bool
     */
    protected function deleteMembershipRequestNotificationEntries($groupId)
    {
        $pm = $this->getProjectManager();
        return $pm->deleteMembershipRequestNotificationEntries($groupId);
    }

    /**
     * Remove Files, releases and packages for a given project.
     *
     * @param int $groupId Id of the project to be deleted
     *
     * @return bool
     */
    protected function cleanupProjectFRS($groupId)
    {
        $frsff = $this->getFRSFileFactory();
        return $frsff->deleteProjectFRS($groupId, $this->getBackend('System'));
    }

    /**
     * Returns a ArtifactTypeFactory
     *
     * @param Project $project Project to be deleted
     *
     * @return ArtifactTypeFactory
     */
    public function getArtifactTypeFactory($project)
    {
        return new ArtifactTypeFactory($project);
    }

    /**
     * Wrapper for getFRSFileFactory
     *
     * @return FRSFileFactory
     */
    protected function getFRSFileFactory()
    {
        return new FRSFileFactory();
    }

    /**
     * Wrapper for tests
     *
     * @param int $groupId Id of the deleted project
     *
     * @return WikiAttachment
     */
    protected function getWikiAttachment($groupId)
    {
        return new WikiAttachment($groupId);
    }

    /**
     * Wrapper for ProjectManager
     *
     * @return ProjectManager
     */
    protected function getProjectManager()
    {
        return ProjectManager::instance();
    }

    /**
     * Remove all binding to user groups from a the given user group.
     *
     * @param int $groupId Id of the deleted project
     *
     * @return bool
     */
    protected function cleanupProjectUgroupsBinding($groupId)
    {
        $ugroupUserDao = new UGroupUserDao();
        $ugroupManager = new UGroupManager(new UGroupDao());
        $uGroupBinding = new UGroupBinding($ugroupUserDao, $ugroupManager);
        return $uGroupBinding->removeProjectUGroupsBinding($groupId);
    }
}
