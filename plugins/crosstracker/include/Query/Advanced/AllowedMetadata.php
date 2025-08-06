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
    public const string TITLE            = '@title';
    public const string DESCRIPTION      = '@description';
    public const string STATUS           = '@status';
    public const string SUBMITTED_ON     = '@submitted_on';
    public const string LAST_UPDATE_DATE = '@last_update_date';
    public const string SUBMITTED_BY     = '@submitted_by';
    public const string LAST_UPDATE_BY   = '@last_update_by';
    public const string ASSIGNED_TO      = '@assigned_to';
    public const string ID               = '@id';
    public const string PROJECT_NAME     = '@project.name';
    public const string TRACKER_NAME     = '@tracker.name';
    public const string PRETTY_TITLE     = '@pretty_title';
    public const string LINK_TYPE        = '@link_type';

    public const array SEARCHABLE_NAMES = [
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

    public const array SELECTABLE_NAMES = [
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
        self::LINK_TYPE,
    ];

    public const array SORTABLE_NAMES = [
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

    public const array FIELD_WITH_NO_CHANGESET = [
        Tracker_FormElementFactory::FIELD_SUBMITTED_ON_TYPE     => self::SUBMITTED_ON,
        Tracker_FormElementFactory::FIELD_LAST_UPDATE_DATE_TYPE => self::LAST_UPDATE_DATE,
        Tracker_FormElementFactory::FIELD_SUBMITTED_BY_TYPE     => self::SUBMITTED_BY,
        Tracker_FormElementFactory::FIELD_LAST_MODIFIED_BY      => self::LAST_UPDATE_BY,
        Tracker_FormElementFactory::FIELD_ARTIFACT_ID_TYPE      => self::ID,
    ];
}
