<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced;

use Tracker_FormElementFactory;

final class AllowedMetadata
{
    public const TITLE            = '@title';
    public const DESCRIPTION      = '@description';
    public const STATUS           = '@status';
    public const SUBMITTED_ON     = '@submitted_on';
    public const LAST_UPDATE_DATE = '@last_update_date';
    public const SUBMITTED_BY     = '@submitted_by';
    public const LAST_UPDATE_BY   = '@last_update_by';
    public const ASSIGNED_TO      = '@assigned_to';
    public const ID               = '@id';
    public const PROJECT_NAME     = '@project.name';
    public const TRACKER_NAME     = '@tracker.name';
    public const PRETTY_TITLE     = '@pretty_title';

    public const SEARCHABLE_NAMES = [
        self::TITLE,
        self::DESCRIPTION,
        self::STATUS,
        self::SUBMITTED_ON,
        self::LAST_UPDATE_DATE,
        self::SUBMITTED_BY,
        self::LAST_UPDATE_BY,
        self::ASSIGNED_TO,
        self::ID,
    ];

    public const SELECTABLE_NAMES = [
        self::TITLE,
        self::DESCRIPTION,
        self::STATUS,
        self::SUBMITTED_ON,
        self::LAST_UPDATE_DATE,
        self::SUBMITTED_BY,
        self::LAST_UPDATE_BY,
        self::ASSIGNED_TO,
        self::ID,
        self::PROJECT_NAME,
        self::TRACKER_NAME,
        self::PRETTY_TITLE,
    ];

    public const SORTABLE_NAMES = [
        self::TITLE,
        self::DESCRIPTION,
        self::STATUS,
        self::SUBMITTED_ON,
        self::LAST_UPDATE_DATE,
        self::SUBMITTED_BY,
        self::LAST_UPDATE_BY,
        self::ASSIGNED_TO,
        self::ID,
    ];

    public const FIELD_WITH_NO_CHANGESET = [
        Tracker_FormElementFactory::FIELD_SUBMITTED_ON_TYPE     => self::SUBMITTED_ON,
        Tracker_FormElementFactory::FIELD_LAST_UPDATE_DATE_TYPE => self::LAST_UPDATE_DATE,
        Tracker_FormElementFactory::FIELD_SUBMITTED_BY_TYPE     => self::SUBMITTED_BY,
        Tracker_FormElementFactory::FIELD_LAST_MODIFIED_BY      => self::LAST_UPDATE_BY,
        Tracker_FormElementFactory::FIELD_ARTIFACT_ID_TYPE      => self::ID,
    ];
}
