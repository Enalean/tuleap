<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * @return string
 */
function service_replace_template_name_in_link($link, array $template, Project $project)
{
    $link = preg_replace('#(/www/|/projects/|group=)' . preg_quote($template['name'], '#') . '(/|&|$)#', '$1' . $project->getUnixName() . '$2', $link);
    $link = preg_replace('/group_id=' . preg_quote($template['id'], '/') . '([^\d]|$)/', 'group_id=' . $project->getGroupId() . '$1', $link);
    EventManager::instance()->processEvent(
        Event::SERVICE_REPLACE_TEMPLATE_NAME_IN_LINK,
        array(
            'link'     => &$link,
            'template' => $template,
            'project'  => $project
        )
    );
    return $link;
}
