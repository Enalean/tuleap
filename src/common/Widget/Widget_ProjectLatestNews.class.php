<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2017 - Present. All rights reserved
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

namespace Tuleap\Widget;

use HTTPRequest;
use Project;
use ProjectManager;
use Tuleap\Date\RelativeDatesAssetsRetriever;
use Widget;

/**
 * Widget_ProjectLatestNews
 */
class Widget_ProjectLatestNews extends Widget //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public $content;

    /**
     * Constructor of the class
     *
     * @retun Void
     */
    public function __construct()
    {
        parent::__construct('projectlatestnews');
        $request = $this->getHTTPRequest();
        $pm      = $this->getProjectManager();
        $project = $pm->getProject($request->get('group_id'));
        if ($project && $this->canBeUsedByProject($project)) {
            require_once __DIR__ . '/../../www/news/news_utils.php';
            $this->content = news_show_latest($request->get('group_id'), 10, false);
        }
    }

    /**
     * Title of the widget
     *
     * @return String
     */
    public function getTitle()
    {
        return $GLOBALS['Language']->getText('include_project_home', 'latest_news');
    }

    /**
     * Content of the widget
     *
     * @return String
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Content is available
     *
     * @return bool
     */
    public function isAvailable()
    {
        return $this->content ? true : false;
    }

    /**
     * Does project has news
     *
     * @param Project $project The project
     *
     * @return bool
     */
    private function canBeUsedByProject(Project $project)
    {
        return $project->usesNews();
    }

    /**
     * Description of the widget
     *
     * @return String
     */
    public function getDescription()
    {
        return $GLOBALS['Language']->getText('widget_description_project_latest_news', 'description');
    }

    /**
     * HTTPRequest instance
     *
     * @return HTTPRequest
     */
    private function getHTTPRequest()
    {
        return HTTPRequest::instance();
    }

    /**
     * ProjectManager instance
     *
     * @return ProjectManager
     */
    private function getProjectManager()
    {
        return ProjectManager::instance();
    }

    public function getJavascriptDependencies(): array
    {
        return [
            ['file' => RelativeDatesAssetsRetriever::retrieveAssetsUrl(), 'unique-name' => 'tlp-relative-dates'],
        ];
    }
}
