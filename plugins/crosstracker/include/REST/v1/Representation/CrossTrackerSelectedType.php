<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\REST\v1\Representation;

enum CrossTrackerSelectedType: string
{
    case TYPE_DATE            = 'date';
    case TYPE_TEXT            = 'text';
    case TYPE_NUMERIC         = 'numeric';
    case TYPE_STATIC_LIST     = 'list_static';
    case TYPE_USER_GROUP_LIST = 'list_user_group';
    case TYPE_USER_LIST       = 'list_user';
    case TYPE_USER            = 'user';
    case TYPE_PROJECT         = 'project';
    case TYPE_TRACKER         = 'tracker';
    case TYPE_PRETTY_TITLE    = 'pretty_title';
    case TYPE_ARTIFACT        = 'artifact';
}
