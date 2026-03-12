/*
 * Copyright (c) Enalean, 2026-present. All Rights Reserved.
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

import {
    CONTAINER_FIELDSET,
    DATE_FIELD,
    FLOAT_FIELD,
    INT_FIELD,
    LINE_BREAK,
    MULTI_SELECTBOX_FIELD,
    SELECTBOX_FIELD,
    SEPARATOR,
    STRING_FIELD,
    TEXT_FIELD,
    CHECKBOX_FIELD,
    RADIO_BUTTON_FIELD,
    ARTIFACT_ID_FIELD,
    ARTIFACT_ID_IN_TRACKER_FIELD,
    STATIC_RICH_TEXT,
    ARTIFACT_LINK_FIELD,
    SUBMITTED_BY_FIELD,
    LAST_UPDATED_BY_FIELD,
    SUBMISSION_DATE_FIELD,
    LAST_UPDATE_DATE_FIELD,
    CROSS_REFERENCE_FIELD,
    FILE_FIELD,
    OPEN_LIST_FIELD,
    PERMISSION_FIELD,
    COMPUTED_FIELD,
    CONTAINER_COLUMN,
    PRIORITY_FIELD,
    SHARED_FIELD,
} from "@tuleap/plugin-tracker-constants";

export function getIconFromFieldType(field_type: string): string {
    switch (field_type) {
        case STRING_FIELD:
        case TEXT_FIELD:
            return "fa-solid fa-t";
        case FLOAT_FIELD:
        case INT_FIELD:
            return "fa-solid fa-3";
        case DATE_FIELD:
        case LAST_UPDATE_DATE_FIELD:
        case SUBMISSION_DATE_FIELD:
            return "fa-solid fa-calendar-days";
        case SELECTBOX_FIELD:
        case MULTI_SELECTBOX_FIELD:
        case OPEN_LIST_FIELD:
            return "fa-solid fa-list";
        case RADIO_BUTTON_FIELD:
            return "fa-regular fa-circle-dot";
        case CHECKBOX_FIELD:
            return "fa-regular fa-square-check";
        case FILE_FIELD:
            return "fa-solid fa-upload";
        case ARTIFACT_LINK_FIELD:
            return "fa-solid fa-link";
        case PERMISSION_FIELD:
            return "fa-solid fa-lock";
        case LAST_UPDATED_BY_FIELD:
        case SUBMITTED_BY_FIELD:
            return "fa-solid fa-user";
        case ARTIFACT_ID_FIELD:
        case ARTIFACT_ID_IN_TRACKER_FIELD:
            return "fa-solid fa-hashtag";
        case CROSS_REFERENCE_FIELD:
            return "fa-solid fa-arrows-turn-to-dots";
        case COMPUTED_FIELD:
            return "fa-solid fa-calculator";
        case PRIORITY_FIELD:
            return "fa-solid fa-arrow-up-short-wide";
        case CONTAINER_FIELDSET:
            return "fa-regular fa-square";
        case CONTAINER_COLUMN:
            return "fa-solid fa-table-columns";
        case LINE_BREAK:
            return "fa-solid fa-arrow-turn-down fa-rotate-90";
        case SEPARATOR:
            return "fa-solid fa-minus";
        case STATIC_RICH_TEXT:
            return "fa-solid fa-align-left";
        case SHARED_FIELD:
            return "fa-solid fa-shapes";
        default:
            return "fa-solid fa-question";
    }
}
