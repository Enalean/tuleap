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

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Bugzilla\Administration\Controller;
use Tuleap\Bugzilla\Administration\Router;
use Tuleap\Bugzilla\Plugin\Info;

require_once 'constants.php';

class bugzilla_referencePlugin extends Plugin
{
    public function __construct($id)
    {
        parent::__construct($id);

        bindtextdomain('tuleap-bugzilla_reference', BUGZILLA_REFERENCE_BASE_DIR. '/site-content');

        $this->addHook('site_admin_option_hook', 'addSiteAdministrationOptionHook');
        $this->addHook(Event::IS_IN_SITEADMIN, 'isInSiteAdmin');
    }

    /**
     * @return PluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo instanceof Info) {
            $this->pluginInfo = new Info($this);
        }

        return $this->pluginInfo;
    }

    public function addSiteAdministrationOptionHook(array $params)
    {
        $params['plugins'][] = array(
            'label' => $this->getPluginInfo()->getPluginDescriptor()->getFullName(),
            'href'  => BUGZILLA_REFERENCE_BASE_URL . '/admin/'
        );
    }

    public function isInSiteAdmin(array $params)
    {
        if (strpos($_SERVER['REQUEST_URI'], BUGZILLA_REFERENCE_BASE_URL . '/admin/') === 0) {
            $params['is_in_siteadmin'] = true;
        }
    }

    public function processAdmin(HTTPRequest $request)
    {
        $controller = new Controller(new AdminPageRenderer());
        $router     = new Router($controller);
        $router->route($request);
    }
}
