<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\TestManagement;

use Service as CoreService;

class Service extends CoreService
{
    public function getInternationalizedName(): string
    {
        $label = $this->getLabel();

        if ($label === 'plugin_testmanagement:service_lbl_key') {
            return dgettext('tuleap-testmanagement', 'Test Management');
        }

        return $label;
    }

    public function getInternationalizedDescription(): string
    {
        $description = $this->getDescription();

        if ($description === 'plugin_testmanagement:service_desc_key') {
            return dgettext('tuleap-testmanagement', 'Test Management');
        }

        return $description;
    }

    public function getIconName(): string
    {
        return 'fas fa-check-double';
    }
}
