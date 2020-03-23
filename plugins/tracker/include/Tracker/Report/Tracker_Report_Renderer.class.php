<?php
/**
 * Copyright (c) Enalean 2017 - Present. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Dashboard\Project\ProjectDashboardDao;
use Tuleap\Dashboard\Project\ProjectDashboardRetriever;
use Tuleap\Dashboard\User\UserDashboardDao;
use Tuleap\Dashboard\User\UserDashboardRetriever;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Tracker\Report\WidgetAddToDashboardDropdownBuilder;
use Tuleap\Tracker\Widget\WidgetWithAssetDependencies;
use Tuleap\Widget\WidgetFactory;

abstract class Tracker_Report_Renderer implements WidgetWithAssetDependencies
{

    public $id;

    /**
     * @var Tracker_Report
     */
    public $report;
    public $name;
    public $description;
    public $rank;

    /**
     * A table renderer. This is the legacy display of the results
     */
    public const TABLE = 'table';

    /**
     * A "Board" renderer. Display artifacts grouped by columns.
     */
    public const BOARD = 'board';

    /**
     * Constructor
     *
     * @param int $id the id of the renderer
     * @param Tracker_Report $report the id of the report
     * @param string $name the name of the renderer
     * @param string $description the description of the renderer
     * @param int $rank the rank
     */
    public function __construct($id, $report, $name, $description, $rank)
    {
        $this->id          = $id;
        $this->report      = $report;
        $this->name        = $name;
        $this->description = $description;
        $this->rank        = $rank;
    }

    /**
     * Return the id of the renderer
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    abstract public function getIcon();

    /**
     * Delete the renderer
     */
    abstract public function delete();

    /**
     * Fetch content of the renderer
     *
     * @param array   $matching_ids
     * @param Request $request
     * @param bool    $report_can_be_modified
     *
     * @return string
     */
    abstract public function fetch($matching_ids, $request, $report_can_be_modified, PFUser $user);

    /**
     * Process the request
     * @param Request $request
     */
    abstract public function processRequest(TrackerManager $tracker_manager, $request, $current_user);

    /**
     * Fetch content to be displayed in widget
     */
    abstract public function fetchWidget(PFUser $user);

    /**
     * Returns the type of this renderer
     */
    abstract public function getType();

    abstract public function initiateSession();
    /**
     * Update the renderer
     *
     * @return bool true if success, false if failure
     */
    abstract public function update();

    /**
     * Finishes import by saving specific properties
     *
     * @param Tracker_Report_Renderer $renderer containig the parameters to save
     */
    abstract public function afterSaveObject(Tracker_Report_Renderer $renderer);

    public function process(TrackerManager $tracker_manager, $request, $current_user)
    {
        $this->processRequest($tracker_manager, $request, $current_user);
        $this->afterProcessRequest($tracker_manager, $request, $current_user);
    }

    public function afterProcessRequest(TrackerManager $tracker_manager, $request, $current_user)
    {
        if (!$request->isAjax()) {
            $params = array(
                'report'   => $this->report->id,
                'renderer' => $this->id
            );
            if ($request->existAndNonEmpty('pv')) {
                $params['pv'] = (int) $request->get('pv');
            }
            $GLOBALS['Response']->redirect('?' . http_build_query($params));
        }
    }

    /**
     * Get the item of the menu options.
     *
     * If no items is returned, the menu won't be displayed.
     *
     * @return array of 'item_key' => {url: '', icon: '', label: ''}
     */
    public function getOptionsMenuItems()
    {
        $items = array(
            'printer_version' => '<div class="btn-group"><a class="btn btn-mini" href="' . TRACKER_BASE_URL . '/?' . http_build_query(
                array(
                    'report'   => $this->report->id,
                    'renderer' => $this->id,
                    'pv'       => 1,
                )
            ) . '"><i class="fa fa-print"></i> ' . $GLOBALS['Language']->getText('global', 'printer_version') . '</a></div>'
        );
        $this->addDashboardButtons($items);

        return $items;
    }

    private function addDashboardButtons(array &$items)
    {
        $user = UserManager::instance()->getCurrentUser();
        if (! $this->canAddToDashboard($user)) {
            return;
        }

        $widget_factory = new WidgetFactory(
            UserManager::instance(),
            new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
            EventManager::instance()
        );

        $project           = $this->report->getTracker()->getProject();
        $widget_dao        = new DashboardWidgetDao($widget_factory);
        $presenter_builder = new WidgetAddToDashboardDropdownBuilder(
            new UserDashboardRetriever(
                new UserDashboardDao($widget_dao)
            ),
            new ProjectDashboardRetriever(new ProjectDashboardDao($widget_dao))
        );

        $html = $this->getTemplateRenderer()->renderToString(
            'add-to-dashboard-dropdown',
            $presenter_builder->build($user, $project, $this)
        );

        $items = array('add_to_dashboard' => $html) + $items;
    }

    private function getTemplateRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR . '/report');
    }

    private function canAddToDashboard($user)
    {
        return $this->id > 0
            && (!isset($this->report_session) || !$this->report_session->hasChanged())
            && $user->isLoggedIn();
    }

    /**
     * Create a renderer - add in db
     *
     * @return bool true if success, false if failure
     */
    abstract public function create();

    /**
     * Duplicate the renderer
     */
    abstract public function duplicate($from_report_id, $field_mapping);

    /**
     * Display a link to let the user go back to report
     * Main usage is in widget
     *
     * @see fetchLinkGoTo
     *
     * @return string html
     */
    public function fetchWidgetGoToReport()
    {
        return $this->fetchLinkGoTo('[' . $GLOBALS['Language']->getText('plugin_tracker_report_widget', 'go_to_report') . ']');
    }

    /**
     * Display a link to let the user go to the tracker
     * Used in ArtifactLink
     *
     * @see fetchLinkGoTo
     *
     * @return string html
     */
    public function fetchArtifactLinkGoToTracker()
    {
        $html = '';
        $html .= '<div class="tracker-form-element-artifactlink-gototracker">';
        $html .=  $this->fetchLinkGoTo($GLOBALS['Language']->getText('plugin_tracker_artifactlink', 'go_to_tracker'), array('target' => '_blank', 'rel' => 'noreferrer'));
        $html .= '</div>';
        return $html;
    }

    /**
     * Display a link to let the user go to the tracker
     *
     * @param string $msg A sanitized string to display as a link
     *
     * @return string html
     */
    protected function fetchLinkGoTo($msg, $params = array())
    {
        $html = '';
        $html .= '<a href="' . TRACKER_BASE_URL . '/?' . http_build_query(
            array(
                'report'   => $this->report->id,
                'renderer' => $this->id
            )
        );
        $html .= '"';
        foreach ($params as $key => $value) {
            $html .= ' ' . $key . '="' . $value . '"';
        }
        $html .= '>' . $msg . '</a>';
        return $html;
    }


    /**
     * Transforms Tracker_Renderer into a SimpleXMLElement
     *
     * @param SimpleXMLElement $root the node to which the renderer is attached (passed by reference)
     */
    public function exportToXml(SimpleXMLElement $root, array $xmlMapping)
    {
        $root->addAttribute('ID', 'R' . $this->id);
        $root->addAttribute('type', $this->getType());
        $root->addAttribute('rank', $this->rank);
        $cdata = new XML_SimpleXMLCDATAFactory();
        $cdata->insert($root, 'name', $this->name);
        if ($this->description) {
            $cdata->insert($root, 'description', $this->description);
        }
    }

    public function getReport()
    {
        return $this->report;
    }
}
