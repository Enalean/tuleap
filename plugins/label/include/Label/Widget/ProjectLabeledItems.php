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

namespace Tuleap\Label\Widget;

use Widget;

class ProjectLabeledItems extends Widget
{
    const NAME = 'projectlabeleditems';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    public function getTitle()
    {
        return dgettext('tuleap-label', 'Labeled Items');
    }

    public function getDescription()
    {
        return dgettext('tuleap-label', 'Displays items with configured labels in the project. For example you can search all Pull Requests labeled "Emergency" and "v3.0"');
    }

    public function isUnique()
    {
        return false;
    }

    public function getContent()
    {
        $renderer = \TemplateRendererFactory::build()->getRenderer(
            LABEL_BASE_DIR . '/templates/widgets'
        );

        return $renderer->renderToString(
            'project-labeled-items',
            new ProjectLabeledItemsPresenter()
        );
    }
}
