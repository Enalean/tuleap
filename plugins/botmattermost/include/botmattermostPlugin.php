<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

declare(strict_types=1);

use FastRoute\RouteCollector;
use Tuleap\Admin\SiteAdministrationAddOption;
use Tuleap\Admin\SiteAdministrationPluginOption;
use Tuleap\BotMattermost\Administration\Project\CreateBotController;
use Tuleap\BotMattermost\Administration\Project\DeleteBotController;
use Tuleap\BotMattermost\Administration\Project\EditBotController;
use Tuleap\BotMattermost\Administration\Project\ListBotController;
use Tuleap\BotMattermost\Administration\Request\ParameterValidator;
use Tuleap\BotMattermost\Bot\BotCreator;
use Tuleap\BotMattermost\Bot\BotDao;
use Tuleap\BotMattermost\Bot\BotDeletor;
use Tuleap\BotMattermost\Bot\BotEditor;
use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\BotMattermost\Controller\AdminController;
use Tuleap\BotMattermost\Router;
use Tuleap\BurningParrotCompatiblePageEvent;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Navigation\NavigationDropdownItemPresenter;
use Tuleap\Project\Admin\Navigation\NavigationPresenter;
use Tuleap\Project\Admin\Navigation\NavigationPresenterBuilder;
use Tuleap\Project\Admin\Routing\AdministrationLayoutHelper;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;

require_once 'constants.php';
require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class BotMattermostPlugin extends Plugin
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);

        bindtextdomain('tuleap-botmattermost', __DIR__ . '/../site-content');

        $this->addHook(SiteAdministrationAddOption::NAME);
        $this->addHook(BurningParrotCompatiblePageEvent::NAME);
        $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);
        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(CollectRoutesEvent::NAME, 'defaultCollectRoutesEvent');
        $this->addHook(NavigationPresenter::NAME);
    }

    /**
     * @return Tuleap\BotMattermost\Plugin\PluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new Tuleap\BotMattermost\Plugin\PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function siteAdministrationAddOption(SiteAdministrationAddOption $site_administration_add_option): void
    {
        $site_administration_add_option->addPluginOption(
            SiteAdministrationPluginOption::withShortname(
                dgettext('tuleap-botmattermost', 'Bot Mattermost'),
                $this->getPluginPath() . '/admin/',
                'botmattermost'
            )
        );
    }

    public function burning_parrot_get_javascript_files($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath() . '/admin/') === 0) {
            $asset                        = $this->getIncludeAssets();
            $params['javascript_files'][] = $asset->getFileURL('modals.js');
        }
    }

    public function burning_parrot_get_stylesheets(array $params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath() . '/admin/') === 0) {
            $asset                   = $this->getIncludeAssets();
            $params['stylesheets'][] = $asset->getFileURL('botmattermost-style.css');
        }
    }

    public function burningParrotCompatiblePage(BurningParrotCompatiblePageEvent $event)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath() . '/admin/') === 0) {
            $event->setIsInBurningParrotCompatiblePage();
        }
    }

    public function routeAdmin(): \Tuleap\Request\DispatchableWithRequest
    {
        return new Router(
            new AdminController(
                new CSRFSynchronizerToken('/plugins/botmattermost/admin/'),
                new BotFactory(new BotDao()),
                $this->getBotDeletor(),
                $this->getBotEditor(),
                $this->getBotCreator()
            )
        );
    }

    public function collectProjectAdminNavigationItems(NavigationPresenter $presenter): void
    {
        $project_id = urlencode((string) $presenter->getProjectId());
        $html_url   = $this->getPluginPath() . "/project/$project_id/admin";
        $presenter->addDropdownItem(
            NavigationPresenterBuilder::OTHERS_ENTRY_SHORTNAME,
            new NavigationDropdownItemPresenter(
                dgettext('tuleap-botmattermost', 'Project Mattermost bots'),
                $html_url
            )
        );
    }

    public function defaultCollectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '/admin/[index.php]', $this->getRouteHandler('routeAdmin'));
            $r->get('/project/{project_id:\d+}/admin', $this->getRouteHandler('routeGetProjectAdmin'));
            $r->post('/bot/create', $this->getRouteHandler('routeCreateBot'));
            $r->post('/bot/{bot_id:\d+}/delete', $this->getRouteHandler('routeDeleteBot'));
            $r->post('/bot/{bot_id:\d+}/edit', $this->getRouteHandler('routeEditBot'));
        });
    }

    public function routeGetProjectAdmin(): DispatchableWithRequest
    {
        return new ListBotController(
            new BotFactory(new BotDao()),
            AdministrationLayoutHelper::buildSelf(),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates/project-admin'),
            $this->getIncludeAssets()
        );
    }

    public function routeDeleteBot(): DispatchableWithRequest
    {
        return new DeleteBotController(
            new BotFactory(
                new BotDao()
            ),
            $this->getBotDeletor()
        );
    }

    public function routeEditBot(): DispatchableWithRequest
    {
        return new EditBotController(
            new BotFactory(
                new BotDao()
            ),
            $this->getBotEditor()
        );
    }

    public function routeCreateBot(): DispatchableWithRequest
    {
        return new CreateBotController(
            $this->getBotCreator()
        );
    }

    private function getIncludeAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../frontend-assets/',
            '/assets/botmattermost'
        );
    }

    private function getBotDeletor(): BotDeletor
    {
        return new BotDeletor(
            new BotFactory(
                new BotDao()
            ),
            EventManager::instance(),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
        );
    }

    private function getBotEditor(): BotEditor
    {
        return new BotEditor(
            new BotFactory(
                new BotDao()
            ),
            new ParameterValidator()
        );
    }

    private function getBotCreator(): BotCreator
    {
        return new BotCreator(
            new BotFactory(
                new BotDao()
            ),
            new ParameterValidator()
        );
    }
}
