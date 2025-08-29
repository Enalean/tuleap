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
use Tuleap\Admin\SiteAdministrationAddOption;
use Tuleap\Admin\SiteAdministrationPluginOption;
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
use Tuleap\User\Account\Register\AddAdditionalFieldUserRegistration;
use Tuleap\User\Account\Register\BeforeRegisterFormValidationEvent;
use Tuleap\User\Account\Register\RegisterFormValidationIssue;

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../vendor/autoload.php';

class captchaPlugin extends Plugin // @codingStandardsIgnoreLine
{
    public function __construct($id)
    {
        parent::__construct($id);

        bindtextdomain('tuleap-captcha', __DIR__ . '/../site-content');

        $this->setScope(self::SCOPE_SYSTEM);
    }

    #[Override]
    public function getPluginInfo(): PluginInfo
    {
        if (! $this->pluginInfo instanceof PluginInfo) {
            $this->pluginInfo = new PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::CONTENT_SECURITY_POLICY_SCRIPT_WHITELIST)]
    public function addExternalScriptToTheWhitelist(array $params): void
    {
        if (strpos($_SERVER['REQUEST_URI'], '/account/register.php') === 0 && $this->isConfigured()) {
            $params['whitelist_scripts'][] = 'https://www.google.com/recaptcha/';
            $params['whitelist_scripts'][] = 'https://www.gstatic.com/recaptcha/';
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function addAdditionalFieldUserRegistration(AddAdditionalFieldUserRegistration $event): void
    {
        if (! $event->getRequest()->getCurrentUser()->isSuperUser()) {
            try {
                $configuration = $this->getConfiguration();
            } catch (\Tuleap\Captcha\ConfigurationNotFoundException $ex) {
                return;
            }
            $site_key  = $configuration->getSiteKey();
            $presenter = new Presenter(
                $site_key,
                $event->validation_issue?->getFieldError('captcha')
            );
            $renderer  = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates');
            $event->appendAdditionalFieldsInHtml($renderer->renderToString('user-registration', $presenter));
            $event->getLayout()->includeFooterJavascriptFile('https://www.google.com/recaptcha/api.js');
            $event->getLayout()->addCssAsset(
                new \Tuleap\Layout\CssAssetWithoutVariantDeclinaisons(
                    new IncludeAssets(
                        __DIR__ . '/../frontend-assets',
                        '/assets/captcha'
                    ),
                    'style'
                )
            );
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function beforeRegisterFormValidationEvent(BeforeRegisterFormValidationEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->getCurrentUser()->isSuperUser()) {
            return;
        }

        try {
            $configuration = $this->getConfiguration();
        } catch (\Tuleap\Captcha\ConfigurationNotFoundException $ex) {
            return;
        }
        $secret_key = $configuration->getSecretKey();
        $challenge  = $request->get('g-recaptcha-response');

        $recaptcha_client = new \Tuleap\Captcha\Client(
            $secret_key,
            HttpClientFactory::createClient(),
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory()
        );
        $is_captcha_valid = $recaptcha_client->verify($challenge, $request->getIPAddress());

        if (! $is_captcha_valid) {
            $event->addValidationError(
                RegisterFormValidationIssue::fromFieldName(
                    'captcha',
                    dgettext('tuleap-captcha', 'We have not been able to assert that you are not a robot, please try again'),
                )
            );
        }
    }

    /**
     * @throws \Tuleap\Captcha\ConfigurationNotFoundException
     */
    private function getConfiguration(): Configuration
    {
        return new ConfigurationRetriever(new DataAccessObject())->retrieve();
    }

    private function isConfigured(): bool
    {
        try {
            $this->getConfiguration();
        } catch (\Tuleap\Captcha\ConfigurationNotFoundException $ex) {
            return false;
        }

        return true;
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function siteAdministrationAddOption(SiteAdministrationAddOption $site_administration_add_option): void
    {
        $site_administration_add_option->addPluginOption(
            SiteAdministrationPluginOption::build($this->getPluginInfo()->getPluginDescriptor()->getFullName(), CAPTCHA_BASE_URL . '/admin/')
        );
    }

    public function routeGetAdmin(): DispatchableWithRequest
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

    public function routePostAdmin(): DispatchableWithRequest
    {
        return new UpdateController(
            new ConfigurationSaver(new DataAccessObject())
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (RouteCollector $r) {
            $r->get('/admin/[index.php]', $this->getRouteHandler('routeGetAdmin'));
            $r->post('/admin/[index.php]', $this->getRouteHandler('routePostAdmin'));
        });
    }
}
