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
use Tuleap\User\Avatar\AvatarHashDao;
use Tuleap\User\Avatar\ComputeAvatarHash;
use Tuleap\User\Avatar\UserAvatarUrlProvider;

class FileModuleMonitorFactory // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public ?FileModuleMonitorDao $dao = null;

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

    public function addUserMonitoring(PFUser $user, FRSPackage $package, FRSPackageFactory $frspf, UserHelper $userHelper): void
    {
        $publicly   = true;
        $package_id = $package->getPackageID();
        $user_id    = (int) $user->getId();
        if ($frspf->userCanRead($package_id, $user_id)) {
            if (! $this->isMonitoring($package_id, $user, $publicly)) {
                $anonymous = false;
                $result    = $this->setMonitor($package_id, $user, $anonymous);
                if ($result) {
                    $historyDao = new ProjectHistoryDao();
                    $historyDao->groupAddHistory('frs_add_monitor_package', $package_id . '_' . $user_id, $package->getGroupID());
                    $this->notifyAfterAdd($package, $user);
                    $GLOBALS['Response']->addFeedback(Feedback::SUCCESS, $GLOBALS['Language']->getText('file_filemodule_monitor', 'monitoring_added', [$userHelper->getDisplayName($user->getUserName(), $user->getRealName())]));
                } else {
                    $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('file_filemodule_monitor', 'insert_err'));
                }
            } else {
                $GLOBALS['Response']->addFeedback(Feedback::WARN, $GLOBALS['Language']->getText('file_filemodule_monitor', 'already_monitoring', [$userHelper->getDisplayName($user->getUserName(), $user->getRealName())]));
            }
        } else {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('file_filemodule_monitor', 'user_no_permission', [$userHelper->getDisplayName($user->getUserName(), $user->getRealName())]));
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
     * @param FRSPackage        $package      Package
     * @param UserManager       $um           User manager
     * @param UserHelper        $userHelper   User helper
     *
     */
    public function stopMonitoringForUsers($users, FRSPackage $package, UserManager $um, UserHelper $userHelper): void
    {
        if ($users && ! empty($users) && is_array($users)) {
            foreach ($users as $userId) {
                $user = $um->getUserById($userId);
                if ($user) {
                    $publicly = true;
                    if ($this->isMonitoring($package->getPackageID(), $user, $publicly)) {
                        $this->stopMonitoringForUser($user, $package, $userHelper);
                    } else {
                        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('file_filemodule_monitor', 'not_monitoring', [$userHelper->getDisplayName($user->getUserName(), $user->getRealName())]));
                    }
                }
            }
        } else {
            $GLOBALS['Response']->addFeedback(Feedback::WARN, $GLOBALS['Language']->getText('file_filemodule_monitor', 'no_delete'));
        }
        $this->redirectToMonitoringPage($package);
    }

    private function stopMonitoringForUser(PFUser $user, FRSPackage $package, UserHelper $userHelper): void
    {
        $package_id = $package->getPackageID();
        if ($this->stopMonitor($package_id, $user, true)) {
            $historyDao = new ProjectHistoryDao();
            $historyDao->groupAddHistory('frs_stop_monitor_package', $package_id . '_' . $user->getId(), $package->getGroupID());
            $this->notifyAfterDelete($package, $user);
            $GLOBALS['Response']->addFeedback(Feedback::SUCCESS, $GLOBALS['Language']->getText('file_filemodule_monitor', 'deleted', [$userHelper->getDisplayName($user->getUserName(), $user->getRealName())]));
        } else {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('file_filemodule_monitor', 'delete_error', [$userHelper->getDisplayName($user->getUserName(), $user->getRealName())]));
        }
    }

    private function redirectToMonitoringPage(FRSPackage $package): void
    {
        $GLOBALS['Response']->redirect('/file/filemodule_monitor.php?' . http_build_query([
            'filemodule_id' => $package->getPackageID(),
            'group_id' => $package->getGroupID(),
        ]));
    }

    private function redirectToPackage(FRSPackage $package): void
    {
        $GLOBALS['Response']->redirect('/file/' . urlencode((string) $package->getGroupID()) . '/package/' . urlencode((string) $package->getPackageID()));
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
     * @param UserHelper  $user_helper   UserHelper instance
     *
     */
    public function getMonitoringListHTML($fileModuleId, $um, $user_helper, CSRFSynchronizerToken $csrf_token): string
    {
        $user_avatar_url_provider = new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash());
        $purifier                 = Codendi_HTMLPurifier::instance();

        $html                 = '<h2>' . $GLOBALS['Language']->getText('file_filemodule_monitor', 'monitoring_people_title') . '</h2>';
        $html                .= '<form method="post">';
        $html                .= '<input type="hidden" name="action" value="delete_monitoring">';
        $html                .= '<table class="tlp-table frs-monitor-users-table">
            <thead>
                <tr>
                    <th></th>
                    <th class="frs-monitor-user-column">' . $purifier->purify(_('User')) . '</th>
                </tr>
            </thead>';
        $list                 = $this->whoIsPubliclyMonitoringPackage($fileModuleId);
        $nb_public_monitoring = count($list);

        $total_count             = count($this->getFilesModuleMonitorFromDb($fileModuleId));
        $nb_anonymous_monitoring = $total_count - $nb_public_monitoring;

        if ($total_count === 0) {
            $html .= '<tbody>
                <tr>
                    <td colspan="2" class="tlp-table-cell-empty">
                        ' . $purifier->purify(_('No users monitoring this package')) . '
                    </td>
                </tr>
            </tbody>';
        } else {
            $html .= '<tbody>';
            foreach ($list as $entry) {
                $user = $um->getUserById($entry['user_id']);
                if ($user !== null) {
                    $html .= '<tr>';
                    $html .= '<td><input type="checkbox" name="delete_user[]" value="' . $purifier->purify($entry['user_id']) . '" /></td>';
                    $html .= '<td>';
                    $html .= '<div class="tlp-avatar">';
                    if ($user->hasAvatar()) {
                        $html .= '<img loading="lazy"
                            src="' . $purifier->purify($user_avatar_url_provider->getAvatarUrl($user)) . '"
                            alt="' . $purifier->purify(_('User avatar')) . '">';
                    }
                    $html .= '</div>';
                    $html .= ' ' . $purifier->purify($user_helper->getDisplayNameFromUser($user)) . '</td>';
                    $html .= '</tr>';
                }
            }
            $html .= '<tr><td></td><td class="tlp-text-muted">';
            $html .= $purifier->purify(
                sprintf(
                    ngettext(
                        '%s user is monitoring anonymously',
                        '%s users are monitoring anonymously',
                        $nb_anonymous_monitoring,
                    ),
                    $nb_anonymous_monitoring,
                ),
            );
            $html .= '</td></tr>';
            $html .= '</tbody>';
            $html .= '<tfoot>';
            $html .= '<tr><th></th><th>' . $purifier->purify(sprintf(_('Total: %s'), $total_count)) . '</th></tr>';
            $html .= '</tfoot>';
        }
        $html .= '</table>';

        if ($nb_public_monitoring > 0) {
            $html .= '<button type="submit" class="tlp-button-danger tlp-button-outline frs-monitor-users-delete-button">';
            $html .= '<i class="tlp-button-icon fa-solid fa-bell-slash" aria-hidden="true"></i>';
            $html .= $purifier->purify(_('Remove monitoring for selected users'));
            $html .= '</button>';
        }
        $html .= $csrf_token->fetchHTMLInput();
        $html .= '</form>';

        return $html;
    }

    /**
     * Display the form to add a user to the monitoring people by the admin
     *
     * @param int $fileModuleId Id of the package
     *
     */
    public function getAddMonitoringForm($fileModuleId, CSRFSynchronizerToken $csrf_token): string
    {
        $purifier = Codendi_HTMLPurifier::instance();

        $html  = '<form method="post" >';
        $html .= '<input type="hidden" name="action" value="add_monitoring">';
        $html .= '<input type="hidden" name="package_id" value="' . $purifier->purify($fileModuleId) . '">';
        $html .= '<div class="tlp-form-element" id="frs-monitor-user-add">';
        $html .= '<label class="tlp-label" for="listeners_to_add">' . $purifier->purify(_('Add users to the monitoring list')) . '</label>';
        $html .= '</div>';
        $html .= '<p><input type="submit" class="tlp-button-primary tlp-button-outline" value="' . $purifier->purify(_('Add')) . '" /></p>';
        $html .= $csrf_token->fetchHTMLInput();
        $html .= '</form>';

        return $html;
    }

    /**
     * Display the form to manage user's self monitoring of the package
     *
     * @param PFUser    $currentUser  Current user
     * @param int $fileModuleId Id of the package
     */
    public function getSelfMonitoringForm($currentUser, $fileModuleId, CSRFSynchronizerToken $csrf_token): string
    {
        $purifier = Codendi_HTMLPurifier::instance();

        $html                  = '<form id="filemodule_monitor_form" method="post" >';
        $html                 .= $csrf_token->fetchHTMLInput();
        $html                 .= '<input type="hidden" name="action" value="monitor_package">';
        $html                 .= '<input type="hidden" id="filemodule_id" name="filemodule_id" value="' . $purifier->purify($fileModuleId) . '" />';
        $html                 .= '<div class="tlp-form-element">';
        $html                 .= '<label class="tlp-label">' . $purifier->purify($GLOBALS['Language']->getText('file_filemodule_monitor', 'my_monitoring')) . '</label>';
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
        $html .= '<label class="tlp-label tlp-checkbox">';
        $html .= '<input type="radio" id="stop_frs_monitoring" name="frs_monitoring" value="stop_monitoring" ' . $notMonitring . '/>';
        $html .= $purifier->purify($GLOBALS['Language']->getText('file_showfiles', 'stop_monitoring'));
        $html .= '</label>';
        $html .= '<label class="tlp-label tlp-checkbox">';
        $html .= '<input type="radio" id="anonymous_frs_monitoring" name="frs_monitoring" value="anonymous_monitoring" ' . $monitoringAnonymously . '/>';
        $html .= $purifier->purify($GLOBALS['Language']->getText('file_filemodule_monitor', 'anonymous'));
        $html .= '</label>';
        $html .= '<label class="tlp-label tlp-checkbox">';
        $html .= '<input type="radio" id="public_frs_monitoring" name="frs_monitoring" value="public_monitoring" ' . $monitoringPublicly . '/>';
        $html .= $purifier->purify($GLOBALS['Language']->getText('file_showfiles', 'start_monitoring') . ' (' . $GLOBALS['Language']->getText('file_filemodule_monitor', 'public') . ')');
        $html .= '</label>';
        $html .= '<div class="tlp-pane-section-submit">';
        $html .= '<input type="submit" class="tlp-button-primary" value="' . $purifier->purify($GLOBALS['Language']->getText('global', 'btn_apply')) . '" />';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</form>';
        return $html;
    }

    /**
     * Display the HTML of the monitoring UI
     *
     * @param PFUser        $currentUser  Current user
     * @param UserManager $um           UserManager instance
     * @param UserHelper  $userHelper   UserHelper instance
     */
    public function getMonitoringHTML($currentUser, FRSPackage $package, $um, $userHelper, CSRFSynchronizerToken $csrf_token): string
    {
        $purifier = Codendi_HTMLPurifier::instance();

        $frspf = new FRSPackageFactory();
        $html  = '';
        $html .= '<section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">
                        <i class="fa-solid fa-bell tlp-pane-title-icon" aria-hidden="true"></i>
                        ' . $purifier->purify(_('Package monitoring')) . '
                    </h1>
                </div>
                <div class="tlp-pane-section">
                    <div class="tlp-property">
                        <label class="tlp-label">' . $purifier->purify(_('Package')) . '</label>
                        <p><a href="/file/' . urlencode($package->getGroupID()) . '/package/' . urlencode((string) $package->getPackageID()) . '" >' . $purifier->purify(util_unconvert_htmlspecialchars($package->getName())) . '</a></p>
                    </div>';
        $html .= $this->getSelfMonitoringForm($currentUser, $package->getPackageID(), $csrf_token);
        $html .= '</div>';
        if ($frspf->userCanAdmin($currentUser, $package->getGroupID())) {
            $html .= '<div class="tlp-pane-section">';
            $html .= $this->getMonitoringListHTML($package->getPackageID(), $um, $userHelper, $csrf_token);
            $html .= $this->getAddMonitoringForm($package->getPackageID(), $csrf_token);
            $html .= '</div>';
        }
        $html .= '
            </div>
        </section>';

        return $html;
    }

    /**
     * Process the self monitoring request
     *
     * @param HTTPRequest $request      HTTP request
     * @param PFUser        $currentUser  Current user
     *
     */
    private function processSelfMonitoringAction(HTTPRequest $request, $currentUser, FRSPackage $package): void
    {
        $anonymous     = true;
        $performAction = false;
        if ($request->get('action') === 'monitor_package' && $request->isPost()) {
            if ($request->valid(new Valid_WhiteList('frs_monitoring', ['stop_monitoring', 'anonymous_monitoring', 'public_monitoring']))) {
                $action = $request->get('frs_monitoring');
                switch ($action) {
                    case 'stop_monitoring':
                        $performAction = $this->stopMonitorActionListener($currentUser, $package->getPackageID());
                        break;
                    case 'public_monitoring':
                        $anonymous = false;
                        // Fall-through seems wanted here
                    case 'anonymous_monitoring':
                        $performAction = $this->anonymousMonitoringActionListener($currentUser, $package, $anonymous);
                        break;
                    default:
                        break;
                }
                if ($performAction) {
                    $this->redirectToPackage($package);
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
            $this->stopMonitor($fileModuleId, $currentUser);
            $GLOBALS['Response']->addFeedback(Feedback::SUCCESS, $GLOBALS['Language']->getText('file_filemodule_monitor', 'monitor_turned_off'));
            $GLOBALS['Response']->addFeedback(Feedback::SUCCESS, $GLOBALS['Language']->getText('file_filemodule_monitor', 'no_emails'));
            return true;
        } else {
            return false;
        }
    }

    /**
     * Listening to anonymous monitoring action
     *
     * @param PFUser    $currentUser  Current user
     * @param bool $anonymous Anonymous monitoring flag
     *
     * @return bool
     */
    private function anonymousMonitoringActionListener($currentUser, FRSPackage $package, $anonymous)
    {
        $package_id    = $package->getPackageID();
        $performAction = false;
        if ($anonymous && (! $this->isMonitoring($package_id, $currentUser, false) || $this->isMonitoring($package_id, $currentUser, $anonymous))) {
            $performAction = true;
        } elseif (! $anonymous && ! $this->isMonitoring($package_id, $currentUser, ! $anonymous)) {
            $performAction = true;
            $historyDao    = new ProjectHistoryDao();
            $historyDao->groupAddHistory('frs_self_add_monitor_package', $package_id, $package->getGroupID());
        }
        if ($performAction) {
            $this->stopMonitor($package_id, $currentUser);
            $result = $this->setMonitor($package_id, $currentUser, $anonymous);
            if (! $result) {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('file_filemodule_monitor', 'insert_err'));
            } else {
                $GLOBALS['Response']->addFeedback(Feedback::SUCCESS, $GLOBALS['Language']->getText('file_filemodule_monitor', 'p_monitored'));
                $GLOBALS['Response']->addFeedback(Feedback::SUCCESS, $GLOBALS['Language']->getText('file_filemodule_monitor', 'now_emails'));
            }
        }
        return $performAction;
    }

    /**
     * Process the monitoring request
     *
     * @param HTTPRequest $request      HTTP request
     * @param PFUser        $currentUser  Current user
     * @param UserManager $um           UserManager instance
     * @param UserHelper  $userHelper   UserHelper instance
     *
     */
    private function processEditMonitoringAction($request, $currentUser, FRSPackage $package, $um, $userHelper): void
    {
        $frspf = new FRSPackageFactory();

        $project_id = $package->getGroupID();

        if ($frspf->userCanAdmin($currentUser, $project_id)) {
            if ($request->valid(new Valid_WhiteList('action', ['add_monitoring', 'delete_monitoring'])) && $request->isPost()) {
                $action = $request->get('action');
                switch ($action) {
                    case 'add_monitoring':
                        $users = array_map('trim', preg_split('/[,;]/', $request->get('listeners_to_add')));
                        foreach ($users as $userName) {
                            if (! empty($userName)) {
                                $user = $um->findUser($userName);
                                if ($user !== null) {
                                    $this->addUserMonitoring($user, $package, $frspf, $userHelper);
                                }
                            }
                        }
                        $this->redirectToMonitoringPage($package);
                        break;
                    case 'delete_monitoring':
                        $users = $request->get('delete_user');
                        $this->stopMonitoringForUsers($users, $package, $um, $userHelper);
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
     * @param UserManager $um           UserManager instance
     * @param UserHelper  $userHelper   UserHelper instance
     */
    public function processMonitoringActions($request, $currentUser, FRSPackage $package, $um, $userHelper, CSRFSynchronizerToken $csrf_token): void
    {
        if (! $request->isPost()) {
            return;
        }
        $csrf_token->check();

        $this->processSelfMonitoringAction($request, $currentUser, $package);
        $this->processEditMonitoringAction($request, $currentUser, $package, $um, $userHelper);
    }
}
