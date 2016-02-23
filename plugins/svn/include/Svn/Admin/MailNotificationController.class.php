<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Svn\Admin;

use Tuleap\Svn\ServiceSvn;
use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\Svn\Repository\Repository;
use Project;
use HTTPRequest;
use Valid_String;
use Valid_Array;
use CSRFSynchronizerToken;

class MailNotificationController {

    private $repository_manager;
    private $mail_header_manager;
    private $mail_notification_manager;

    public function __construct(
            MailHeaderManager $mail_header_manager,
            RepositoryManager $repository_manager,
            MailNotificationManager $mail_notification_manager
        ) {
        $this->repository_manager        = $repository_manager;
        $this->mail_header_manager       = $mail_header_manager;
        $this->mail_notification_manager = $mail_notification_manager;
    }

    private function generateToken(Project $project, Repository $repository) {
        return new CSRFSynchronizerToken(SVN_BASE_URL."/?group_id=".$project->getid(). '&repo_id='.$repository->getId()."&action=display-mail-notification");
    }

    public function displayMailNotification(ServiceSvn $service, HTTPRequest $request) {
        $repository = $this->repository_manager->getById($request->get('repo_id'), $request->getProject());

        $token = $this->generateToken($request->getProject(), $repository);

        $mail_header           = $this->mail_header_manager->getByRepository($repository);
        $notifications_details = $this->mail_notification_manager->getByRepository($repository);

        $title = $GLOBALS['Language']->getText('global', 'Administration');

        $service->renderInPage(
            $request,
            $repository->getName() .' – '. $title,
            'admin/mail_notification',
            new MailNotificationPresenter(
                $repository,
                $request->getProject(),
                $token,
                $title,
                $mail_header,
                $notifications_details
            )
        );
    }

    public function saveMailHeader(HTTPRequest $request) {
        $repository = $this->repository_manager->getById($request->get('repo_id'), $request->getProject());

        $token = $this->generateToken($request->getProject(), $repository);
        $token->check();

        $repo_name = $request->get("form_mailing_header");
        $vHeader = new Valid_String('form_mailing_header');
        if($request->valid($vHeader)) {
            $mail_header = new MailHeader($repository, $repo_name);
            try {
                $this->mail_header_manager->create($mail_header);
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_svn_admin_notification','upd_header_success'));
            } catch (CannotCreateMailHeaderException $e) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svn_admin_notification','upd_header_fail'));
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svn_admin_notification','upd_header_fail'));
        }

        $GLOBALS['Response']->redirect(SVN_BASE_URL.'/?'. http_build_query(
            array('group_id' => $request->getProject()->getid(),
                  'repo_id'  => $request->get('repo_id'),
                  'action'   => 'display-mail-notification'
            )));
    }

    public function createMailingList(HTTPRequest $request) {
        $repository = $this->repository_manager->getById($request->get('repo_id'), $request->getProject());

        $token = $this->generateToken($request->getProject(), $repository);
        $token->check();

        $valid_path    = new Valid_String('form_path');
        $form_path     = $request->get('form_path');
        $list_mails    = new MailReceivedFromUserExtractor($request->get('form_mailing_list'));
        $valid_mails   = join(', ', $list_mails->getValidAdresses());
        $invalid_mails = join(', ', $list_mails->getInvalidAdresses());

        if(!empty($valid_mails) && !empty($form_path) && $request->valid($valid_path)) {
            $mail_notification = new MailNotification($repository, $valid_mails, $form_path);
            try {
                $this->mail_notification_manager->create($mail_notification);
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_svn_admin_notification','upd_email_success'));
            } catch (CannotCreateMailHeaderException $e) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svn_admin_notification','upd_email_error'));
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svn_admin_notification','upd_email_fail'));
        }
        if (!empty($invalid_mails)) {
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_svn_admin_notification','upd_email_bad_adr', $invalid_mails));
        }

        $GLOBALS['Response']->redirect(SVN_BASE_URL.'/?'. http_build_query(
            array('group_id' => $request->getProject()->getid(),
                  'repo_id'  => $request->get('repo_id'),
                  'action'   => 'display-mail-notification'
            )));
    }

    public function deleteMailingList(HTTPRequest $request) {
        $repository = $this->repository_manager->getById($request->get('repo_id'), $request->getProject());

        $token = $this->generateToken($request->getProject(), $repository);
        $token->check();

        $vPathToDelete = new Valid_Array('paths_to_delete');
        if($request->valid($vPathToDelete)) {
            $PathsToDelete = $request->get('paths_to_delete');
            try {
                $this->mail_notification_manager->removeSvnNotification($repository, $PathsToDelete);
            } catch (CannotDeleteMailNotificationException $e) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_svn_admin_notification','delete_error'));
            }
        }

        $GLOBALS['Response']->redirect(SVN_BASE_URL.'/?'. http_build_query(
            array('group_id' => $request->getProject()->getid(),
                  'repo_id'  => $request->get('repo_id'),
                  'action'   => 'display-mail-notification'
            )));
    }

}
