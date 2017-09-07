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

require_once 'autoload.php';
require_once 'constants.php';

use Tuleap\Layout\IncludeAssets;
use Tuleap\MyTuleapContactSupport\Plugin\Info;
use Tuleap\MyTuleapContactSupport\Router;
use Tuleap\MyTuleapContactSupport\ContactSupportController;
use Tuleap\MyTuleapContactSupport\Presenter\HomepagePresenter;

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
            new ContactSupportController(
                $this->getRenderer()
            )
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
        if (! UserManager::instance()->getCurrentUser()->isAnonymous()) {
            echo '<link rel="stylesheet" type="text/css" href="'. $this->getThemePath() .'/css/style.css" />';
        }
    }

    public function javascript_file($params)
    {
        if (! UserManager::instance()->getCurrentUser()->isAnonymous()) {
            echo '<script type="text/javascript" src="'.$this->getPluginPath().'/js/modal.js"></script>';
            echo '<script type="text/javascript" src="'.$this->getPluginPath().'/js/modal-flaming-parrot.js"></script>';
        }
    }

    public function burning_parrot_get_javascript_files(array $params)
    {
        if (! UserManager::instance()->getCurrentUser()->isAnonymous()) {
            $params['javascript_files'][] = $this->getPluginPath().'/js/modal.js';
            $params['javascript_files'][] = $this->getPluginPath().'/js/modal-burning-parrot.js';
        }
    }

    public function burning_parrot_get_stylesheets(array $params)
    {
        if (! UserManager::instance()->getCurrentUser()->isAnonymous()) {
            $variant = $params['variant'];
            $params['stylesheets'][] = $this->getThemePath() .'/css/style-'. $variant->getName() .'.css';
        }
    }
}
