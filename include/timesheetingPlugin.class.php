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

use Tuleap\Timesheeting\Admin\AdminController;
use Tuleap\Timesheeting\Admin\AdminDao;
use Tuleap\Timesheeting\Admin\TimesheetingEnabler;
use Tuleap\Timesheeting\Fieldset\FieldsetPresenter;
use Tuleap\Timesheeting\TimesheetingPluginInfo;
use Tuleap\Timesheeting\Router;
use Tuleap\Tracker\Artifact\Event\GetAdditionalContent;

require_once 'autoload.php';
require_once 'constants.php';

class timesheetingPlugin extends Plugin
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_PROJECT);

        bindtextdomain('tuleap-timesheeting', __DIR__.'/../site-content');
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook('cssfile');

        if (defined('TRACKER_BASE_URL')) {
            $this->addHook(TRACKER_EVENT_FETCH_ADMIN_BUTTONS);
            $this->addHook(GetAdditionalContent::NAME);
        }

        return parent::getHooksAndCallbacks();
    }

    public function getPluginInfo() {
        if (! is_a($this->pluginInfo, 'TimesheetingPluginInfo')) {
            $this->pluginInfo = new TimesheetingPluginInfo($this);
        }

        return $this->pluginInfo;
    }

    public function getDependencies()
    {
        return array('tracker');
    }

    public function cssfile($params)
    {
        $include_tracker_css_file = false;
        EventManager::instance()->processEvent(
            TRACKER_EVENT_INCLUDE_CSS_FILE,
            array('include_tracker_css_file' => &$include_tracker_css_file)
        );
        // Only show the stylesheet if we're actually in the tracker pages.
        // This stops styles inadvertently clashing with the main site.
        if ($include_tracker_css_file) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }

    /**
     * @see TRACKER_EVENT_FETCH_ADMIN_BUTTONS
     */
    public function trackerEventFetchAdminButtons($params)
    {
        $url = '/plugins/timesheeting/?'. http_build_query(array(
                'tracker' => $params['tracker_id'],
                'action'  => 'admin-timesheeting'
        ));

        $params['items']['timesheeting'] = array(
            'url'         => $url,
            'short_title' => dgettext('tuleap-timesheeting', 'Timesheeting'),
            'title'       => dgettext('tuleap-timesheeting', 'Timesheeting'),
            'description' => dgettext('tuleap-timesheeting', 'Timesheeting for Tuleap artifacts'),
            'img'         => TIMESHEETING_BASE_URL . '/images/icon-timesheeting.png'
        );
    }

    public function process(Codendi_Request $request)
    {
        $tracker_factory = TrackerFactory::instance();
        $tracker_id      = $request->get('tracker');
        $tracker         = $tracker_factory->getTrackerById($tracker_id);

        if (! $tracker) {
            $this->redirectToTuleapHomepage();
        }

        $router = new Router(
            new AdminController(
                new TrackerManager(),
                new TimesheetingEnabler(new AdminDao()),
                new CSRFSynchronizerToken($tracker->getAdministrationUrl())
            )
        );

        $router->route($request, $tracker);
    }

    private function redirectToTuleapHomepage()
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            dgettext('tuleap-timesheeting', 'The request is not valid.')
        );

        $GLOBALS['Response']->redirect('/');
    }

    public function tracker_view_get_additional_content(GetAdditionalContent $event)
    {
        $renderer  = TemplateRendererFactory::build()->getRenderer(TIMESHEETING_TEMPLATE_DIR);
        $presenter = new FieldsetPresenter();

        $content = $renderer->renderToString(
            'fieldset',
            $presenter
        );

        $event->setContent($content);
    }
}
