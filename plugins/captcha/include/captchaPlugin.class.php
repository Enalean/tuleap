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

use Tuleap\Captcha\ConfigurationRetriever;
use Tuleap\Captcha\DataAccessObject;
use Tuleap\Captcha\Plugin\Info as PluginInfo;
use Tuleap\Captcha\Registration\Presenter;

class captchaPlugin extends Plugin
{
    public function __construct($id)
    {
        parent::__construct($id);

        bindtextdomain('tuleap-captcha', __DIR__ . '/../site-content');

        $this->setScope(self::SCOPE_SYSTEM);

        $this->addHook('javascript_file', 'loadJavascriptFiles');
        $this->addHook('cssfile', 'loadCSSFiles');
        $this->addHook(Event::CONTENT_SECURITY_POLICY_SCRIPT_WHITELIST, 'addExternalScriptToTheWhitelist');
        $this->addHook(Event::USER_REGISTER_ADDITIONAL_FIELD, 'addAdditionalFieldUserRegistration');
        $this->addHook(Event::BEFORE_USER_REGISTRATION, 'checkCaptchaBeforeSubmission');
    }

    /**
     * @return PluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo instanceof PluginInfo) {
            $this->pluginInfo = new PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    public function loadJavascriptFiles()
    {
        if (strpos($_SERVER['REQUEST_URI'], '/account/register.php') === 0 && $this->isConfigured()) {
            echo '<script src="https://www.google.com/recaptcha/api.js" async></script>';
        }
    }

    public function loadCSSFiles()
    {
        if (strpos($_SERVER['REQUEST_URI'], '/account/register.php') === 0 && $this->isConfigured()) {
            echo '<link rel="stylesheet" type="text/css" href="'. $this->getThemePath() .'/css/style.css" />';
        }
    }

    public function addExternalScriptToTheWhitelist(array $params)
    {
        if (strpos($_SERVER['REQUEST_URI'], '/account/register.php') === 0 && $this->isConfigured()) {
            $params['whitelist_scripts'][] = 'https://www.google.com/recaptcha/';
            $params['whitelist_scripts'][] = 'https://www.gstatic.com/recaptcha/';
        }
    }

    public function addAdditionalFieldUserRegistration(array $params)
    {
        $request = $params['request'];
        if (! $request->getCurrentUser()->isSuperUser()) {
            try {
                $configuration = $this->getConfiguration();
            } catch (\Tuleap\Captcha\ConfigurationNotFoundException $ex) {
                return;
            }
            $site_key         = $configuration->getSiteKey();
            $presenter        = new Presenter($site_key);
            $renderer         = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates');
            $params['field'] .= $renderer->renderToString('user-registration', $presenter);
        }
    }

    public function checkCaptchaBeforeSubmission(array $params)
    {
        try {
            $configuration = $this->getConfiguration();
        } catch (\Tuleap\Captcha\ConfigurationNotFoundException $ex) {
            return;
        }
        $secret_key  = $configuration->getSecretKey();
        $request     = $params['request'];
        $challenge   = $request->get('g-recaptcha-response');
        $http_client = new Http_Client();

        $recaptcha_client = new \Tuleap\Captcha\Client($secret_key, $http_client);
        $is_captcha_valid = $recaptcha_client->verify($challenge, $request->getIPAddress());

        if (! $is_captcha_valid) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-captcha', 'Captcha is not valid')
            );
            $params['is_registration_valid'] = false;
        }
    }

    /**
     * @return \Tuleap\Captcha\Configuration
     * @throws \Tuleap\Captcha\ConfigurationNotFoundException
     */
    private function getConfiguration()
    {
        $configuration_retriever = new ConfigurationRetriever(new DataAccessObject());
        return $configuration_retriever->retrieve();
    }

    /**
     * @return bool
     */
    private function isConfigured()
    {
        try {
            $this->getConfiguration();
        } catch (\Tuleap\Captcha\ConfigurationNotFoundException $ex) {
            return false;
        }

        return true;
    }
}
