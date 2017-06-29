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

use TemplateRendererFactory;
use Widget;

abstract class Kanban extends Widget
{
    public function getTitle()
    {
        return dgettext('tuleap-agiledashboard', 'Kanban');
    }

    public function getDescription()
    {
        return dgettext('tuleap-agiledashboard', 'Displays a board to see the tasks to do, in progress, done etc. Please go on a kanban to add it.');
    }

    public function getIcon()
    {
        return 'fa-columns';
    }

    public function getContent()
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(
            AGILEDASHBOARD_TEMPLATE_DIR . '/widgets'
        );

        return $renderer->renderToString('kanban', array(
            'purified_empty_state' => \Codendi_HTMLPurifier::instance()->purify(
                dgettext('tuleap-agiledashboard', "There is no content <br> you can see"),
                CODENDI_PURIFIER_LIGHT
            )
        ));
    }

    public function getCategory()
    {
        return 'plugin_agiledashboard';
    }

    public function canBeAddedFromWidgetList()
    {
        return false;
    }
}
