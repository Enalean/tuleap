<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\LDAP;

use Account_TimezonesCollection;
use HTTPRequest;
use LDAP_UserDao;
use PFUser;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Valid_WhiteList;

class WelcomeUpdateController implements DispatchableWithRequest
{
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var LDAP_UserDao
     */
    private $ldap_user_dao;
    /**
     * @var Account_TimezonesCollection
     */
    private $timezones_collection;

    public function __construct(\UserManager $user_manager, LDAP_UserDao $ldap_user_dao, Account_TimezonesCollection $timezones_collection)
    {
        $this->user_manager = $user_manager;
        $this->ldap_user_dao = $ldap_user_dao;
        $this->timezones_collection = $timezones_collection;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array       $variables
     * @return void
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        include_once __DIR__ . '/../../../src/www/include/account.php';

        $current_user = $request->getCurrentUser();
        $timezone     = $request->get('timezone');

        if (! $this->timezones_collection->isValidTimezone($timezone)) {
            $this->welcomeExitError($request, $layout, $GLOBALS['Language']->getText('plugin_ldap', 'welcome_error_up'), $GLOBALS['Language']->getText('plugin_ldap', 'welcome_err_notz'));
            return;
        }

        $mailSite = 0;
        $vMailSite = new Valid_WhiteList('form_mail_site', array('1'));
        $vMailSite->required();
        if ($request->valid($vMailSite)) {
            $mailSite = 1;
        }

        $mailVa = 0;
        $vMailVa = new Valid_WhiteList('form_mail_va', array('1'));
        $vMailVa->required();
        if ($request->valid($vMailVa)) {
            $mailVa = 1;
        }

        if ($current_user) {
            $current_user->setTimezone($timezone);
            $current_user->setMailVA($mailVa);
            $current_user->setMailSiteUpdates($mailSite);
            $current_user->setUnixStatus('A');
            if ($this->userValuesHaveNotBeenModified($current_user, $timezone, $mailVa, $mailSite) || $this->user_manager->updateDb($current_user)) {
                $this->ldap_user_dao->setLoginDate($current_user->getId(), $_SERVER['REQUEST_TIME']);
            } else {
                $this->welcomeExitError($request, $layout, $GLOBALS['Language']->getText('plugin_ldap', 'welcome_error_up'), $GLOBALS['Language']->getText('plugin_ldap', 'welcome_error_up_expl', array('')));
                return;
            }
        }
        account_redirect_after_login($request->get('return_to'));
    }


    private function welcomeExitError(HTTPRequest $request, BaseLayout $layout, $title, $text): void
    {
        assert($layout instanceof \FlamingParrot_Theme);

        $layout->addFeedback(\Feedback::ERROR, $title);

        if ((int) $request->get('pv') === 2) {
            $layout->pv_header(array());
        } else {
            site_header(array('title' => $GLOBALS['Language']->getText('include_exit', 'exit_error'), 'registeration_process' => true));
        }

        echo '<p>',$text,'</p>';

        if ((int) $request->get('pv') === 2) {
            $layout->pv_footer(array());
        } else {
            $layout->footer(array('showfeedback' => false));
        }
    }

    private function userValuesHaveNotBeenModified(PFUser $current_user, $timezone, $mailVa, $mailSite): bool
    {
        return $current_user->getTimezone() == $timezone &&
            $current_user->getMailVA() == $mailVa &&
            $current_user->getMailSiteUpdates() == $mailSite;
    }
}
