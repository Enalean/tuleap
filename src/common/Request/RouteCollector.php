<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Request;

use EventManager;
use FastRoute;
use Tuleap\Admin\ProjectCreation\ProjectCategoriesDisplayController;
use Tuleap\Admin\ProjectCreation\ProjectFieldsDisplayController;
use Tuleap\Admin\ProjectCreation\ProjectFieldsUpdateController;
use Tuleap\Admin\ProjectCreation\WebhooksDisplayController;
use Tuleap\Admin\ProjectCreation\WebhooksUpdateController;
use Tuleap\Admin\ProjectCreationModerationDisplayController;
use Tuleap\Admin\ProjectCreationModerationUpdateController;
use Tuleap\Admin\ProjectTemplatesController;
use Tuleap\Instrument\MetricsController;
use Tuleap\layout\LegacySiteHomePageController;
use Tuleap\Layout\SiteHomepageController;
use Tuleap\Password\Administration\PasswordPolicyDisplayController;
use Tuleap\Password\Administration\PasswordPolicyUpdateController;
use Tuleap\Password\Configuration\PasswordConfigurationDAO;
use Tuleap\Password\Configuration\PasswordConfigurationRetriever;
use Tuleap\Password\Configuration\PasswordConfigurationSaver;
use Tuleap\Trove\TroveCatListController;

class RouteCollector
{
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(EventManager $event_manager)
    {
        $this->event_manager = $event_manager;
    }

    public function collect(FastRoute\RouteCollector $r)
    {
        $r->get('/', function () {
            $dao = new \Admin_Homepage_Dao();
            if ($dao->isStandardHomepageUsed()) {
                return new SiteHomepageController();
            }
            return new LegacySiteHomePageController();
        });
        $r->addRoute(['GET', 'POST'], '/projects/{name}[/]', function () {
            return new \Tuleap\Project\Home();
        });
        $r->addGroup('/admin', function (FastRoute\RouteCollector $r) {
            $r->get('/password_policy/', function () {
                return new PasswordPolicyDisplayController(
                    new \Tuleap\Admin\AdminPageRenderer,
                    \TemplateRendererFactory::build(),
                    new PasswordConfigurationRetriever(new PasswordConfigurationDAO)
                );
            });
            $r->post('/password_policy/', function () {
                return new PasswordPolicyUpdateController(
                    new PasswordConfigurationSaver(new PasswordConfigurationDAO)
                );
            });
            $r->get('/project-creation/moderation', function () {
                return new ProjectCreationModerationDisplayController();
            });
            $r->post('/project-creation/moderation', function () {
                return new ProjectCreationModerationUpdateController();
            });
            $r->get('/project-creation/templates', function () {
                return new ProjectTemplatesController();
            });
            $r->get('/project-creation/webhooks', function () {
                return new WebhooksDisplayController();
            });
            $r->post('/project-creation/webhooks', function () {
                return new WebhooksUpdateController();
            });
            $r->get('/project-creation/fields', function () {
                return new ProjectFieldsDisplayController();
            });
            $r->post('/project-creation/fields', function () {
                return new ProjectFieldsUpdateController();
            });
            $r->get('/project-creation/categories', function () {
                return new ProjectCategoriesDisplayController();
            });
            $r->post('/project-creation/categories', function () {
                return new TroveCatListController();
            });
        });

        $collect_routes = new CollectRoutesEvent($r);
        $this->event_manager->processEvent($collect_routes);
    }
}
