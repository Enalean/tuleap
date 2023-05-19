<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\JavascriptAssetGeneric;
use Tuleap\Project\MappingRegistry;

/**
* Widget
*/
/* abstract */ class Widget // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public $content_id;
    public $id;
    public $owner_id;
    public $owner_type;

    /** @var  int */
    protected $dashboard_widget_id;

    /**
     * @var int
     */
    protected $dashboard_id;

    /**
    * Constructor
    */
    public function __construct($id)
    {
        $this->id         = $id;
        $this->content_id = 0;
    }

    public function getId()
    {
        return $this->id;
    }

    public function hasCustomTitle()
    {
        return false;
    }

    public function getPurifiedCustomTitle()
    {
        return '';
    }

    public function getTitle()
    {
        return '';
    }

    public function getContent()
    {
        return '';
    }

    public function getInstallPreferences()
    {
        return '';
    }

    public function hasPreferences($widget_id)
    {
        return false;
    }

    public function getPreferences(int $widget_id, int $content_id): string
    {
        return '';
    }

    public function updatePreferences(Codendi_Request $request)
    {
        return true;
    }

    public function hasRss()
    {
        return false;
    }

    public function getRssUrl($owner_id, $owner_type)
    {
        if ($this->hasRss()) {
            return '/widgets/?' . http_build_query(
                [
                    'owner'  => $owner_type . $owner_id,
                    'action' => 'rss',
                    'name'   => [
                        $this->id => $this->getInstanceId(),
                    ],
                ]
            );
        } else {
            return false;
        }
    }

    public function isUnique()
    {
        return true;
    }

    public function isAvailable()
    {
        return true;
    }

    public function isAjax()
    {
        return false;
    }

    public function getInstanceId()
    {
        return $this->content_id;
    }

    public function loadContent($id)
    {
    }

    public function setOwner($owner_id, $owner_type)
    {
        $this->owner_id   = $owner_id;
        $this->owner_type = $owner_type;
    }

    /**
    * cloneContent
    *
    * Take the content of a widget, clone it and return the id of the new content
    */
    public function cloneContent(
        Project $template_project,
        Project $new_project,
        $id,
        $owner_id,
        $owner_type,
        MappingRegistry $mapping_registry,
    ) {
        return $this->getInstanceId();
    }

    /**
     *
     * @return null|false|int
     */
    public function create(Codendi_Request $request)
    {
        return null;
    }

    public function destroy($id)
    {
    }

    public function getCategory()
    {
        return _('General');
    }

    public function getDescription()
    {
        return '';
    }

    /**
     * @return PFUser
     */
    public function getCurrentUser()
    {
        return UserManager::instance()->getCurrentUser();
    }

    public function getAjaxUrl($owner_id, $owner_type, $dashboard_id)
    {
        $additional_parameters = [];
        if ($owner_type === ProjectDashboardController::LEGACY_DASHBOARD_TYPE) {
            $additional_parameters = ['group_id' => $owner_id];
        }

        return \Tuleap\ServerHostname::HTTPSUrl() . '/widgets/?' . http_build_query(
            array_merge(
                [
                    'dashboard_id' => $dashboard_id,
                    'action'       => 'ajax',
                    'name'         => [
                        $this->id => $this->getInstanceId(),
                    ],
                ],
                $additional_parameters
            )
        );
    }

    public function displayRss()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return "";
    }

    public function canBeAddedFromWidgetList()
    {
        return true;
    }

    /**
     * @return string
     */
    public function getImageSource()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getImageTitle()
    {
        return '';
    }

    /**
     * @return array
     * @deprecated
     */
    public function getJavascriptDependencies()
    {
        return [];
    }

    /**
     * @return JavascriptAssetGeneric[]
     */
    public function getJavascriptAssets(): array
    {
        return [];
    }

    /**
     * @return CssAssetCollection
     */
    public function getStylesheetDependencies()
    {
        return new CssAssetCollection([]);
    }

    public function setDashboardWidgetId($dashboard_widget_id)
    {
        $this->dashboard_widget_id = $dashboard_widget_id;
    }

    public function setDashboardId($dashboard_id)
    {
        $this->dashboard_id = $dashboard_id;
    }

    /**
     * @return int
     */
    public function getDashboardId()
    {
        return $this->dashboard_id;
    }

    public function exportAsXML(): ?\SimpleXMLElement
    {
        return null;
    }
}
