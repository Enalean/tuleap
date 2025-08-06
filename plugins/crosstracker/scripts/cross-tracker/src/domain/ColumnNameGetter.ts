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

import type { VueGettextProvider } from "../helpers/vue-gettext-provider";

export type GetColumnName = {
    getTranslatedColumnName(name: string): string;
};

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

export const ColumnNameGetter = (gettext_provider: VueGettextProvider): GetColumnName => {
    return {
        getTranslatedColumnName(name: string): string {
            if (name === TITLE_COLUMN_NAME) {
                return gettext_provider.$gettext("Title");
            }
            if (name === DESCRIPTION_COLUMN_NAME) {
                return gettext_provider.$gettext("Description");
            }
            if (name === STATUS_COLUMN_NAME) {
                return gettext_provider.$gettext("Status");
            }
            if (name === ASSIGNED_TO_COLUMN_NAME) {
                return gettext_provider.$gettext("Assigned to");
            }
            if (name === ARTIFACT_ID_COLUMN_NAME) {
                return gettext_provider.$gettext("Id");
            }
            if (name === SUBMITTED_ON_COLUMN_NAME) {
                return gettext_provider.$gettext("Submitted on");
            }
            if (name === SUBMITTED_BY_COLUMN_NAME) {
                return gettext_provider.$gettext("Submitted by");
            }
            if (name === LAST_UPDATE_DATE_COLUMN_NAME) {
                return gettext_provider.$gettext("Last update date");
            }
            if (name === LAST_UPDATE_BY_COLUMN_NAME) {
                return gettext_provider.$gettext("Last update by");
            }
            if (name === PROJECT_COLUMN_NAME) {
                return gettext_provider.$gettext("Project");
            }
            if (name === TRACKER_COLUMN_NAME) {
                return gettext_provider.$gettext("Tracker");
            }
            if (name === PRETTY_TITLE_COLUMN_NAME) {
                return gettext_provider.$gettext("Artifact");
            }
            if (name === ARTIFACT_COLUMN_NAME) {
                return "";
            }
            if (name === LINK_TYPE_COLUMN_NAME) {
                return gettext_provider.$gettext("Link type");
            }
            return name;
        },
    };
};
