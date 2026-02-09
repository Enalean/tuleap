<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Cardwall\OnTop\Config\View;

use Cardwall_OnTop_Config;
use Cardwall_OnTop_Config_View_ColumnDefinition;

/**
 * Display the admin of the Cardwall
 */
final readonly class CardwallConfigAdminView
{
    public function __construct(
        private bool $uses_taskboard,
    ) {
    }

    public function displayAdminOnTop(Cardwall_OnTop_Config $config): string
    {
        return $this->generateAdminForm($config);
    }

    private function generateAdminForm(Cardwall_OnTop_Config $config): string
    {
        $column_definition_view = new Cardwall_OnTop_Config_View_ColumnDefinition($config);
        $checked                = $config->isEnabled() ? 'checked="checked"' : '';

        $html  = '<div class="tlp-form-element">';
        $html .= '<input type="hidden" name="cardwall_on_top" value="0" />';
        $html .= '<label class="tlp-label tlp-checkbox">';
        $html .= '<input type="checkbox" name="cardwall_on_top" value="1" id="cardwall_on_top" ' . $checked . '/> ';
        if ($this->uses_taskboard) {
            $html .= dgettext('tuleap-cardwall', 'Enable taskboard on top of this planning');
        } else {
            $html .= dgettext('tuleap-cardwall', 'Enable cardwall on top of this planning');
        }
        $html .= '</label>';
        $html .= '</div>';
        $html .= '<input type="hidden" name="update_cardwall" value="1" />';

        if ($checked) {
            $html .= $column_definition_view->fetchColumnDefinition();
        }

        return $html;
    }
}
