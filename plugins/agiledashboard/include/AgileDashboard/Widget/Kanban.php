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

namespace Tuleap\AgileDashboard\Widget;

use AgileDashboard_KanbanCannotAccessException;
use AgileDashboard_KanbanFactory;
use AgileDashboard_KanbanNotFoundException;
use AgileDashboard_PermissionsManager;
use KanbanPresenter;
use TemplateRendererFactory;
use TrackerFactory;
use Widget;

abstract class Kanban extends Widget
{
    protected $kanban_id;
    protected $kanban_title;
    /**
     * @var WidgetKanbanCreator
     */
    private $widget_kanban_creator;
    /**
     * @var AgileDashboard_KanbanFactory
     */
    private $kanban_factory;
    /**
     * @var WidgetKanbanRetriever
     */
    private $widget_kanban_retriever;
    /**
     * @var AgileDashboard_PermissionsManager
     */
    private $permissions_manager;
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var WidgetKanbanDeletor
     */
    private $widget_kanban_deletor;

    public function __construct(
        $id,
        $owner_id,
        $owner_type,
        WidgetKanbanCreator $widget_kanban_creator,
        WidgetKanbanRetriever $widget_kanban_retriever,
        WidgetKanbanDeletor $widget_kanban_deletor,
        AgileDashboard_KanbanFactory $kanban_factory,
        TrackerFactory $tracker_factory,
        AgileDashboard_PermissionsManager $permissions_manager
    ) {
        parent::__construct($id);
        $this->owner_id                = $owner_id;
        $this->owner_type              = $owner_type;
        $this->widget_kanban_creator   = $widget_kanban_creator;
        $this->widget_kanban_retriever = $widget_kanban_retriever;
        $this->widget_kanban_deletor   = $widget_kanban_deletor;
        $this->kanban_factory          = $kanban_factory;
        $this->tracker_factory         = $tracker_factory;
        $this->permissions_manager     = $permissions_manager;
    }

    public function create(&$request)
    {
        return $this->widget_kanban_creator->create($request, $this->owner_id, $this->owner_type);
    }

    public function getTitle()
    {
        return $this->kanban_title ? : 'Kanban';
    }

    public function getDescription()
    {
        return dgettext('tuleap-agiledashboard', 'Displays a board to see the tasks to do, in progress, done etc. Please go on a kanban to add it.');
    }

    public function getIcon()
    {
        return 'fa-columns';
    }

    public function loadContent($id)
    {
        $widget = $this->widget_kanban_retriever->searchWidgetById($id, $this->owner_id, $this->owner_type);
        if ($widget) {
            $this->content_id   = $id;
            $this->kanban_id    = $widget['kanban_id'];
            $this->kanban_title = $widget['title'];
        }
    }

    public function getContent()
    {
        $is_empty = true;
        $renderer = TemplateRendererFactory::build()->getRenderer(
            AGILEDASHBOARD_TEMPLATE_DIR . '/widgets'
        );
        try {
            $kanban     = $this->kanban_factory->getKanban($this->getCurrentUser(), $this->kanban_id);
            $tracker    = $this->tracker_factory->getTrackerByid($kanban->getTrackerId());
            $project_id = $tracker->getProject()->getID();
            $is_empty   = ! $kanban;

            $user_is_kanban_admin = $this->permissions_manager->userCanAdministrate(
                $this->getCurrentUser(),
                $project_id
            );
            $kanban_presenter = new KanbanPresenter(
                $kanban,
                $this->getCurrentUser(),
                $user_is_kanban_admin,
                $this->getCurrentUser()->getShortLocale(),
                $project_id,
                true
            );
            $widget_kanban_presenter = new WidgetKanbanPresenter(
                $is_empty,
                '',
                $kanban_presenter
            );
        } catch (AgileDashboard_KanbanNotFoundException $exception) {
            $widget_kanban_presenter = new WidgetKanbanPresenter(
                $is_empty,
                $GLOBALS['Language']->getText('plugin_agiledashboard', 'kanban_not_found')
            );
        } catch (AgileDashboard_KanbanCannotAccessException $exception) {
            $widget_kanban_presenter = new WidgetKanbanPresenter(
                $is_empty,
                $GLOBALS['Language']->getText('global', 'error_perm_denied')
            );
        }

        return $renderer->renderToString('widget-kanban', $widget_kanban_presenter);
    }

    public function getCategory()
    {
        return 'plugin_agiledashboard';
    }

    public function destroy($id)
    {
        $this->widget_kanban_deletor->delete($id, $this->owner_id, $this->owner_type);
    }

    public function canBeAddedFromWidgetList()
    {
        return false;
    }

    public function isUnique()
    {
        return false;
    }

    public function getImageSource()
    {
        return '/themes/common/images/widgets/add-kanban-widget-from-kanban.png';
    }

    public function getImageTitle()
    {
        return dgettext('tuleap-agiledashboard', 'Add Kanban to dashboard');
    }
}
