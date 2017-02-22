<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Captcha\Administration;

use CSRFSynchronizerToken;
use Tuleap\Captcha\Configuration;

class Presenter
{
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;
    public $captcha_title;
    public $captcha_configuration;
    public $save_settings;
    public $site_key_label;
    public $secret_key_label;
    public $site_key;
    public $secret_key;
    public $google_recaptcha_administration;
    public $obtain_keys;

    public function __construct(CSRFSynchronizerToken $csrf_token, Configuration $configuration)
    {
        $this->csrf_token            = $csrf_token;
        $this->captcha_title         = dgettext('tuleap-captcha', 'Captcha');
        $this->captcha_configuration = dgettext('tuleap-captcha', 'Captcha configuration');
        $this->save_settings         = dgettext('tuleap-captcha', 'Save settings');
        $this->site_key_label        = dgettext('tuleap-captcha', 'Site key');
        $this->secret_key_label      = dgettext('tuleap-captcha', 'Secret key');
        $this->site_key              = $configuration->getSiteKey();
        $this->secret_key            = $configuration->getSecretKey();
        $this->obtain_keys           = dgettext('tuleap-captcha', 'You can get the required keys from the Google reCAPTCHA administration');
    }
}
