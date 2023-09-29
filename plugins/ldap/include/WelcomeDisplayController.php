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

use Account_TimezoneSelectorPresenter;
use ForgeConfig;
use HTTPRequest;
use LDAP_UserManager;
use TemplateRendererFactory;
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
        assert($layout instanceof \Layout);

        $currentUser = $request->getCurrentUser();
        $timezone    = $request->get('timezone');
        if ($timezone === false) {
            $timezone = null;
        }

        $pv  = 0;
        $vPv = new Valid_Pv();
        if ($request->valid($vPv)) {
            $pv = $request->get('pv');
        }

        $lr = $this->ldap_user_manager->getLdapFromUserId($request->getCurrentUser()->getId());
        if ($lr === false) {
            $layout->addFeedback(\Feedback::ERROR, dgettext('tuleap-ldap', 'Unable to retrieve LDAP info for your user account, skipping Welcome page'));
            $layout->redirect('/my/');
        }
        $ldap_name = $lr->getLogin();

        $star = '<span class="highlight"><big>*</big></span>';

        if ($pv === 2) {
            $layout->pv_header([]);
        } else {
            $layout->header(
                [
                    'title' => sprintf(dgettext('tuleap-ldap', 'Welcome %1$s'), $this->html_purifier->purify($lr->getCommonName())),
                    'registeration_process' => true,
                ]
            );
        }

        print '<h2>';
        print sprintf(dgettext('tuleap-ldap', 'Welcome %1$s'), $this->html_purifier->purify($lr->getCommonName()));
        print '</h2>';

        print '<h3>';
        print sprintf(dgettext('tuleap-ldap', 'First login to %1$s'), \ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME));
        print '</h3>';

        print '<p>' . dgettext('tuleap-ldap', 'Thank you to fill the following before accessing your data:') . '</p>';

        print '<fieldset>';

        print '<legend>' . dgettext('tuleap-ldap', 'Account preferences') . '</legend>';

        $return_to = '';
        $vReturnTo = new Valid_String('return_to');
        $vReturnTo->required();
        if ($request->valid($vReturnTo)) {
            $return_to = trim($request->get('return_to'));
        }

        print '
<form name="welcome" action="' . $this->html_purifier->purify($this->base_url) . '/welcome" method="post">
<input type="hidden" name="return_to" value="' . $this->html_purifier->purify($return_to, CODENDI_PURIFIER_CONVERT_HTML) . '">
<input type="hidden" name="action" value="update_reg">
<input type="hidden" name="pv" value="' . $this->html_purifier->purify($pv) . '">

<p>' . $star . ' ' . dgettext('tuleap-ldap', 'Timezone') . ':';

        echo $this->getTimezonePopup($layout, $timezone);

        print '</p>
<p><input type="checkbox" name="form_mail_site" value="1" checked />' . dgettext('tuleap-ldap', 'Receive Email about Site Updates <em>(Very low traffic and includes security notices. Highly Recommended.)</em>');

        print '</p>
<p><input type="checkbox" name="form_mail_va" value="1" />' . dgettext('tuleap-ldap', 'Receive additional community mailings. <em>(Low traffic.)</em>') . '</p>';

        print '<p>' . sprintf(dgettext('tuleap-ldap', 'Fields marked with %1$s are mandatory.'), $star) . '</p>';

        print '<p><input type="submit" name="update_reg" value="' . dgettext('tuleap-ldap', 'Update my account') . '"></p>';
        print '</fieldset>';

        print '<fieldset>';
        print '<legend>' . dgettext('tuleap-ldap', 'Account details') . '</legend>';

        print '<table>
<tr>
<td>' . dgettext('tuleap-ldap', 'User name:') . '</td>
<td><strong>' . $this->html_purifier->purify($ldap_name) . '</strong></td>
</tr>
<tr>
<td>' . dgettext('tuleap-ldap', 'Email Address:') . '</td>
<td><strong>' . $this->html_purifier->purify($currentUser->getEmail()) . '</strong></td>
</tr>
<tr>
<td>' . sprintf(dgettext('tuleap-ldap', '%1$s internal login:'), \ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME)) . '</td>
<td>' . $this->html_purifier->purify($currentUser->getUserName()) . '<br>
</td>
</tr>
</table>';

        print '</fieldset>';

        ($pv === 2) ? $layout->pv_footer() : $layout->footer([]);
    }

    private function getTimezonePopup(BaseLayout $layout, ?string $timezone): string
    {
        $layout->includeFooterJavascriptFile('/scripts/tuleap/timezone.js');
        $renderer = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/account/');
        return $renderer->renderToString('timezone', new Account_TimezoneSelectorPresenter($timezone));
    }
}
