/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { describe, expect, it } from "vitest";
import {
    ARTIFACT_COLUMN_NAME,
    ARTIFACT_ID_COLUMN_NAME,
    ASSIGNED_TO_COLUMN_NAME,
    DESCRIPTION_COLUMN_NAME,
    LAST_UPDATE_BY_COLUMN_NAME,
    LAST_UPDATE_DATE_COLUMN_NAME,
    LINK_TYPE_COLUMN_NAME,
    PRETTY_TITLE_COLUMN_NAME,
    PROJECT_COLUMN_NAME,
    STATUS_COLUMN_NAME,
    SUBMITTED_BY_COLUMN_NAME,
    SUBMITTED_ON_COLUMN_NAME,
    TITLE_COLUMN_NAME,
    TRACKER_COLUMN_NAME,
} from "./ColumnName";
import { ColumnNameGetter } from "./ColumnNameGetter";
import { createVueGettextProviderPassThrough } from "../helpers/vue-gettext-provider-for-test";

describe("ColumnNameGetter.ts", () => {
    it.each([
        [TITLE_COLUMN_NAME, "Title"],
        [DESCRIPTION_COLUMN_NAME, "Description"],
        [STATUS_COLUMN_NAME, "Status"],
        [ASSIGNED_TO_COLUMN_NAME, "Assigned to"],
        [ARTIFACT_ID_COLUMN_NAME, "Id"],
        [SUBMITTED_ON_COLUMN_NAME, "Submitted on"],
        [SUBMITTED_BY_COLUMN_NAME, "Submitted by"],
        [LAST_UPDATE_DATE_COLUMN_NAME, "Last update date"],
        [LAST_UPDATE_BY_COLUMN_NAME, "Last update by"],
        [PROJECT_COLUMN_NAME, "Project"],
        [TRACKER_COLUMN_NAME, "Tracker"],
        [PRETTY_TITLE_COLUMN_NAME, "Artifact"],
        [ARTIFACT_COLUMN_NAME, ""],
        [LINK_TYPE_COLUMN_NAME, "Link type"],
    ])(`returns the real column name of %s`, (column_name, expected_final_name) => {
        const result = ColumnNameGetter(
            createVueGettextProviderPassThrough(),
        ).getTranslatedColumnName(column_name);
        expect(result).toBe(expected_final_name);
    });
});
