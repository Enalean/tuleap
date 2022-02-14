<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

require_once __DIR__  . '/../vendor/autoload.php';
require_once 'constants.php';

use FastRoute\RouteCollector;
use Tuleap\Layout\IncludeAssets;
use Tuleap\MyTuleapContactSupport\Plugin\Info;
use Tuleap\MyTuleapContactSupport\ContactSupportFormController;
use Tuleap\MyTuleapContactSupport\SendMailSupportController;
use Tuleap\Request\CollectRoutesEvent;

class mytuleap_contact_supportPlugin extends Plugin // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function __construct($id)
    {
        parent::__construct($id);

        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-mytuleap_contact_support', __DIR__ . '/../site-content');
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook('cssfile');
        $this->addHook('javascript_file');
        $this->addHook('site_help');
        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);
        $this->addHook(CollectRoutesEvent::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new Info($this);
        }

        return $this->pluginInfo;
    }

    private function getContactSupportFormController()
    {
        return new ContactSupportFormController(
            $this->getRenderer()
        );
    }

    private function getSendMailSupportController()
    {
        $contact_support_email = $this->getPluginInfo()->getPropertyValueForName('contact_support_email');
        if (! $contact_support_email) {
            $contact_support_email = 'support@mytuleap.com';
        }

        return new SendMailSupportController(
            $this->getRenderer(),
            $contact_support_email
        );
    }
    private function getRenderer()
    {
        $template_path = MYTULEAP_CONTACT_SUPPORT_BASE_DIR . '/templates/';

        return TemplateRendererFactory::build()->getRenderer($template_path);
    }

    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup('/plugins/mytuleap_contact_support', function (RouteCollector $r) {
                $r->post(
                    '/send-message',
                    $this->getRouteHandler('routePost')
                );
                $r->get(
                    '/get-modal-content',
                    $this->getRouteHandler('routeGet')
                );
        });
    }

    public function routePost(): SendMailSupportController
    {
        return $this->getSendMailSupportController();
    }

    public function routeGet(): ContactSupportFormController
    {
        return $this->getContactSupportFormController();
    }

    public function cssfile()
    {
        $asset = $this->getIncludeAssets();
        echo '<link rel="stylesheet" type="text/css" href="' . $asset->getFileURL('style-flamingparrot.css') . '" />';
    }

    public function javascript_file($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $layout = $params['layout'];
        assert($layout instanceof \Tuleap\Layout\BaseLayout);
        $asset = $this->getIncludeAssets();
        $layout->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($asset, 'modal-flaming-parrot.js'));
    }

    public function site_help($params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['extra_content'] = $this->getContactSupportFormController()->getFormContent();
    }

    public function burning_parrot_get_javascript_files(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $asset                        = $this->getIncludeAssets();
        $params['javascript_files'][] = $asset->getFileURL('modal-burning-parrot.js');

        if (strpos($_SERVER['REQUEST_URI'], '/help/') === 0) {
            $params['javascript_files'][] = $asset->getFileURL('help-page.js');
        }
    }

    public function burning_parrot_get_stylesheets(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $asset                   = $this->getIncludeAssets();
        $params['stylesheets'][] = $asset->getFileURL('style-burningparrot.css');
    }

    private function getIncludeAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../frontend-assets/',
            '/assets/mytuleap_contact_support'
        );
    }
}
