<?php
/**
 * Copyright (c) Enalean, 2015 — 2018. All Rights Reserved.
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

use Tracker\FormElement\Field\ArtifactLink\Nature\NatureConfigPresenter;
use Tuleap\Tracker\Config\EmailGateWayPresenter;
use Tuleap\Tracker\Config\SectionsPresenter;
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
        $title,
        $localinc_path,
        MailGatewayConfig $config
    ) {
        $this->title                 = $title;
        $this->csrf_token            = $csrf->fetchHTMLInput();

        $this->is_insecure_emailgateway_enabled    = $config->isInsecureEmailgatewayEnabled();
        $this->is_token_based_emailgateway_enabled = $config->isTokenBasedEmailgatewayEnabled();
        $this->is_emailgateway_disabled            = $config->isEmailgatewayDisabled();

        $this->email_gateway            = $GLOBALS['Language']->getText('plugin_tracker_config', 'email_gateway');
        $this->email_gateway_pane_title = $GLOBALS['Language']->getText('plugin_tracker_config', 'email_gateway_pane_title');
        $this->email_gateway_desc       = $GLOBALS['Language']->getText('plugin_tracker_config', 'email_gateway_desc');
        $this->disable                  = $GLOBALS['Language']->getText('plugin_tracker_config', 'disable');
        $this->disable_desc             = $GLOBALS['Language']->getText('plugin_tracker_config', 'disable_desc');
        $this->token                    = $GLOBALS['Language']->getText('plugin_tracker_config', 'token');
        $this->token_desc               = $GLOBALS['Language']->getText('plugin_tracker_config', 'token_desc');
        $this->insecure                 = $GLOBALS['Language']->getText('plugin_tracker_config', 'insecure');
        $this->insecure_desc            = $GLOBALS['Language']->getText('plugin_tracker_config', 'insecure_desc');
        $this->save_conf                = $GLOBALS['Language']->getText('admin_main', 'save_conf');

        $this->sections = new EmailGateWayPresenter();

        $this->is_localinc_obsolete      = $this->isLocalIncObsolete($localinc_path);
        $this->localinc_obsolete_message = $GLOBALS['Language']->getText(
            'plugin_tracker_config',
            'localinc_obsolete_message',
            $localinc_path
        );
    }

    private function isLocalIncObsolete($localinc_path)
    {
        include($localinc_path);
        $variables_in_local_inc = get_defined_vars();

        return isset($variables_in_local_inc['sys_enable_reply_by_mail']);
    }
}
