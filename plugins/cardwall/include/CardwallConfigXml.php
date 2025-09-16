<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

class CardwallConfigXml //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public const string NODE_CARDWALL = 'cardwall';
    public const string NODE_TRACKERS = 'trackers';
    public const string NODE_TRACKER  = 'tracker';
    public const string NODE_COLUMNS  = 'columns';
    public const string NODE_COLUMN   = 'column';
    public const string NODE_MAPPINGS = 'mappings';
    public const string NODE_MAPPING  = 'mapping';
    public const string NODE_VALUES   = 'values';
    public const string NODE_VALUE    = 'value';

    public const string ATTRIBUTE_COLUMN_LABEL          = 'label';
    public const string ATTRIBUTE_COLUMN_ID             = 'id';
    public const string ATTRIBUTE_COLUMN_BG_RED         = 'bg_red';
    public const string ATTRIBUTE_COLUMN_BG_GREEN       = 'bg_green';
    public const string ATTRIBUTE_COLUMN_BG_BLUE        = 'bg_blue';
    public const string ATTRIBUTE_COLUMN_TLP_COLOR_NAME = 'tlp_color_name';

    public const string ATTRIBUTE_TRACKER_ID = 'id';
}
