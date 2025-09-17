/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import type { StrictInjectionKey } from "@tuleap/vue-strict-inject";
import type { ProjectPrivacy } from "@tuleap/project-privacy-helper";
import type { ProjectFlag } from "@tuleap/vue3-breadcrumb-privacy";
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";
import type { ListOfSearchResultColumnDefinition, SearchCriteria } from "./type";

export const USER_ID: StrictInjectionKey<number> = Symbol("user-id");
export const PROJECT_ID: StrictInjectionKey<number> = Symbol("project-id");
export const ROOT_ID: StrictInjectionKey<number> = Symbol("root-id");
export const PROJECT_NAME: StrictInjectionKey<string> = Symbol("project-name");
export const PROJECT_PUBLIC_NAME: StrictInjectionKey<string> = Symbol("project-public-name");
export const USER_IS_ADMIN: StrictInjectionKey<boolean> = Symbol("user-is-admin");
export const USER_CAN_CREATE_WIKI: StrictInjectionKey<boolean> = Symbol("user-can-create-wiki");
export const EMBEDDED_ARE_ALLOWED: StrictInjectionKey<boolean> = Symbol("embedded-are-allowed");
export const IS_STATUS_PROPERTY_USED: StrictInjectionKey<boolean> =
    Symbol("is-status-property-used");
export const IS_OBSOLESCENCE_DATE_PROPERTY_USED: StrictInjectionKey<boolean> = Symbol(
    "is-obsolescence-date-property-used",
);
export const MAX_FILES_DRAGNDROP: StrictInjectionKey<number> = Symbol("max-files-dragndrop");
export const USER_CAN_DRAGNDROP: StrictInjectionKey<boolean> = Symbol("user-can-dragndrop");
export const MAX_SIZE_UPLOAD: StrictInjectionKey<number> = Symbol("max-size-upload");
export const WARNING_THRESHOLD: StrictInjectionKey<number> = Symbol("warning-threshold");
export const MAX_ARCHIVE_SIZE: StrictInjectionKey<number> = Symbol("max-archive-size");
export const PROJECT_URL: StrictInjectionKey<string> = Symbol("project-url");
export const DATE_TIME_FORMAT: StrictInjectionKey<string> = Symbol("date-time-format");
export const PROJECT_PRIVACY: StrictInjectionKey<ProjectPrivacy> = Symbol("project-privacy");
export const PROJECT_FLAGS: StrictInjectionKey<ReadonlyArray<ProjectFlag>> =
    Symbol("project-flags");
export const IS_CHANGELOG_PROPOSED_AFTER_DND: StrictInjectionKey<boolean> = Symbol(
    "is-changelog-proposed-after-dnd",
);
export const IS_DELETION_ALLOWED: StrictInjectionKey<boolean> = Symbol("is-deletion-allowed");
export const USER_LOCALE: StrictInjectionKey<string> = Symbol("user-locale");
export const RELATIVE_DATES_DISPLAY: StrictInjectionKey<RelativeDatesDisplayPreference> =
    Symbol("relative-dates-display");
export const PROJECT_ICON: StrictInjectionKey<string> = Symbol("project-icon");
export const SEARCH_CRITERIA: StrictInjectionKey<SearchCriteria> = Symbol("search-criteria");
export const SEARCH_COLUMNS: StrictInjectionKey<ListOfSearchResultColumnDefinition> =
    Symbol("search-column");
