<?php
/**
 * Copyright (c) Enalean 2017 - Present. All rights reserved
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
 */

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;
use Tuleap\FRS\FRSReleasePaginatedCollection;
use Tuleap\FRS\UploadedLinkDeletor;
use Tuleap\FRS\UploadedLinksDao;
use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use Tuleap\Notification\Notification;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;

class FRSReleaseFactory // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    // Kept for legacy
    public $STATUS_ACTIVE  = FRSRelease::STATUS_ACTIVE;
    public $STATUS_DELETED = FRSRelease::STATUS_DELETED;
    public $STATUS_HIDDEN  = FRSRelease::STATUS_HIDDEN;
    private static $instance;
    private ?FRSPackageFactory $package_factory = null;

    public function __construct()
    {
    }

    public static function instance()
    {
        if (empty(self::$instance)) {
            self::$instance = new FRSReleaseFactory();
        }
        return self::$instance;
    }

    public static function setInstance(FRSReleaseFactory $instance)
    {
        self::$instance = $instance;
    }

    public static function clearInstance()
    {
        self::$instance = null;
    }

    public function getFRSReleaseFromArray(&$array)
    {
        $frs_release = new FRSRelease($array);
        return $frs_release;
    }

    /**
     * Get one or more releases from the database
     *
     * $extraFlags allow to define if you want to include deleted releases into
     * the search (thanks to FRSReleaseDao::INCLUDE_DELETED constant)
     *
     * @param $release_id
     * @param $group_id
     * @param $package_id
     * @param $extraFlags
         *
         * @return FRSRelease|null
     */
    public function getFRSReleaseFromDb($release_id, $group_id = null, $package_id = null, $extraFlags = 0)
    {
        $_id = (int) $release_id;
        $dao = $this->getFRSReleaseDao();
        if ($group_id && $package_id) {
            $_group_id   = (int) $group_id;
            $_package_id = (int) $package_id;
            $dar         = $dao->searchByGroupPackageReleaseID($_id, $_group_id, $package_id, $extraFlags);
        } elseif ($group_id) {
            $_group_id = (int) $group_id;
            $dar       = $dao->searchInGroupById($_id, $_group_id, $extraFlags);
        } else {
            $dar = $dao->searchById($_id, $extraFlags);
        }

        if ($dar->isError()) {
            return null;
        }

        if (! $dar->valid()) {
            return null;
        }

        $data_array = $dar->current();

        return (self::getFRSReleaseFromArray($data_array));
    }

    /**
     * @return FRSRelease[]
     */
    public function getFRSReleasesFromDb($package_id)
    {
        $dao = $this->getFRSReleaseDao();

        $releases =  [];
        foreach ($dao->searchByPackageId($package_id) as $data_array) {
            $releases[] = $this->getFRSReleaseFromArray($data_array);
        }

        return $releases;
    }

    /**
     * @return FRSRelease[]
     */
    public function getActiveFRSReleases($package_id, $group_id)
    {
        $dao  = $this->getFRSReleaseDao();
        $dar  = $dao->searchActiveReleasesByPackageId($package_id, $this->STATUS_ACTIVE);
        $user = UserManager::instance()->getCurrentUser();

        return $this->instantiateActivePackagesFromDar($package_id, $group_id, $dar, $user);
    }

    /**
     * @return FRSRelease[]
     */
    private function instantiateActivePackagesFromDar($package_id, $group_id, LegacyDataAccessResultInterface $dar, PFUser $user)
    {
        $releases = [];
        foreach ($dar as $data_array) {
            if ($this->userCanRead($group_id, $package_id, $data_array['release_id'], $user->getID())) {
                $releases[] = $this->getFRSReleaseFromArray($data_array);
            }
        }

        return $releases;
    }

    /**
     * @return FRSReleasePaginatedCollection
     */
    public function getPaginatedActiveFRSReleasesForUser(FRSPackage $package, PFUser $user, $limit, $offset)
    {
        $dao        = $this->getFRSReleaseDao();
        $dar        = $dao->searchPaginatedActiveReleasesByPackageId($package->getPackageID(), $limit, $offset);
        $total_size = $dao->foundRows();

        return new FRSReleasePaginatedCollection(
            $this->instantiateActivePackagesFromDar($package->getPackageID(), $package->getGroupID(), $dar, $user),
            $total_size
        );
    }

    /**
     * Returns the list of releases for a given proejct
     *
     * @param int $group_id
     * @param int $package_id
     *
     * @return Array
     */
    public function getFRSReleasesInfoListFromDb($group_id, $package_id = null)
    {
        $_id = (int) $group_id;
        $dao = $this->getFRSReleaseDao();
        if ($package_id) {
            $_package_id = (int) $package_id;
            $dar         = $dao->searchByGroupPackageID($_id, $_package_id);
        } else {
            $dar = $dao->searchByGroupPackageID($_id);
        }

        if ($dar && ! $dar->isError()) {
            $releases =  [];
            foreach ($dar as $row) {
                $releases[] = $row;
            }
            return $releases;
        }
        return;
    }

    public function isActiveReleases($package_id)
    {
        $_id = (int) $package_id;
        $dao = $this->getFRSReleaseDao();
        $dar = $dao->searchActiveReleasesByPackageId($_id, $this->STATUS_ACTIVE);

        if ($dar->isError()) {
            return;
        }

        return $dar->valid();
    }

    /**
     * @return int|null
     */
    public function getReleaseIdByName($release_name, $package_id)
    {
        $_id = (int) $package_id;
        $dao = $this->getFRSReleaseDao();
        $dar = $dao->searchReleaseByName($release_name, $_id);

        if ($dar->isError()) {
            return;
        }

        if (! $dar->valid()) {
            return;
        } else {
            $res = $dar->current();
            return $res['release_id'];
        }
    }

    /**
     * Determine if a release has already the name $release_name in the package $package_id
     *
     * @return bool true if there is already a release named $release_name in the package package_id, false otherwise
     */
    public function isReleaseNameExist($release_name, $package_id)
    {
        $release_exists = $this->getReleaseIdByName($release_name, $package_id);
        return ($release_exists && count($release_exists) >= 1);
    }


    public $dao;

    private function getFRSReleaseDao(): FRSReleaseDao
    {
        if (! $this->dao) {
            $this->dao =  new FRSReleaseDao(CodendiDataAccess::instance(), $this->STATUS_DELETED);
        }
        return $this->dao;
    }

    public function update($data_array)
    {
        $dao =  $this->getFRSReleaseDao();
        if ($dao->updateFromArray($data_array)) {
            $release = $this->getFRSReleaseFromDb($data_array['release_id']);
            $this->getEventManager()->processEvent(
                'frs_update_release',
                ['group_id' => $release->getGroupID(),
                    'item_id'    => $data_array['release_id'],
                ]
            );
            return true;
        }
        return false;
    }

    public function create($data_array)
    {
        $dao = $this->getFRSReleaseDao();
        if ($id = $dao->createFromArray($data_array)) {
            $release = $this->getFRSReleaseFromDb($id);
            $this->getEventManager()->processEvent(
                'frs_create_release',
                ['group_id' => $release->getGroupID(),
                    'item_id'    => $id,
                ]
            );
            return $id;
        }
        return false;
    }

    private function delete($release_id): void
    {
        $_id     = (int) $release_id;
        $release = $this->getFRSReleaseFromDb($_id);
        $dao     = $this->getFRSReleaseDao();
        if ($dao->delete($_id, $this->STATUS_DELETED)) {
            $this->getEventManager()->processEvent(
                'frs_delete_release',
                ['group_id' => $release->getGroupID(),
                    'item_id' => $_id,
                ]
            );
        }
    }

    /**
     * Physically delete a release from the download server and database
     * First, make sure the release is theirs
     * Second, delete all its files from the db
     * Third, delete the release itself from the deb
     * Fourth, put it into the delete_files to be removed from the download server
     * return false if release not deleted, true otherwise
     *
     * @param int $group_id
     * @param int $release_id
     *
     * @return bool
     */
    public function delete_release($group_id, $release_id) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $release = $this->getFRSReleaseFromDb($release_id, $group_id);

        if (! $release) {
            //release not found for this project
            return false;
        } else {
            //delete all corresponding files from the database
            $res   = $release->getFiles();
            $frsff = $this->_getFRSFileFactory();
            foreach ($res as $file) {
                $frsff->delete_file($group_id, $file->getFileID());
            }

            $uploaded_link_deletor = new UploadedLinkDeletor(new UploadedLinksDao(), FRSLog::instance());
            $uploaded_link_deletor->deleteByRelease($release, UserManager::instance()->getCurrentUser());

            //delete the release from the database
            $this->delete($release_id);
            return true;
        }
    }

    /**
     * Delete all FRS releases and files of given project
     *
     * @param int $groupId Project ID
     *
     * @return bool
     */
    public function deleteProjectReleases($groupId)
    {
        $deleteState = true;
        $resReleases = $this->getFRSReleasesInfoListFromDb($groupId);
        if (! empty($resReleases)) {
            foreach ($resReleases as $release) {
                if (! $this->delete_release($groupId, $release['release_id'])) {
                    $deleteState = false;
                }
            }
        }
        return $deleteState;
    }

    /**
     * Test is user can administrate FRS service of given project
     *
     * @param PFUser    $user    User to test
     * @param int $project_id Project
     *
     * @return bool
     */
    public function userCanAdmin($user, $project_id)
    {
        return $this->_getFRSPackageFactory()->userCanAdmin($user, $project_id);
    }

    /**
     * @return bool
     */
    public function userCanRead($group_id, $package_id, $release_id, $user_id = false)
    {
        $um = $this->getUserManager();
        if (! $user_id) {
            $user = $um->getCurrentUser();
        } else {
            $user = $um->getUserById($user_id);
        }

        if ($user === null) {
            return false;
        }

        if ($this->userCanAdmin($user, $group_id)) {
            return true;
        }

        $project = ProjectManager::instance()->getProject($group_id);
        if ($project === null) {
            return false;
        }

        $project_access_checker = new ProjectAccessChecker(
            new RestrictedUserCanAccessProjectVerifier(),
            EventManager::instance()
        );

        try {
            $project_access_checker->checkUserCanAccessProject($user, $project);
        } catch (Project_AccessException $exception) {
            return false;
        }

        $pm = $this->getPermissionsManager();
        if ($pm->isPermissionExist($release_id, FRSRelease::PERM_READ)) {
            return $pm->userHasPermission(
                $release_id,
                FRSRelease::PERM_READ,
                $user->getUgroups($project->getID(), [])
            );
        }

        $frspf = $this->_getFRSPackageFactory();
        return $frspf->userCanRead($project->getID(), $package_id, $user->getId());
    }

    /**
     * Return true if user has Update permission on this release
     *
     * @param int $group_id The project this release is in
     * @param int $release_id The ID of the release to update
     * @param int $user_id If not given or false, take the current user
     *
     * @return bool true if user can update the release $release_id, false otherwise
     */
    public function userCanUpdate($group_id, $release_id, $user_id = false)
    {
        return $this->userCanCreate($group_id, $user_id);
    }

    /**
     * Returns true if user has permissions to Create releases
     *
     * NOTE : At this time, there is no difference between creation and update, but in the future, permissions could be added
     * For the moment, only super admin, project admin (A) and file admin (R2) can create releases
     *
     * @param int $group_id The project ID this release is in
     * @param int $user_id The ID of the user. If not given or false, take the current user
     *
     * @return bool true if the user has permission to create releases, false otherwise
     */
    public function userCanCreate($group_id, $user_id = false)
    {
        $um = $this->getUserManager();
        if (! $user_id) {
            $user = $um->getCurrentUser();
        } else {
            $user = $um->getUserById($user_id);
        }
        return $this->userCanAdmin($user, $group_id);
    }

    /**
     * Set default permission on given release
     *
     * By default, release inherits its permissions from the parent package.
     * If no permission is set "explicitly" to package, release should be set to default one
     *
     * @param FRSRelease $release Release on which to apply permissions
     *
     * @return bool
     */
    public function setDefaultPermissions(FRSRelease $release)
    {
        $pm = $this->getPermissionsManager();
        // Reset permissions for this release, before setting the new ones
        if ($pm->clearPermission(FRSRelease::PERM_READ, $release->getReleaseID())) {
            $dar = $pm->getAuthorizedUgroups($release->getPackageID(), FRSPackage::PERM_READ, false);
            if ($dar && ! $dar->isError() && $dar->rowCount() > 0) {
                foreach ($dar as $row) {
                    // Set new permissions
                    $pm->addPermission(FRSRelease::PERM_READ, $release->getReleaseID(), $row['ugroup_id']);
                }
                permission_add_history($release->getGroupID(), FRSRelease::PERM_READ, $release->getReleaseID());
                return true;
            }
        }
        return false;
    }

    /**
     * Send email notification to people monitoring the package the release belongs to
     *
     * @param FRSRelease $release Release in which the file is published
     *
     * @return int The number of people notified. False in case of error.
     */
    public function emailNotification(FRSRelease $release)
    {
        $fmmf         = new FileModuleMonitorFactory();
        $result       = $fmmf->whoIsMonitoringPackageById($release->getGroupID(), $release->getPackageID());
        $user_manager = $this->getUserManager();

        if ($result && count($result) > 0) {
            $package = $this->_getFRSPackageFactory()->getFRSPackageFromDb($release->getPackageID());

            // To
            $array_emails =  [];
            foreach ($result as $res) {
                $user           = $user_manager->getUserById($res['user_id']);
                $user_can_read  = $this->userCanRead(
                    $release->getGroupID(),
                    $release->getPackageID(),
                    $release->getReleaseID(),
                    $user->getID()
                );
                $user_can_admin = $this->userCanAdmin($user, $release->getGroupID());
                if ($user_can_admin || ($user_can_read && $release->isActive())) {
                    $array_emails[] = $res['email'];
                }
            }

            $notification  = $this->getNotification($release, $package, $array_emails);
            $is_email_sent = $this->sendEmail($release, $notification);

            if ($is_email_sent) {
                return count($result);
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * @return Notification
     */
    private function getNotification(FRSRelease $release, FRSPackage $package, array $array_emails)
    {
        $subject = ' ' . $GLOBALS['Language']->getText(
            'file_admin_editreleases',
            'file_rel_notice_subject',
            [ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME), $release->getProject()->getPublicName(), $package->getName()]
        );

        $body_text    = $this->getEmailBody($release, $package);
        $body_html    = '';
        $service_name = 'Files';
        $goto_link    = \Tuleap\ServerHostname::HTTPSUrl() . '/goto?key=release&val=' . $release->getReleaseID() .
                        '&group_id=' . $release->getProject()->getID();

        return new Notification(
            $array_emails,
            $subject,
            $body_html,
            $body_text,
            $goto_link,
            $service_name
        );
    }

    /**
     * @return bool
     */
    private function sendEmail(FRSRelease $release, Notification $notification)
    {
        $builder = new MailBuilder(
            TemplateRendererFactory::build(),
            new MailFilter(
                UserManager::instance(),
                new ProjectAccessChecker(
                    new RestrictedUserCanAccessProjectVerifier(),
                    EventManager::instance()
                ),
                new MailLogger()
            )
        );

        return $builder->buildAndSendEmail($release->getProject(), $notification, new MailEnhancer());
    }

    private function getEmailBody(FRSRelease $release, FRSPackage $package)
    {
        $server_url = \Tuleap\ServerHostname::HTTPSUrl();

        $fileUrl  = $server_url . "/file/showfiles.php?group_id=" . $package->getGroupID() . "&release_id=" . $release->getReleaseID();
        $notifUrl = $server_url . "/file/filemodule_monitor.php?filemodule_id=" . $package->getPackageID() . "&group_id=" . $package->getGroupID();

        $body = $GLOBALS['Language']->getText('file_admin_editreleases', 'download_explain_modified_package', [$release->getProject()->getPublicName(), $package->getName(), $release->getName(), $fileUrl]);

        if ($release->getNotes() != '') {
            $body .= $GLOBALS['Language']->getText('file_admin_editreleases', 'file_rel_notice_notes', [$release->getNotes()]);
        }
        if ($release->getChanges() != '') {
            $body .= $GLOBALS['Language']->getText('file_admin_editreleases', 'file_rel_notice_changes', [$release->getChanges()]);
        }

        $body .= $GLOBALS['Language']->getText('file_admin_editreleases', 'download_explain', [$notifUrl]);

        return $body;
    }

    /**
     * Returns an instance of EventManager
     *
     * @return EventManager
     */
    public function getEventManager()
    {
         $em = EventManager::instance();
         FRSLog::instance();
         return $em;
    }

    /**
     * Return an instance of PermissionsManager
     *
     * @return PermissionsManager
     */
    public function getPermissionsManager()
    {
        return PermissionsManager::instance();
    }

    /**
     * @return UserManager
     */
    public function getUserManager()
    {
        return UserManager::instance();
    }

    /**
     * Get a Package Factory
     *
     * @return FRSPackageFactory
     */
    public function _getFRSPackageFactory() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (empty($this->package_factory)) {
            $this->package_factory = new FRSPackageFactory();
        }
        return $this->package_factory;
    }

    /**
     * Get a File Factory
     *
     * @return FRSFileFactory
     */
    public function _getFRSFileFactory() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        if (empty($this->file_factory)) {
            $this->file_factory = new FRSFileFactory();
        }
        return $this->file_factory;
    }
}
