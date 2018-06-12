<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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

class CardwallConfigXml {
    const NODE_CARDWALL = 'cardwall';
    const NODE_TRACKERS = 'trackers';
    const NODE_TRACKER  = 'tracker';
    const NODE_COLUMNS  = 'columns';
    const NODE_COLUMN   = 'column';
    const NODE_MAPPINGS = 'mappings';
    const NODE_MAPPING  = 'mapping';
    const NODE_VALUES   = 'values';
    const NODE_VALUE    = 'value';

    const ATTRIBUTE_COLUMN_LABEL          = 'label';
    const ATTRIBUTE_COLUMN_ID             = 'id';
    const ATTRIBUTE_COLUMN_BG_RED         = 'bg_red';
    const ATTRIBUTE_COLUMN_BG_GREEN       = 'bg_green';
    const ATTRIBUTE_COLUMN_BG_BLUE        = 'bg_blue';
    const ATTRIBUTE_COLUMN_TLP_COLOR_NAME = 'tlp_color_name';

    const ATTRIBUTE_TRACKER_ID = 'id';
}
