/*
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

export const TITLE_COLUMN_NAME = "@title";
export const DESCRIPTION_COLUMN_NAME = "@description";
export const STATUS_COLUMN_NAME = "@status";
export const ASSIGNED_TO_COLUMN_NAME = "@assigned_to";
export const ARTIFACT_ID_COLUMN_NAME = "@id";
export const SUBMITTED_ON_COLUMN_NAME = "@submitted_on";
export const SUBMITTED_BY_COLUMN_NAME = "@submitted_by";
export const LAST_UPDATE_DATE_COLUMN_NAME = "@last_update_date";
export const LAST_UPDATE_BY_COLUMN_NAME = "@last_update_by";
export const PROJECT_COLUMN_NAME = "@project.name";
export const TRACKER_COLUMN_NAME = "@tracker.name";
export const PRETTY_TITLE_COLUMN_NAME = "@pretty_title";
export const ARTIFACT_COLUMN_NAME = "@artifact";
export const LINK_TYPE_COLUMN_NAME = "@link_type";

export type ColumnName =
    | typeof TITLE_COLUMN_NAME
    | typeof DESCRIPTION_COLUMN_NAME
    | typeof STATUS_COLUMN_NAME
    | typeof ASSIGNED_TO_COLUMN_NAME
    | typeof ARTIFACT_ID_COLUMN_NAME
    | typeof SUBMITTED_ON_COLUMN_NAME
    | typeof SUBMITTED_BY_COLUMN_NAME
    | typeof LAST_UPDATE_DATE_COLUMN_NAME
    | typeof LAST_UPDATE_BY_COLUMN_NAME
    | typeof PROJECT_COLUMN_NAME
    | typeof TRACKER_COLUMN_NAME
    | typeof PRETTY_TITLE_COLUMN_NAME
    | typeof ARTIFACT_COLUMN_NAME
    | typeof LINK_TYPE_COLUMN_NAME
    | string;
