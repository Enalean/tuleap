<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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


/**
 * Display the admin of the Cardwall
 */
class Cardwall_View_Admin extends Cardwall_View {

    public function displayAdminOnTop(
        Tracker_IDisplayTrackerLayout $layout,
        CSRFSynchronizerToken $token,
        Cardwall_OnTop_Config $config
    ) {

        $column_definition_view = $config->getDashboardColumns()->accept($this, $config);

        $checked           = $config->isEnabled() ? 'checked="checked"' : '';
        $freestyle_checked = $config->isFreestyleEnabled() ? 'checked="checked"' : '';
        $token_html        = $token->fetchHTMLInput();
        $formview          = new Cardwall_View_Admin_Form($column_definition_view);

        $config->getTracker()->displayAdminItemHeader($layout, 'plugin_cardwall');
        $formview->displayAdminForm($token_html, $checked, $freestyle_checked, $config->getTracker()->getId());
        $config->getTracker()->displayFooter($layout);
    }

    public function visitColumnStatusCollection($collection, Cardwall_OnTop_Config $config) {
        return new Cardwall_OnTop_Config_View_SemanticStatusColumnDefinition($config);
    }

    public function visitColumnFreestyleCollection($collection, Cardwall_OnTop_Config $config) {
        return new Cardwall_OnTop_Config_View_FreestyleColumnDefinition($config);
    }
}
?>
