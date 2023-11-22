<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 */

use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use Tuleap\Notification\Notification;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;

class FileModuleMonitorFactory // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public FileModuleMonitorDao|null $dao = null;

    public function whoIsMonitoringPackageById($group_id, $package_id)
    {
        $_group_id   = (int) $group_id;
        $_package_id = (int) $package_id;

        $dao = $this->getFileModuleMonitorDao();
        $dar = $dao->whoIsMonitoringPackageByID($group_id, $package_id);
        if ($dar->isError()) {
            return;
        }

        if (! $dar->valid()) {
            return;
        }

        $data_array = [];
        while ($dar->valid()) {
            $data_array[] = $dar->current();
            $dar->next();
        }
        return $data_array;
    }

    /**
     * Get the list of users publicly monitoring a package
     *
     * @param int $packageId Id of the package
     *
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface|array
     */
    public function whoIsPubliclyMonitoringPackage($packageId)
    {
        $dao    = $this->getFileModuleMonitorDao();
        $dar    = $dao->whoIsPubliclyMonitoringPackage($packageId);
        $result = [];
        if ($dar && ! $dar->isError()) {
            $result = $dar;
        }
        return $result;
    }

    public function getFilesModuleMonitorFromDb($id)
    {
        $_id = (int) $id;
        $dao = $this->getFileModuleMonitorDao();
        $dar = $dao->searchById($_id);

        $data_array = [];
        if (! $dar->isError() && $dar->valid()) {
            while ($dar->valid()) {
                $data_array[] = $dar->current();
                $dar->next();
            }
        }
        return $data_array;
    }

    /**
     * Is the user in the list of people monitoring this package.
     *
     * @param int $filemodule_id Id of the package
     * @param PFUser    $user          The user
     * @param bool $publicly If true check if the user is monitoring publicly
     *
     * @return bool is_monitoring
     */
    public function isMonitoring($filemodule_id, PFUser $user, $publicly)
    {
        $_filemodule_id = (int) $filemodule_id;
        $dao            = $this->getFileModuleMonitorDao();
        $dar            = $dao->searchMonitoringFileByUserAndPackageId($_filemodule_id, $user, $publicly);

        if ($dar->isError()) {
            return;
        }

        if (! $dar->valid() || $dar->rowCount() < 1) {
            return false;
        } else {
            return true;
        }
    }

    private function getFileModuleMonitorDao(): FileModuleMonitorDao
    {
        if (! $this->dao) {
            $this->dao = new FileModuleMonitorDao(CodendiDataAccess::instance());
        }
        return $this->dao;
    }

    /**
     * Set package monitoring
     *
     * @param int $filemodule_id Id of the package
     * @param PFUser    $user          The user
     * @param bool $anonymous True if the user want to monitor the package anonymously
     *
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
     */
    public function setMonitor($filemodule_id, PFUser $user, $anonymous = true)
    {
        $dao = $this->getFileModuleMonitorDao();
        $res = $dao->create($filemodule_id, $user, $anonymous);
        return $res;
    }

    /**
     * Add package monitoring for a user
     *
     * @param PFUser              $user         The user
     * @param int $groupId Id of the project
     * @param int $fileModuleId Id of the package
     * @param FRSPackage        $package      Package
     * @param FRSPackageFactory $frspf        Package factory
     * @param UserHelper        $userHelper   User helper
     *
     * @return Void
     */
    public function addUserMonitoring(PFUser $user, $groupId, $fileModuleId, FRSPackage $package, FRSPackageFactory $frspf, UserHelper $userHelper)
    {
        if ($user) {
            $publicly = true;
            if ($frspf->userCanRead($groupId, $fileModuleId, $user->getId())) {
                if (! $this->isMonitoring($fileModuleId, $user, $publicly)) {
                    $anonymous = false;
                    $result    = $this->setMonitor($fileModuleId, $user, $anonymous);
                    if ($result) {
                        $historyDao = new ProjectHistoryDao();
                        $historyDao->groupAddHistory("frs_add_monitor_package", $fileModuleId . "_" . $user->getId(), $groupId);
                        $this->notifyAfterAdd($package, $user);
                        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_filemodule_monitor', 'monitoring_added', [$userHelper->getDisplayName($user->getUserName(), $user->getRealName())]));
                    } else {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_filemodule_monitor', 'insert_err'));
                    }
                } else {
                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('file_filemodule_monitor', 'already_monitoring', [$userHelper->getDisplayName($user->getUserName(), $user->getRealName())]));
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_filemodule_monitor', 'user_no_permission', [$userHelper->getDisplayName($user->getUserName(), $user->getRealName())]));
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_filemodule_monitor', 'no_user', [$userName]));
        }
    }

    /**
     * Stop the package monitoring
     *
     * @param int $filemodule_id Id of th package
     * @param PFUser    $user          The user
     * @param bool $onlyPublic If true delete only user publicly monitoring the package
     *
     * @return bool
     */
    public function stopMonitor($filemodule_id, PFUser $user, $onlyPublic = false)
    {
        $_id = (int) $filemodule_id;
        $dao = $this->getFileModuleMonitorDao();
        return $dao->delete($_id, $user, $onlyPublic);
    }

    /**
     * Stop the package monitoring for some users
     *
     * @param Array             $users        Array of users
     * @param int $groupId Id of the project
     * @param int $fileModuleId Id of the package
     * @param FRSPackage        $package      Package
     * @param UserManager       $um           User manager
     * @param UserHelper        $userHelper   User helper
     *
     * @return Void
     */
    public function stopMonitoringForUsers($users, $groupId, $fileModuleId, FRSPackage $package, UserManager $um, UserHelper $userHelper)
    {
        if ($users && ! empty($users) && is_array($users)) {
            foreach ($users as $userId) {
                $user = $um->getUserById($userId);
                if ($user) {
                    $publicly = true;
                    if ($this->isMonitoring($fileModuleId, $user, $publicly)) {
                        $this->stopMonitoringForUser($fileModuleId, $user, $groupId, $package, $userHelper);
                    } else {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_filemodule_monitor', 'not_monitoring', [$userHelper->getDisplayName($user->getUserName(), $user->getRealName())]));
                    }
                }
            }
        } else {
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('file_filemodule_monitor', 'no_delete'));
        }
    }

    /**
     * Stop only the public package monitoring for a given user
     *
     * @param int $fileModuleId Id of the package
     * @param PFUser       $user         User we want to stop its monitoring
     * @param int $groupId Id of the project
     * @param FRSPackage $package      Package
     * @param UserHelper $userHelper   User helper
     *
     * @return Void
     */
    private function stopMonitoringForUser($fileModuleId, $user, $groupId, FRSPackage $package, UserHelper $userHelper)
    {
        if ($this->stopMonitor($fileModuleId, $user, true)) {
            $historyDao = new ProjectHistoryDao();
            $historyDao->groupAddHistory("frs_stop_monitor_package", $fileModuleId . "_" . $user->getId(), $groupId);
            $this->notifyAfterDelete($package, $user);
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_filemodule_monitor', 'deleted', [$userHelper->getDisplayName($user->getUserName(), $user->getRealName())]));
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_filemodule_monitor', 'delete_error', [$userHelper->getDisplayName($user->getUserName(), $user->getRealName())]));
        }
    }

    /**
     * @return Notification
     */
    private function getNotification(FRSPackage $package, PFUser $user, $html_body, $text_body)
    {
        $subject = $GLOBALS['Language']->getText(
            'file_filemodule_monitor',
            'mail_subject',
            [ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME),
                $package->getName(),
            ]
        );

        $emails       = [$user->getEmail()];
        $service_name = 'Files';
        $goto_link    = \Tuleap\ServerHostname::HTTPSUrl() . '/file/showfiles.php?group_id=' . $package->getGroupID() .
                        '&package_id=' . $package->getPackageID();

        return new Notification($emails, $subject, $html_body, $text_body, $goto_link, $service_name);
    }

    /**
     * Notify after adding monitoring for a user
     *
     * @param FRSPackage $package Id of th package
     * @param PFUser       $user    The added user
     *
     * @return bool
     */
    public function notifyAfterAdd(FRSPackage $package, PFUser $user)
    {
        $mail_builder = new MailBuilder(
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
        $purifier     = Codendi_HTMLPurifier::instance();

        $goto_link = \Tuleap\ServerHostname::HTTPSUrl() . '/file/showfiles.php?group_id=' . urlencode($package->getGroupID()) .
            '&package_id=' . urlencode($package->getPackageID());
        $htmlBody  = $GLOBALS['Language']->getText('file_filemodule_monitor', 'add_monitor_mail');
        $htmlBody .= ' <a href="' . $purifier->purify($goto_link) . '" >' . $purifier->purify($package->getName()) . '</a>';

        $txtBody  = $GLOBALS['Language']->getText('file_filemodule_monitor', 'add_monitor_mail') . ' "' . $package->getName() . '" : ';
        $txtBody .= $goto_link;
        $txtBody .= "\n\n" . $GLOBALS['Language']->getText('file_showfiles', 'stop_monitoring') . ': ';
        $txtBody .= \Tuleap\ServerHostname::HTTPSUrl() . '/file/filemodule_monitor.php?group_id=' . urlencode($package->getGroupID()) . '&filemodule_id=' . urlencode($package->getPackageID());

        $notification = $this->getNotification($package, $user, $htmlBody, $txtBody);
        $project      = ProjectManager::instance()->getProject($package->getGroupID());

        return $mail_builder->buildAndSendEmail($project, $notification, new MailEnhancer());
    }

    /**
     * Notify after deleting monitoring for a user
     *
     * @param FRSPackage $package Id of th package
     * @param PFUser       $user    The deleted user
     *
     * @return bool
     */
    public function notifyAfterDelete(FRSPackage $package, PFUser $user)
    {
        $mail_builder = new MailBuilder(
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
        $purifier     = Codendi_HTMLPurifier::instance();

        $server_url = \Tuleap\ServerHostname::HTTPSUrl();

        $goto_link = $server_url . '/file/showfiles.php?group_id=' . urlencode($package->getGroupID()) .
            '&package_id=' . urlencode($package->getPackageID());
        $htmlBody  = $GLOBALS['Language']->getText('file_filemodule_monitor', 'delete_monitor_mail');
        $htmlBody .= ' <a href="' . $purifier->purify($goto_link) . '" >' . $purifier->purify($package->getName()) . '</a>';
        $htmlBody .= '<br /><br /><a href="' . $purifier->purify($server_url . '/file/filemodule_monitor.php?group_id=' . urlencode($package->getGroupID()) . '&filemodule_id=' . urlencode($package->getPackageID())) . '" >' .
            $GLOBALS['Language']->getText('file_showfiles', 'start_monitoring') . '</a>';

        $txtBody  = $GLOBALS['Language']->getText('file_filemodule_monitor', 'delete_monitor_mail') . ' "' . $package->getName() . '" : ';
        $txtBody .= $goto_link;
        $txtBody .= "\n\n" . $GLOBALS['Language']->getText('file_showfiles', 'start_monitoring') . ': ';
        $txtBody .= $server_url . '/file/filemodule_monitor.php?group_id=' . urlencode($package->getGroupID()) . '&filemodule_id=' . urlencode($package->getPackageID());

        $notification = $this->getNotification($package, $user, $htmlBody, $txtBody);
        $project      = ProjectManager::instance()->getProject($package->getGroupID());

        return $mail_builder->buildAndSendEmail($project, $notification, new MailEnhancer());
    }

    /**
     * Display the list of people monitoring the package with the delete form
     *
     * @param int $fileModuleId Id of the package
     * @param UserManager $um           UserManager instance
     * @param UserHelper  $userHelper   UserHelper instance
     *
     * @return String
     */
    public function getMonitoringListHTML($fileModuleId, $um, $userHelper)
    {
        $editContent = '<h3>' . $GLOBALS['Language']->getText('file_filemodule_monitor', 'monitoring_people_title') . '</h3>';
        $list        = $this->whoIsPubliclyMonitoringPackage($fileModuleId);
        $totalCount  = count($this->getFilesModuleMonitorFromDb($fileModuleId));
        $count       = $totalCount - count($this->whoIsPubliclyMonitoringPackage($fileModuleId));
        if (count($list) === 0) {
            $editContent .= $GLOBALS['Language']->getText('file_filemodule_monitor', 'users_monitor', $count) . '<br />';
            $editContent .= $GLOBALS['Language']->getText('file_filemodule_monitor', 'no_list');
        } else {
            $purifier     = Codendi_HTMLPurifier::instance();
            $editContent .= '<form id="filemodule_monitor_form_delete" method="post" >';
            $editContent .= '<input type="hidden" name="action" value="delete_monitoring">';
            $editContent .= html_build_list_table_top([$GLOBALS['Language']->getText('file_filemodule_monitor', 'user'), $GLOBALS['Language']->getText('global', 'delete') . '?'], false, false, false);
            $rowBgColor   = 0;
            foreach ($list as $entry) {
                $user = $um->getUserById($entry['user_id']);
                if ($user !== null) {
                    $editContent .= '<tr class="' . html_get_alt_row_color(++$rowBgColor) . '"><td>' . $purifier->purify($userHelper->getDisplayName($user->getUserName(), $user->getRealName())) . '</td><td><input type="checkbox" name="delete_user[]" value="' . $purifier->purify($entry['user_id']) . '" /></td></tr>';
                }
            }
            $editContent .= '<tr class="' . html_get_alt_row_color(++$rowBgColor) . '"><td>' . $GLOBALS['Language']->getText('file_filemodule_monitor', 'users_monitor', $count) . '</td><td></td></tr>';
            $editContent .= '<tr class="' . html_get_alt_row_color(++$rowBgColor) . '"><td>' . $GLOBALS['Language']->getText('global', 'total') . ': ' . $totalCount . '</td><td><input id="filemodule_monitor_submit" type="submit" value="' . $GLOBALS['Language']->getText('global', 'delete') . '" /></td></tr>';
            $editContent .= '</table>';
            $editContent .= '</form>';
        }
        return $editContent;
    }

    /**
     * Display the form to add a user to the monitoring people by the admin
     *
     * @param int $fileModuleId Id of the package
     *
     * @return String
     */
    public function getAddMonitoringForm($fileModuleId)
    {
        $purifier     = Codendi_HTMLPurifier::instance();
        $editContent  = '<form id="filemodule_monitor_form_add" method="post" >';
        $editContent .= '<input type="hidden" name="action" value="add_monitoring">';
        $editContent .= '<input type="hidden" name="package_id" value="' . $purifier->purify($fileModuleId) . '">';
        $editContent .= '<h3>' . $GLOBALS['Language']->getText('file_filemodule_monitor', 'add_users') . '</h3>';
        $editContent .= '<br /><textarea name="listeners_to_add" value="" id="listeners_to_add" rows="2" cols="50"></textarea>';
        $autocomplete = "new UserAutoCompleter('listeners_to_add', '" . util_get_dir_image_theme() . "', true);";
        $GLOBALS['Response']->includeFooterJavascriptSnippet($autocomplete);
        $editContent .= '<br /><input id="filemodule_monitor_submit" type="submit" value="' . $GLOBALS['Language']->getText('global', 'add') . '" />';
        $editContent .= '</form>';
        return $editContent;
    }

    /**
     * Display the form to manage user's self monitoring of the package
     *
     * @param PFUser    $currentUser  Current user
     * @param int $fileModuleId Id of the package
     *
     * @return String
     */
    public function getSelfMonitoringForm($currentUser, $fileModuleId)
    {
        $purifier = Codendi_HTMLPurifier::instance();

        $html                  = '<h3>' . $GLOBALS['Language']->getText('file_filemodule_monitor', 'my_monitoring') . '</h3>';
        $html                 .= '<form id="filemodule_monitor_form" method="post" >';
        $html                 .= '<input type="hidden" name="action" value="monitor_package">';
        $html                 .= '<input type="hidden" id="filemodule_id" name="filemodule_id" value="' . $purifier->purify($fileModuleId) . '" />';
        $notMonitring          = '';
        $monitoringPublicly    = '';
        $monitoringAnonymously = '';
        if ($this->isMonitoring($fileModuleId, $currentUser, false)) {
            $publicly = true;
            if ($this->isMonitoring($fileModuleId, $currentUser, $publicly)) {
                $monitoringPublicly = 'checked="checked"';
            } else {
                $monitoringAnonymously = 'checked="checked"';
            }
        } else {
            $notMonitring = 'checked="checked"';
        }
        $html .= '<table>';
        $html .= '<tr><td><input type="radio" id="stop_frs_monitoring" name="frs_monitoring" value="stop_monitoring" ' . $notMonitring . '/></td>';
        $html .= '<td>' . $GLOBALS['Language']->getText('file_showfiles', 'stop_monitoring') . '</td></tr>';
        $html .= '<tr><td><input type="radio" id="anonymous_frs_monitoring" name="frs_monitoring" value="anonymous_monitoring" ' . $monitoringAnonymously . '/></td>';
        $html .= '<td>' . $GLOBALS['Language']->getText('file_filemodule_monitor', 'anonymous') . '</td></tr>';
        $html .= '<tr><td><input type="radio" id="public_frs_monitoring" name="frs_monitoring" value="public_monitoring" ' . $monitoringPublicly . '/></td>';
        $html .= '<td>' . $GLOBALS['Language']->getText('file_showfiles', 'start_monitoring') . ' (' . $GLOBALS['Language']->getText('file_filemodule_monitor', 'public') . ')</td></tr>';
        $html .= '<tr><td></td><td><input type="submit" value="' . $GLOBALS['Language']->getText('global', 'btn_apply') . '" /></td></tr>';
        $html .= '</table>';
        $html .= '</form>';
        return $html;
    }

    /**
     * Display the HTML of the monitoring UI
     *
     * @param PFUser        $currentUser  Current user
     * @param int $groupId Id of the project
     * @param int $fileModuleId Id of the package
     * @param UserManager $um           UserManager instance
     * @param UserHelper  $userHelper   UserHelper instance
     *
     * @return String
     */
    public function getMonitoringHTML($currentUser, $groupId, $fileModuleId, $um, $userHelper)
    {
        $purifier = Codendi_HTMLPurifier::instance();

        $frspf   = new FRSPackageFactory();
        $package = $frspf->getFRSPackageFromDb($fileModuleId);
        $html    = '<h2>' . $GLOBALS['Language']->getText('file_admin_editpackagepermissions', 'p') . ' <a href="showfiles.php?group_id=' . urlencode($groupId) . '" >' . $purifier->purify($package->getName()) . '</a></h2>';
        $html   .= $this->getSelfMonitoringForm($currentUser, $fileModuleId);
        if ($frspf->userCanAdmin($currentUser, $groupId)) {
            $html .= $this->getMonitoringListHTML($fileModuleId, $um, $userHelper);
            $html .= $this->getAddMonitoringForm($fileModuleId);
        }
        return $html;
    }

    /**
     * Process the self monitoring request
     *
     * @param HTTPRequest $request      HTTP request
     * @param PFUser        $currentUser  Current user
     * @param int $groupId Id of the project
     * @param int $fileModuleId Id of the package
     *
     * @return String
     */
    public function processSelfMonitoringAction(HTTPRequest $request, $currentUser, $groupId, $fileModuleId)
    {
        $anonymous     = true;
        $performAction = false;
        if ($request->get('action') === 'monitor_package' && $request->isPost()) {
            if ($request->valid(new Valid_WhiteList('frs_monitoring', ['stop_monitoring', 'anonymous_monitoring', 'public_monitoring']))) {
                $action = $request->get('frs_monitoring');
                switch ($action) {
                    case 'stop_monitoring':
                        $performAction = $this->stopMonitorActionListener($currentUser, $fileModuleId);
                        break;
                    case 'public_monitoring':
                        $anonymous = false;
                        // Fall-through seems wanted here
                    case 'anonymous_monitoring':
                        $performAction = $this->anonymousMonitoringActionListener($currentUser, $fileModuleId, $anonymous, $groupId);
                        break;
                    default:
                        break;
                }
                if ($performAction) {
                    $GLOBALS['Response']->redirect($request->getFromServer('REQUEST_URI'));
                }
            }
        }
    }

    /**
     * Listening to stop self monitoring action
     *
     * @param PFUser    $currentUser  Current user
     * @param int $fileModuleId Id of the package
     *
     * @return bool
     */
    private function stopMonitorActionListener($currentUser, $fileModuleId)
    {
        if ($this->isMonitoring($fileModuleId, $currentUser, false)) {
            $result = $this->stopMonitor($fileModuleId, $currentUser);
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_filemodule_monitor', 'monitor_turned_off'));
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_filemodule_monitor', 'no_emails'));
            return true;
        } else {
            return false;
        }
    }

    /**
     * Listening to anonymous monitoring action
     *
     * @param PFUser    $currentUser  Current user
     * @param int $fileModuleId Id of the package
     * @param bool $anonymous Anonymous monitoring flag
     * @param int $groupId Id of the project
     *
     * @return bool
     */
    private function anonymousMonitoringActionListener($currentUser, $fileModuleId, $anonymous, $groupId)
    {
        $performAction = false;
        if ($anonymous && (! $this->isMonitoring($fileModuleId, $currentUser, false) || $this->isMonitoring($fileModuleId, $currentUser, $anonymous))) {
            $performAction = true;
        } elseif (! $anonymous && ! $this->isMonitoring($fileModuleId, $currentUser, ! $anonymous)) {
            $performAction = true;
            $historyDao    = new ProjectHistoryDao();
            $historyDao->groupAddHistory("frs_self_add_monitor_package", $fileModuleId, $groupId);
        }
        if ($performAction) {
            $this->stopMonitor($fileModuleId, $currentUser);
            $result = $this->setMonitor($fileModuleId, $currentUser, $anonymous);
            if (! $result) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_filemodule_monitor', 'insert_err'));
            } else {
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_filemodule_monitor', 'p_monitored'));
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_filemodule_monitor', 'now_emails'));
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_filemodule_monitor', 'turn_monitor_off'), CODENDI_PURIFIER_LIGHT);
            }
        }
        return $performAction;
    }

    /**
     * Process the monitoring request
     *
     * @param HTTPRequest $request      HTTP request
     * @param PFUser        $currentUser  Current user
     * @param int $groupId Id of the project
     * @param int $fileModuleId Id of the package
     * @param UserManager $um           UserManager instance
     * @param UserHelper  $userHelper   UserHelper instance
     *
     * @return String
     */
    public function processEditMonitoringAction($request, $currentUser, $groupId, $fileModuleId, $um, $userHelper)
    {
        $frspf   = new FRSPackageFactory();
        $package = $frspf->getFRSPackageFromDb($fileModuleId);

        if ($frspf->userCanAdmin($currentUser, $groupId)) {
            if ($request->valid(new Valid_WhiteList('action', ['add_monitoring', 'delete_monitoring'])) && $request->isPost()) {
                $action = $request->get('action');
                switch ($action) {
                    case 'add_monitoring':
                        $users = array_map('trim', preg_split('/[,;]/', $request->get('listeners_to_add')));
                        foreach ($users as $userName) {
                            if (! empty($userName)) {
                                $user = $um->findUser($userName);
                                if ($user !== null) {
                                    $this->addUserMonitoring($user, $groupId, $fileModuleId, $package, $frspf, $userHelper);
                                }
                            }
                        }
                        break;
                    case 'delete_monitoring':
                        $users = $request->get('delete_user');
                        $this->stopMonitoringForUsers($users, $groupId, $fileModuleId, $package, $um, $userHelper);
                        break;
                    default:
                        break;
                }
            }
        }
    }

    /**
     * Process the monitoring request
     *
     * @param HTTPRequest $request      HTTP request
     * @param PFUser        $currentUser  Current user
     * @param int $groupId Id of the project
     * @param int $fileModuleId Id of the package
     * @param UserManager $um           UserManager instance
     * @param UserHelper  $userHelper   UserHelper instance
     *
     * @return String
     */
    public function processMonitoringActions($request, $currentUser, $groupId, $fileModuleId, $um, $userHelper)
    {
        $this->processSelfMonitoringAction($request, $currentUser, $groupId, $fileModuleId);
        $this->processEditMonitoringAction($request, $currentUser, $groupId, $fileModuleId, $um, $userHelper);
    }
}
