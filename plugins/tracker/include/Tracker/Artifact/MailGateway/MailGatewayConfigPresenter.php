<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Artifact\MailGateway;

use Tuleap\Tracker\Config\EmailGateWayPresenter;
use CSRFSynchronizerToken;

class MailGatewayConfigPresenter
{
    /** @var string */
    public $csrf_token;

    /** @var string */
    public $title;

    /** @var bool */
    public $is_insecure_emailgateway_enabled;

    /** @var bool */
    public $is_token_based_emailgateway_enabled;

    /** @var bool */
    public $is_emailgateway_disabled;
    public $sections;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        string $title,
        string $localinc_path,
        MailGatewayConfig $config,
    ) {
        $this->title      = $title;
        $this->csrf_token = $csrf->fetchHTMLInput();

        $this->is_insecure_emailgateway_enabled    = $config->getEmailgatewayRowMode() === MailGatewayConfig::INSECURE;
        $this->is_token_based_emailgateway_enabled = $config->getEmailgatewayRowMode() === MailGatewayConfig::TOKEN;
        $this->is_emailgateway_disabled            = $config->getEmailgatewayRowMode() === MailGatewayConfig::DISABLED;

        $this->email_gateway            = dgettext('tuleap-tracker', 'Email Gateway');
        $this->email_gateway_pane_title = dgettext('tuleap-tracker', 'Email Gateway configuration');
        $this->email_gateway_desc       = dgettext('tuleap-tracker', 'Allow user to interact with trackers by email.');
        $this->disable                  = dgettext('tuleap-tracker', 'Disable email gateway');
        $this->disable_desc             = dgettext('tuleap-tracker', 'The feature is deactivated, nobody can interact with trackers by email.');
        $this->token                    = dgettext('tuleap-tracker', 'Token based email gateway');
        $this->token_desc               = dgettext('tuleap-tracker', 'Users can interact with trackers by email. Authentication is done through token that is injected in the headers of the email. This decreases the risk of a forged email, we can moderately trust the sender.<br>As of today, only reply (add a follow-up comment) by email is supported by this option.');
        $this->insecure                 = dgettext('tuleap-tracker', 'Insecure email gateway');
        $this->insecure_desc            = dgettext('tuleap-tracker', 'Users can interact with trackers by email. Authentication is not done at all.<span class="text-error"><i class="fas fa-exclamation-triangle"></i> With this option we <strong>cannot trust the sender of the email</strong>. This means that villains can easily forge an email and pretend to be someone they are not.</span><br>As of today, users can create artifacts and reply (add a follow-up comment) by email with this option.');

        $this->sections = new EmailGateWayPresenter();

        $this->is_localinc_obsolete      = $this->isLocalIncObsolete($localinc_path);
        $this->localinc_obsolete_message = sprintf(dgettext('tuleap-tracker', '<h4><i class="fa fa-exclamation-triangle"></i> Your local.inc file is outdated!</h4><p>It appears that your local.inc file contains definitions of variables that are unused and it may lead to confusion.</p><p>Please edit <code>%1$s</code> and remove the following variable: <code>$sys_enable_reply_by_mail</code>.</p>'), $localinc_path);
    }

    private function isLocalIncObsolete($localinc_path): bool
    {
        include($localinc_path);
        $variables_in_local_inc = get_defined_vars();

        return isset($variables_in_local_inc['sys_enable_reply_by_mail']);
    }
}
