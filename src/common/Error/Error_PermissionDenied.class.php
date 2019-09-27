<?php
/**
 * Copyright (c) Enalean, 2016-2019. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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

/**
 * It allows the management of permission denied error.
 * It offres to user the possibility to request the project membership directly.
 */
abstract class Error_PermissionDenied // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * @var URL
     */
    protected $url;

    public function __construct(?URL $url = null)
    {
        if ($url === null) {
            $url = new URL();
        }
        $this->url = $url;
    }

    /**
     * Returns the type of the error to manage
     *
     * @return String
     */
    abstract function getType(); // phpcs:ignore Squiz.Scope.MethodScope.Missing

    /**
     * Returns the base on language file
     *
     * @return String
     */
    function getTextBase()  // phpcs:ignore Squiz.Scope.MethodScope.Missing
    {
        return 'include_exit';
    }


     /**
     * Returns the build interface parameters
     *
     * @return Array
     */
    abstract function returnBuildInterfaceParam();  // phpcs:ignore Squiz.Scope.MethodScope.Missing

    /**
     * Returns the url link after modification if needed else returns the same string
     *
     * @param String $link
     * @param BaseLanguage $language
     */
    function getRedirectLink($link, $language)  // phpcs:ignore Squiz.Scope.MethodScope.Missing
    {
        return $link;
    }

    /**
     * Build the user interface to ask for membership
     *
     */
    function buildInterface(PFUser $user, ?Project $project = null)  // phpcs:ignore Squiz.Scope.MethodScope.Missing
    {
        if ($user->isAnonymous()) {
            $event_manager = EventManager::instance();
            $redirect = new URLRedirect($event_manager);
            $redirect->redirectToLogin();
        } else {
            $this->buildPermissionDeniedInterface($project);
        }
    }


    /**
     *
     * Returns the administrators' list of a given project
     *
     * @param Project $project
     *
     * @return Array
     */
    function extractReceiver($project, $urlData)  // phpcs:ignore Squiz.Scope.MethodScope.Missing
    {
        $admins = array();
        $status  = true;
        $pm = ProjectManager::instance();
        $res = $pm->getMembershipRequestNotificationUGroup($project->getId());
        if ($res && !$res->isError()) {
            if ($res->rowCount() == 0) {
                $dar = $pm->returnProjectAdminsByGroupId($project->getId());
                if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
                    foreach ($dar as $row) {
                        $admins[] = $row['email'];
                    }
                }
            } else {
                /* We can face one of these composition for ugroups array:
                 * 1 - UGROUP_PROJECT_ADMIN
                 * 2 - UGROUP_PROJECT_ADMIN, UGROUP_1, UGROUP_2,.., UGROUP_n
                 * 3 - UGROUP_1, UGROUP_2,.., UGROUP_n
                 */
                $ugroups = array();
                $dars = array();
                foreach ($res as $row) {
                    if ($row['ugroup_id'] == $GLOBALS['UGROUP_PROJECT_ADMIN']) {
                        $dar = $pm->returnProjectAdminsByGroupId($project->getId());
                        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
                            $dars[] = $dar;
                        }
                    } else {
                        $ugroups[] = $row['ugroup_id'];
                    }
                }
                if (count($ugroups) > 0) {
                    $dar = $this->getUGroup()->returnProjectAdminsByStaticUGroupId($project->getId(), $ugroups);
                    if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
                        $dars[] = $dar;
                    }
                }
                foreach ($dars as $dar) {
                    foreach ($dar as $row) {
                        $admins[] = $row['email'];
                    }
                }
            }

            //If all selected ugroups are not valid, send mail to the project admins
            if (count($admins) == 0) {
                $status = false;
                $dar = $pm->returnProjectAdminsByGroupId($project->getId());
                if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
                    foreach ($dar as $row) {
                        $admins[] = $row['email'];
                    }
                }
            }
        }
        return array('admins' => $admins, 'status' => $status);
    }

    /**
     * Prepare the mail inputs
     * @return String $messageToAdmin
     */
    function processMail($messageToAdmin)  // phpcs:ignore Squiz.Scope.MethodScope.Missing
    {
        $request =HTTPRequest::instance();

        $pm = $this->getProjectManager();
        $project = $pm->getProject($request->get('groupId'));

        $user_manager = $this->getUserManager();
        $user         = $user_manager->getCurrentUser();

        $messageToAdmin = trim($messageToAdmin);
        $messageToAdmin ='>'.$messageToAdmin;
        $messageToAdmin = str_replace(array("\r\n"), "\n>", $messageToAdmin);

        $hrefApproval = $request->getServerUrl() . '/project/admin/?group_id='.$request->get('groupId');
        $urlData      = $request->getServerUrl() . $request->get('url_data');
        return $this->sendMail($project, $user, $urlData, $hrefApproval, $messageToAdmin);
    }


    /**
     * Send mail to administrators with the apropriate subject and body
     *
     * @param Project $project
     * @param PFUser    $user
     * @param String  $urlData
     * @param String  $hrefApproval
     * @param String  $messageToAdmin
     */
    public function sendMail($project, $user, $urlData, $hrefApproval, $messageToAdmin)
    {
        $mail = new Codendi_Mail();

        //to
        $adminList = $this->extractReceiver($project, $urlData);
        $admins = array_unique($adminList['admins']);
        $to = implode(',', $admins);
        $mail->setTo($to);

        $mail->setFrom(ForgeConfig::get('sys_noreply'));
        $mail->addAdditionalHeader('Reply-To', $user->getEmail());

        $mail->setSubject($this->getPermissionDeniedMailSubject($project, $user));

        $link = $this->getRedirectLink($urlData, $GLOBALS['Language']);
        $body = $this->getPermissionDeniedMailBody($project, $user, $hrefApproval, $messageToAdmin, $link);
        if ($adminList['status']== false) {
            $body .= "\n\n". $GLOBALS['Language']->getText($this->getTextBase(), 'mail_content_unvalid_ugroup', array($project->getPublicName()));
        }
        $mail->setBodyText($body);

        if (!$mail->send()) {
            exit_error($GLOBALS['Language']->getText('global', 'error'), $GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])));
        }

        $GLOBALS['Response']->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('include_exit', 'request_sent'));
        $GLOBALS['Response']->redirect('/my');
        exit;
    }

    /**
     * Get an instance of UserManager. Mainly used for mock
     *
     * @return UserManager
     */
    protected function getUserManager()
    {
        return UserManager::instance();
    }

    /**
     * Get an instance of UserManager. Mainly used for mock
     *
     * @return ProjectManager
     */
    protected function getProjectManager()
    {
        return ProjectManager::instance();
    }

    /**
     * Get an instance of ProjectUGroup.
     *
     * @return ProjectUGroup
     */
    protected function getUGroup()
    {
        return new ProjectUGroup();
    }

    /**
     * Build the Permission Denied error interface
     */
    private function buildPermissionDeniedInterface(?Project $project = null)
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $param    = $this->returnBuildInterfaceParam();

        site_header(array('title' => $GLOBALS['Language']->getText('include_exit', 'exit_error')));

        echo "<b>" . $purifier->purify($GLOBALS['Language']->getText($this->getTextBase(), 'perm_denied')) . "</b>";
        echo '<br></br>';
        echo "<br>" . $purifier->purify($GLOBALS['Language']->getText($this->getTextBase(), $param['index']), CODENDI_PURIFIER_FULL);

        //In case of restricted user, we only show the zone text area to ask for membership
        //just when the requested page belongs to a project
        if (!(($param['func'] == 'restricted_user_request') && ($project === null))) {
            $message = $GLOBALS['Language']->getText('project_admin_index', 'member_request_delegation_msg_to_requester');
            $pm = ProjectManager::instance();
            $dar = $pm->getMessageToRequesterForAccessProject($project->getID());
            if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
                $row = $dar->current();
                if ($row['msg_to_requester'] != "member_request_delegation_msg_to_requester") {
                    $message = $row['msg_to_requester'];
                }
            }
            echo $GLOBALS['Language']->getText($this->getTextBase(), 'request_to_admin');
            echo '<br></br>';
            echo '<form action="' . $purifier->purify($param['action']) . '" method="post" name="display_form">
                  <textarea wrap="virtual" rows="5" cols="70" name="' . $purifier->purify($param['name']) . '">' . $purifier->purify($message) . ' </textarea></p>
                  <input type="hidden" id="func" name="func" value="' . $purifier->purify($param['func']) . '">
                  <input type="hidden" id="groupId" name="groupId" value="' . $purifier->purify($project->getID()) . '">
                  <input type="hidden" id="data" name="url_data" value="' . $purifier->purify($_SERVER['REQUEST_URI']) . '">
                  <br><input name="Submit" type="submit" value="' . $purifier->purify($GLOBALS['Language']->getText('include_exit', 'send_mail')) . '"/></br>
              </form>';
        }

        $GLOBALS['HTML']->footer(array('showfeedback' => false));
    }

    protected function getPermissionDeniedMailBody(
        Project $project,
        PFUser $user,
        string $href_approval,
        string $message_to_admin,
        string $link
    ) {
        return $GLOBALS['Language']->getText(
            $this->getTextBase(),
            'mail_content_' . $this->getType(),
            [
                $user->getRealName(),
                $user->getName(),
                $link,
                $project->getPublicName(),
                $href_approval,
                $message_to_admin,
                $user->getEmail()
            ]
        );
    }

    protected function getPermissionDeniedMailSubject(Project $project, PFUser $user)
    {
        return $GLOBALS['Language']->getText(
            $this->getTextBase(),
            'mail_subject_' . $this->getType(),
            [$project->getPublicName(), $user->getRealName()]
        );
    }
}
