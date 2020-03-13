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

use HTTPRequest;
use LDAP_UserManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Valid_Pv;
use Valid_String;

class WelcomeDisplayController implements DispatchableWithRequest
{

    /**
     * @var LDAP_UserManager
     */
    private $ldap_user_manager;
    /**
     * @var \Codendi_HTMLPurifier
     */
    private $html_purifier;
    /**
     * @var string
     */
    private $base_url;

    public function __construct(LDAP_UserManager $ldap_user_manager, \Codendi_HTMLPurifier $html_purifier, string $base_url)
    {
        $this->ldap_user_manager = $ldap_user_manager;
        $this->html_purifier     = $html_purifier;
        $this->base_url          = $base_url;
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
        assert($layout instanceof \FlamingParrot_Theme);

        $currentUser = $request->getCurrentUser();
        $timezone = $request->get('timezone');

        $pv = 0;
        $vPv = new Valid_Pv();
        if ($request->valid($vPv)) {
            $pv = $request->get('pv');
        }

        $lr = $this->ldap_user_manager->getLdapFromUserId($request->getCurrentUser()->getId());
        if ($lr === false) {
            $layout->addFeedback(\Feedback::ERROR, dgettext('tuleap-ldap', 'Unable to retrieve LDAP info for your user account, skipping Welcome page'));
            $layout->redirect('/my/');
            return;
        }
        $ldap_name = $lr->getLogin();

        $star = '<span class="highlight"><big>*</big></span>';

        if ($pv === 2) {
            $layout->pv_header(array());
        } else {
            $layout->header(array('title' => $GLOBALS['Language']->getText('plugin_ldap', 'welcome_title', array($lr->getCommonName())),
                                'registeration_process' => true));
        }

        print '<h2>';
        print $GLOBALS['Language']->getText('plugin_ldap', 'welcome_title', array($lr->getCommonName()));
        print '</h2>';

        print '<h3>';
        print $GLOBALS['Language']->getText('plugin_ldap', 'welcome_first_login', array($GLOBALS['sys_name']));
        print '</h3>';

        print '<p>' . $GLOBALS['Language']->getText('plugin_ldap', 'welcome_fill_form', array($GLOBALS['sys_name'])) . '</p>';

        print '<fieldset>';

        print '<legend>' . $GLOBALS['Language']->getText('plugin_ldap', 'welcome_preferences') . '</legend>';

        $return_to = '';
        $vReturnTo = new Valid_String('return_to');
        $vReturnTo->required();
        if ($request->valid($vReturnTo)) {
            $return_to = trim($request->get('return_to'));
        }

        print '
<form name="welcome" action="' . $this->base_url . '/welcome" method="post">
<input type="hidden" name="return_to" value="' . $this->html_purifier->purify($return_to, CODENDI_PURIFIER_CONVERT_HTML) . '">
<input type="hidden" name="action" value="update_reg">
<input type="hidden" name="pv" value="' . $pv . '">

<p>' . $star . ' ' . $GLOBALS['Language']->getText('plugin_ldap', 'welcome_tz') . ':';

        echo html_get_timezone_popup($timezone);

        print '</p>
<p><input type="checkbox" name="form_mail_site" value="1" checked />' . $GLOBALS['Language']->getText('plugin_ldap', 'welcome_siteupdate');

        print '</p>
<p><input type="checkbox" name="form_mail_va" value="1" />' . $GLOBALS['Language']->getText('plugin_ldap', 'welcome_communitymail') . '</p>';

        print '<p>' . $GLOBALS['Language']->getText('plugin_ldap', 'welcome_mandatory', array($star)) . '</p>';

        print '<p><input type="submit" name="update_reg" value="' . $GLOBALS['Language']->getText('plugin_ldap', 'welcome_btn_update') . '"></p>';
        print '</fieldset>';

        print '<fieldset>';
        print '<legend>' . $GLOBALS['Language']->getText('plugin_ldap', 'welcome_your_data', array($GLOBALS['sys_org_name'])) . '</legend>';

        print '<table>
<tr>
<td>' . $GLOBALS['Language']->getText('plugin_ldap', 'welcome_ldap_login') . '</td>
<td><strong>' . $ldap_name . '</strong></td>
</tr>
<tr>
<td>' . $GLOBALS['Language']->getText('plugin_ldap', 'welcome_email') . '</td>
<td><strong>' . $currentUser->getEmail() . '</strong></td>
</tr>
<tr>
<td>' . $GLOBALS['Language']->getText('plugin_ldap', 'welcome_codendi_login', array($GLOBALS['sys_name'])) . '</td>
<td>' . $currentUser->getUserName() . '<br>
' . $GLOBALS['Language']->getText('plugin_ldap', 'welcome_codendi_login_j', array($GLOBALS['sys_name'])) . '
</td>
</tr>
</table>';

        print '</fieldset>';

        ($pv === 2) ? $layout->pv_footer(array()) : $layout->footer(array());
    }
}
