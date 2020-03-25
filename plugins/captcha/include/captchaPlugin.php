<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

use FastRoute\RouteCollector;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\BurningParrotCompatiblePageEvent;
use Tuleap\Captcha\Administration\DisplayController;
use Tuleap\Captcha\Administration\UpdateController;
use Tuleap\Captcha\Configuration;
use Tuleap\Captcha\ConfigurationNotFoundException;
use Tuleap\Captcha\ConfigurationRetriever;
use Tuleap\Captcha\ConfigurationSaver;
use Tuleap\Captcha\DataAccessObject;
use Tuleap\Captcha\Plugin\Info as PluginInfo;
use Tuleap\Captcha\Registration\Presenter;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../vendor/autoload.php';

class captchaPlugin extends Plugin // @codingStandardsIgnoreLine
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
        $this->addHook('site_admin_option_hook', 'addSiteAdministrationOptionHook');
        $this->addHook(BurningParrotCompatiblePageEvent::NAME);
        $this->addHook(CollectRoutesEvent::NAME);
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
            $assets = new IncludeAssets(
                __DIR__ . '/../../../src/www/assets/captcha',
                '/assets/captcha'
            );
            echo '<link rel="stylesheet" type="text/css" href="' . $assets->getFileURL('style.css') . '" />';
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
        $request = $params['request'];
        \assert($request instanceof HTTPRequest);
        if ($request->getCurrentUser()->isSuperUser()) {
            return;
        }

        try {
            $configuration = $this->getConfiguration();
        } catch (\Tuleap\Captcha\ConfigurationNotFoundException $ex) {
            return;
        }
        $secret_key  = $configuration->getSecretKey();
        $challenge   = $request->get('g-recaptcha-response');

        $recaptcha_client = new \Tuleap\Captcha\Client(
            $secret_key,
            HttpClientFactory::createClient(),
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory()
        );
        $is_captcha_valid = $recaptcha_client->verify($challenge, $request->getIPAddress());

        if (! $is_captcha_valid) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-captcha', 'We have not been able to assert that you are not a robot, please try again')
            );
            $params['is_registration_valid'] = false;
        }
    }

    /**
     * @return Configuration
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

    public function addSiteAdministrationOptionHook(array $params)
    {
        $params['plugins'][] = array(
            'label' => $this->getPluginInfo()->getPluginDescriptor()->getFullName(),
            'href'  => CAPTCHA_BASE_URL . '/admin/'
        );
    }

    public function burningParrotCompatiblePage(BurningParrotCompatiblePageEvent $event)
    {
        if (strpos($_SERVER['REQUEST_URI'], CAPTCHA_BASE_URL . '/admin/') === 0) {
            $event->setIsInBurningParrotCompatiblePage();
        }
    }

    public function routeGetAdmin() : DispatchableWithRequest
    {
        try {
            $configuration = $this->getConfiguration();
        } catch (ConfigurationNotFoundException $ex) {
            $configuration = new Configuration('', '');
        }
        return new DisplayController(
            $configuration,
            new AdminPageRenderer()
        );
    }

    public function routePostAdmin() : DispatchableWithRequest
    {
        return new UpdateController(
            new ConfigurationSaver(new DataAccessObject())
        );
    }

    public function collectRoutesEvent(CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (RouteCollector $r) {
            $r->get('/admin/[index.php]', $this->getRouteHandler('routeGetAdmin'));
            $r->post('/admin/[index.php]', $this->getRouteHandler('routePostAdmin'));
        });
    }
}
