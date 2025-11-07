<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use Tuleap\Widget\Event\GetPublicAreas;

/**
* Widget_ProjectPublicAreas
*/
class Widget_ProjectPublicAreas extends Widget //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    public function __construct()
    {
        parent::__construct('projectpublicareas');
    }

    #[\Override]
    public function getTitle()
    {
        return $GLOBALS['Language']->getText('include_project_home', 'public_areas');
    }

    #[\Override]
    public function getContent(): string
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $request  = HTTPRequest::instance();
        $group_id = db_ei($request->get('group_id'));
        $pm       = ProjectManager::instance();
        $project  = $pm->getProject($group_id);
        $html     = '';

        // ######################### Wiki (only for Active)

        $wiki_service = $project->getService(Service::WIKI);
        if ($wiki_service !== null) {
            $html    .= '<p><a href="' . $purifier->purify($wiki_service->getUrl()) . '">';
            $html    .= '<i class="dashboard-widget-content-projectpublicareas ' . $purifier->purify($wiki_service->getIcon()) . '"></i>';
            $html    .= $GLOBALS['Language']->getText('include_project_home', 'wiki') . '</A>';
                $wiki = new Wiki($group_id);
            $pos      = strpos($project->getWikiPage(), '/wiki/');
            if ($pos === 0) {
                $html .= ' ( ' . $GLOBALS['Language']->getText('include_project_home', 'nb_wiki_pages', $wiki->getProjectPageCount()) . ' )';
            }
            $html .= '</p>';
        }


        // ######################### File Releases (only for Active)

        $file_service = $project->getService(Service::FILE);
        if ($file_service !== null) {
            $html .= $file_service->getPublicArea();
        }


        $event = new GetPublicAreas($project);
        EventManager::instance()->processEvent($event);
        foreach ($event->getAreas() as $area) {
            $html .= '<p>' . $area . '</p>';
        }

        return $html;
    }

    #[\Override]
    public function getDescription()
    {
        return $GLOBALS['Language']->getText('widget_description_project_public_areas', 'description');
    }
}
