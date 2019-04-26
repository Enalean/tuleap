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

require_once 'autoload.php';
require_once 'constants.php';

use Tuleap\Layout\IncludeAssets;
use Tuleap\MyTuleapContactSupport\Plugin\Info;
use Tuleap\MyTuleapContactSupport\Router;
use Tuleap\MyTuleapContactSupport\ContactSupportController;

class mytuleap_contact_supportPlugin extends Plugin
{
    public function __construct($id)
    {
        parent::__construct($id);

        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-mytuleap_contact_support', __DIR__.'/../site-content');
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook('cssfile');
        $this->addHook('javascript_file');
        $this->addHook('site_help');
        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);

        return parent::getHooksAndCallbacks();
    }

    public function getPluginInfo()
    {
        if (!$this->pluginInfo) {
            $this->pluginInfo = new Info($this);
        }

        return $this->pluginInfo;
    }

    private function getRouter()
    {
        return new Router(
            $this->getContactSupportController()
        );
    }

    private function getContactSupportController()
    {
        return new ContactSupportController(
            $this->getRenderer()
        );
    }

    private function getRenderer()
    {
        $template_path = MYTULEAP_CONTACT_SUPPORT_BASE_DIR . '/templates/';

        return TemplateRendererFactory::build()->getRenderer($template_path);
    }

    public function process(HTTPRequest $request)
    {
        $this->getRouter()->route($request);
    }

    public function cssfile()
    {
        $asset = $this->getIncludeAssets();
        echo '<link rel="stylesheet" type="text/css" href="'. $asset->getFileURL('style-flamingparrot.css') .'" />';
    }

    public function javascript_file($params)
    {
        $asset = $this->getIncludeAssets();
        echo $asset->getHTMLSnippet('modal-flaming-parrot.js');
    }

    public function site_help($params)
    {
        $params['extra_content'] = $this->getContactSupportController()->getFormContent();
    }

    public function burning_parrot_get_javascript_files(array $params)
    {
        $asset = $this->getIncludeAssets();
        $params['javascript_files'][] = $asset->getFileURL('modal-burning-parrot.js');

        if (strpos($_SERVER['REQUEST_URI'], '/help/') === 0) {
            $params['javascript_files'][] = $asset->getFileURL('help-page.js');
        }
    }

    public function burning_parrot_get_stylesheets(array $params)
    {
        $asset                   = $this->getIncludeAssets();
        $variant                 = $params['variant'];
        $params['stylesheets'][] = $asset->getFileURL('style-burningparrot-' . $variant->getName() . '.css');
    }

    private function getIncludeAssets() : IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/mytuleap_contact_support/',
            '/assets/mytuleap_contact_support'
        );
    }
}
