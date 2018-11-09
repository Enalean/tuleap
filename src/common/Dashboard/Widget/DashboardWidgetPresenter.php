<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Dashboard\Widget;

use Tuleap\Dashboard\Dashboard;
use Tuleap\Layout\CssAssetCollection;
use Widget;

class DashboardWidgetPresenter
{
    public $title;
    public $content;
    public $has_rss;
    public $rss_url;
    public $ajax_url;
    public $widget_id;
    public $widget_name;
    public $is_editable;
    public $is_minimized;
    public $edit_widget_label;
    public $delete_widget_label;
    public $delete_widget_confirm;
    public $is_content_loaded_asynchronously;
    public $has_actions;
    public $has_icon;
    public $icon;
    public $javascript_dependencies;
    public $has_custom_title;
    public $purified_custom_title;
    /** @var CssAssetCollection */
    public $stylesheet_dependencies;

    public function __construct(
        Dashboard $dashboard,
        DashboardWidget $dashboard_widget,
        Widget $widget,
        $can_update_dashboards
    ) {
        $this->widget_id    = $dashboard_widget->getId();
        $this->widget_name  = $dashboard_widget->getName();
        $this->is_minimized = $dashboard_widget->isMinimized();

        $widget->setDashboardWidgetId($dashboard_widget->getId());

        $this->title            = $widget->getTitle();
        $this->has_custom_title = $widget->hasCustomTitle();
        if ($this->has_custom_title) {
            $this->purified_custom_title = $widget->getPurifiedCustomTitle();
        }

        $this->is_editable    = $widget->hasPreferences($this->widget_id);
        $this->has_rss        = $widget->hasRss();
        $this->rss_url        = (string) $widget->getRssUrl($widget->owner_id, $widget->owner_type);
        $this->icon           = $widget->getIcon();
        $this->has_icon       = (bool) $this->icon;

        $this->javascript_dependencies = $widget->getJavascriptDependencies();
        $this->stylesheet_dependencies = $widget->getStylesheetDependencies();

        $this->has_actions = $this->has_rss || $can_update_dashboards;

        $this->is_content_loaded_asynchronously = $widget->isAjax();
        if ($this->is_content_loaded_asynchronously) {
            $this->content = '';
            $this->ajax_url = $widget->getAjaxUrl($widget->owner_id, $widget->owner_type, $dashboard->getId());
        } else {
            $this->content  = $widget->getContent();
            $this->ajax_url = '';
        }

        $this->edit_widget_label     = _('Edit widget');
        $this->delete_widget_label   = _('Delete widget');
        $this->delete_widget_confirm = sprintf(
            _(
                'You are about to delete the widget "%s".
                This action is irreversible. Please confirm this deletion.'
            ),
            $this->title
        );
    }
}
