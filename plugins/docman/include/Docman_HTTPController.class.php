<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 * Copyright (c) Enalean, 2017-Present. All rights reserved
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

use Tuleap\Date\DateHelper;
use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;

class Docman_HTTPController extends Docman_Controller
{
    public function __construct(&$plugin, $pluginPath, $themePath, $request = null)
    {
        if (! $request) {
            $request = HTTPRequest::instance();
        }
        parent::__construct($plugin, $pluginPath, $themePath, $request);
    }

    /* protected */ public function _includeView()
    {
        $className = 'Docman_View_' . $this->view;
        if (file_exists(dirname(__FILE__) . '/view/' . $className . '.class.php')) {
            require_once('view/' . $className . '.class.php');
            return $className;
        }
        return false;
    }

    /* protected */ public function _set_deleteView_errorPerms()
    {
        $this->view = 'Details';
    }

    /* protected */ public function _set_redirectView()
    {
        if ($redirect_to = Docman_Token::retrieveUrl($this->request->get('token'))) {
            $this->_viewParams['redirect_to'] = $redirect_to;
        }
        $this->view = 'RedirectAfterCrud';
    }

    /* protected */ public function _setView($view)
    {
        if ($view == 'getRootFolder') {
            $this->feedback->log('error', 'Unable to process request');
            $this->_set_redirectView();
        } else {
            $this->view = $view;
        }
    }

    /* protected */ public function _set_moveView_errorPerms()
    {
        $this->view = 'Details';
    }

    /* protected */ public function _set_createItemView_errorParentDoesNotExist(&$item, $get_show_view)
    {
           $this->view = $item->accept($get_show_view, $this->request->get('report'));
    }

    /* protected */ public function _set_createItemView_afterCreate($view)
    {
        if ($view == 'createFolder') {
            $this->view = 'NewFolder';
        } else {
            $this->view = 'NewDocument';
        }
    }

    /* protected */ public function _set_doesnot_belong_to_project_error($item, $group)
    {
        $this->feedback->log('warning', sprintf(dgettext('tuleap-docman', 'The item %1$s doesn\'t exist or doesn\'t belong to project %2$s.'), $item->getId(), $group->getPublicName()));
        $this->_viewParams['redirect_to'] = str_replace('group_id=' . $this->request->get('group_id'), 'group_id=' . $item->getGroupId(), $_SERVER['REQUEST_URI']);
        $this->view                       = 'Redirect';
    }

    /**
     * Get the list of all futur obsolete documents and warn document owner
     * about this obsolescence.
     */
    public function notifyFuturObsoleteDocuments()
    {
        $hp          = Codendi_HTMLPurifier::instance();
        $pm          = ProjectManager::instance();
        $itemFactory = new Docman_ItemFactory(0);

        //require_once('common/mail/TestMail.class.php');
        //$mail = new TestMail();
        //$mail->_testDir = '/local/vm16/codev/servers/docman-2.0/var/spool/mail';

        $itemIter = $itemFactory->findFuturObsoleteItems();
        $itemIter->rewind();
        while ($itemIter->valid()) {
            $item = $itemIter->current();

            // Users
            $um    = UserManager::instance();
            $owner = $um->getUserById($item->getOwnerId());

            // Project
            $group = $pm->getProject($item->getGroupId());

            // Date
            $obsoDate = DateHelper::formatForLanguage($GLOBALS['Language'], $item->getObsolescenceDate(), true);

            // Urls
            $baseUrl   = \Tuleap\ServerHostname::HTTPSUrl() . $this->pluginPath . '/index.php?group_id=' . $item->getGroupId() . '&id=' . $item->getId();
            $directUrl = $baseUrl . '&action=show';
            $detailUrl = $baseUrl . '&action=details';

            $subj = sprintf(dgettext('tuleap-docman', '[%1$s] Document \'%2$s\' will be obsolete in one month'), ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME), $item->getTitle());
            $body = sprintf(dgettext('tuleap-docman', 'As document owner, you are notified of the obsolescence in one month of the
document:
Title: %1$s
Project: %2$s
Obsolescence date: %3$s
Direct Link: <%4$s>

This document will disappear from your document manager in one month if you
do nothing. The document will remain accessible through the administration
interface though.
You can change the obsolescence date of your document or make it permanent with
the following link:
<%5$s>

--
This is an automatic message sent by a robot. Please do not reply to this email.'), $item->getTitle(), $hp->purify($group->getPublicName()), $obsoDate, $directUrl, $detailUrl);

            $mail_notification_builder = new MailNotificationBuilder(
                new MailBuilder(
                    TemplateRendererFactory::build(),
                    new MailFilter(
                        UserManager::instance(),
                        new ProjectAccessChecker(
                            new RestrictedUserCanAccessProjectVerifier(),
                            EventManager::instance()
                        ),
                        new MailLogger()
                    )
                )
            );

            $mail_notification_builder->buildAndSendEmail(
                $group,
                [$owner->getEmail()],
                $subj,
                '',
                $body,
                $baseUrl,
                DocmanPlugin::TRUNCATED_SERVICE_NAME,
                new MailEnhancer()
            );

            $itemIter->next();
        }
    }
}
