<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
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
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\ForumML;
use Tuleap\ForumML\CurrentListBreadcrumbCollectionBuilder;
use Tuleap\ForumML\Plugin\MailTransportConfigurationPluginInstallRequirement;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Mail\Transport\MailTransportBuilder;
use Tuleap\MailingList\MailingListPresenterBuilder;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\SystemEvent\RootDailyStartEvent;

require_once __DIR__ . '/../vendor/autoload.php';

class ForumMLPlugin extends Plugin //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public const SEARCH_TYPE = 'mail';

    public function __construct($id)
    {
        parent::__construct($id);
        bindtextdomain('tuleap-forumml', __DIR__ . '/../site-content');

        $this->addHook('browse_archives', 'forumml_browse_archives');
        $this->addHook('cssfile', 'cssFile');
        $this->addHook('javascript_file', 'jsFile');
        $this->addHook(RootDailyStartEvent::NAME);

        // Search
        $this->addHook(Event::SEARCH_TYPE);
        $this->addHook(Event::SEARCH_TYPES_PRESENTERS);
        $this->addHook(Event::LAYOUT_SEARCH_ENTRY);

        // Stat plugin
        $this->addHook('plugin_statistics_disk_usage_collect_project');
        $this->addHook('plugin_statistics_disk_usage_service_label');
        $this->addHook('plugin_statistics_color');

        $this->addHook(CollectRoutesEvent::NAME);

        // Set ForumML plugin scope to 'Projects' wide
        $this->setScope(Plugin::SCOPE_PROJECT);
        $this->allowedForProject = [];
    }

    public function getPluginInfo()
    {
        if (! $this->pluginInfo instanceof \ForumMLPluginInfo) {
            require_once('ForumMLPluginInfo.class.php');
            $this->pluginInfo = new ForumMLPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function layout_search_entry($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $group_id = null;
        $list_id  = null;

        preg_match('%/plugins/forumml/list/(?P<list_id>\d+)/%', $_SERVER['REQUEST_URI'], $matches);
        if (isset($matches['list_id'])) {
            $row = (new ForumML\ThreadsDao())->searchActiveList((int) $matches['list_id']);
            if ($row) {
                $group_id = $row['group_id'];
                $list_id  = $matches['list_id'];
            }
        } else {
            $request  = HTTPRequest::instance();
            $group_id = (int) $request->get('group_id');
            $list_id  = (int) $request->get('list');
        }

        if ($group_id && $list_id) {
            $params['search_entries'][] = [
                'value'    => 'mail',
                'selected' => true,
            ];
            $params['hidden_fields'][]  = [
                'name'  => 'list',
                'value' => $list_id,
            ];
            $params['hidden_fields'][]  = [
                'name'  => 'group_id',
                'value' => $group_id,
            ];
        }
    }

    public function forumml_browse_archives($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isAllowed((int) $params['project']->getID())) {
            $params['html'] = ForumML\Threads\ThreadsController::getUrl((int) $params['list_id']);
        }
    }

    public function cssFile($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $asset = $this->getAssets()->getFileURL('style.css');
            echo '<link rel="stylesheet" type="text/css" href="' . $asset . '" />' . "\n";
        }
    }

    public function jsFile($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $layout = $params['layout'];
            assert($layout instanceof \Tuleap\Layout\BaseLayout);
            $layout->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($this->getAssets(), 'forumml.js'));
        }
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../frontend-assets',
            '/assets/forumml'
        );
    }

    /**
     * @see Event::SEARCH_TYPES_PRESENTERS
     */
    public function search_types_presenters($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isAllowed($params['project']->getId()) && ! $params['project']->isError()) {
            $lists = [];
            $dao   = new MailingListDao();
            foreach ($dao->searchByProject($params['project']->getId()) as $row) {
                $lists[] = [
                    'url'              => $this->getSearchUrl((int) $row['group_list_id'], (string) $params['words']),
                    'title'            => $row['list_name'],
                    'extra-parameters' => false,
                ];
            }

            if (! $lists) {
                return;
            }

            $params['project_presenters'][] = new Search_SearchTypePresenter(
                self::SEARCH_TYPE,
                dgettext('tuleap-forumml', 'Mailing list'),
                $lists
            );
        }
    }

    public function search_type($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $query = $params['query'];

        if ($query->getTypeOfSearch() == self::SEARCH_TYPE) {
            $list_id = (int) HTTPRequest::instance()->get('list');
            util_return_to($this->getSearchUrl($list_id, (string) $query->getWords()));
        }
    }

    private function getSearchUrl(int $list_id, string $words): string
    {
        return ForumML\Threads\ThreadsController::getSearchUrl($list_id, $words);
    }

    /**
     * Hook to collect forumml disk size usage per project
     *
     * @param array $params
     */
    public function plugin_statistics_disk_usage_collect_project($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $start       = microtime(true);
        $project_row = $params['project_row'];
        $root        = $this->getPluginInfo()->getPropertyValueForName('forumml_dir');
        $path        = $root . '/' . strtolower($project_row['unix_group_name']);

        $sql = 'SELECT group_list_id, list_name FROM mail_group_list WHERE group_id = ' . $project_row['group_id'];
        $res = db_query($sql);
        $sum = 0;
        while ($row = db_fetch_array($res)) {
            $sum += $params['DiskUsageManager']->getDirSize($path . '/' . $row['list_name'] . '/');
            $sum += $params['DiskUsageManager']->getDirSize($path . '/' . $row['group_list_id'] . '/');
        }

        $dao = $params['DiskUsageManager']->_getDao();
        $dao->addGroup($project_row['group_id'], 'plugin_forumml', $sum, $params['collect_date']->getTimestamp());

        $end  = microtime(true);
        $time = $end - $start;

        if (! isset($params['time_to_collect']['plugin_forumml'])) {
            $params['time_to_collect']['plugin_forumml'] = 0;
        }

        $params['time_to_collect']['plugin_forumml'] += $time;
    }

    /**
     * Hook to list forumml in the list of serices managed by disk stats
     *
     * @param array $params
     */
    public function plugin_statistics_disk_usage_service_label($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['services']['plugin_forumml'] = 'ForumML';
    }

    /**
     * Hook to choose the color of the plugin in the graph
     *
     * @param array $params
     */
    public function plugin_statistics_color($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['service'] == 'plugin_forumml') {
            $params['color'] = 'lemonchiffon3';
        }
    }

    public function routeGetMessages(): DispatchableWithRequest
    {
        return new ForumML\ListMailsController($this);
    }

    public function routeSendMail(): DispatchableWithRequest
    {
        return new ForumML\SendMailController($this);
    }

    public function routeWriteMail(): DispatchableWithRequest
    {
        return new ForumML\WriteMailController();
    }

    public function routeOutputAttachment(): DispatchableWithRequest
    {
        return new ForumML\OutputAttachmentController($this);
    }

    public function routeThreads(): ForumML\Threads\ThreadsController
    {
        return new ForumML\Threads\ThreadsController(
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates/'),
            $this->getAssets(),
            new ForumML\Threads\ThreadsPresenterBuilder(
                new ForumML\ThreadsDao(),
                new TlpRelativeDatePresenterBuilder(),
                UserManager::instance(),
                UserHelper::instance(),
            ),
            new CurrentListBreadcrumbCollectionBuilder(
                new MailingListPresenterBuilder(EventManager::instance()),
            ),
            new ForumML\ListInfoFromVariablesProvider(
                $this,
                ProjectManager::instance(),
                new ForumML\ThreadsDao(),
                new System_Command(),
            ),
        );
    }

    public function routeOneThread(): ForumML\OneThread\OneThreadController
    {
        return new ForumML\OneThread\OneThreadController(
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates/'),
            $this->getAssets(),
            new ForumML\OneThread\OneThreadPresenterBuilder(
                new ForumML\ThreadsDao(),
                new ForumML\OneThread\MessageInfoToMessagePresenterConvertor(
                    UserHelper::instance(),
                    new TlpRelativeDatePresenterBuilder(),
                    new ForumML\ThreadsDao(),
                ),
                UserManager::instance(),
            ),
            new CurrentListBreadcrumbCollectionBuilder(
                new MailingListPresenterBuilder(EventManager::instance()),
            ),
            new ForumML\ListInfoFromVariablesProvider(
                $this,
                ProjectManager::instance(),
                new ForumML\ThreadsDao(),
                new System_Command(),
            ),
        );
    }

    public function collectRoutesEvent(CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (RouteCollector $r) {
            $r->get('/list/{id:\d+}/threads', $this->getRouteHandler('routeThreads'));
            $r->get('/list/{id:\d+}/threads/{thread_id:\d+}', $this->getRouteHandler('routeOneThread'));
            $r->get('/list/{id:\d+}/threads/upload.php', $this->getRouteHandler('routeOutputAttachment'));

            $r->get('/message.php', $this->getRouteHandler('routeGetMessages'));
            $r->get('/upload.php', $this->getRouteHandler('routeOutputAttachment'));
            $r->get('/index.php', $this->getRouteHandler('routeWriteMail'));
            $r->post('/index.php', $this->getRouteHandler('routeSendMail'));
        });
    }

    public function rootDailyStart(RootDailyStartEvent $event)
    {
        try {
            $tmp_watch = new \Tuleap\TmpWatch((string) $this->getPluginInfo()->getPropertyValueForName('forumml_tmp'), 24);
            $tmp_watch->run();
        } catch (Exception $exception) {
            $event->getLogger()->error('ForumML root_daily_start ' . $exception::class . ': ' . $exception->getMessage(), ['exception' => $exception]);
            $event->addWarning($exception->getMessage());
        }
    }

    public function getInstallRequirements(): array
    {
        return [new MailTransportConfigurationPluginInstallRequirement(MailTransportBuilder::getPlatformMailConfiguration())];
    }
}
