<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Docman;

class ServiceDocman extends \Service
{
    public function displayHeader(string $title, $breadcrumbs, array $toolbar, array $params = []): void
    {
        $GLOBALS['HTML']->includeCalendarScripts();

        parent::displayHeader($title, [], $toolbar);
    }

    public function getIconName(): string
    {
        return 'fa-folder-open';
    }

    public function getInternationalizedName(): string
    {
        $label = $this->getLabel();

        if ($label === 'plugin_docman:service_lbl_key') {
            return dgettext('tuleap-docman', 'Documents');
        }

        return $label;
    }

    public function getInternationalizedDescription(): string
    {
        $description = $this->getDescription();

        if ($description === 'plugin_docman:service_desc_key') {
            return dgettext('tuleap-docman', 'Document manager');
        }

        return $description;
    }
}
