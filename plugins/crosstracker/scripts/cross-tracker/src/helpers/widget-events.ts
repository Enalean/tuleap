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

import type { Query } from "../type";
import type { Fault } from "@tuleap/fault";
import type { QuerySuggestion } from "../domain/SuggestedQueriesGetter";

export const SWITCH_QUERY_EVENT = "switch-query";
export const INITIALIZED_WITH_QUERY_EVENT = "initialized-with-query";
export const REFRESH_ARTIFACTS_EVENT = "refresh-artifacts";
export const SEARCH_ARTIFACTS_EVENT = "search-artifact";
export const CREATE_NEW_QUERY_EVENT = "create-new-query";
export const EDIT_QUERY_EVENT = "edit-query";
export const QUERY_EDITED_EVENT = "query-edited";
export const NEW_QUERY_CREATED_EVENT = "new-query-created";
export const QUERY_DELETED_EVENT = "query-deleted";
export const NOTIFY_FAULT_EVENT = "notify-fault";
export const NOTIFY_SUCCESS_EVENT = "notify-success";
export const DISPLAY_QUERY_PREVIEW_EVENT = "display-query-preview";
export const TOGGLE_QUERY_DETAILS_EVENT = "toggle-query-details";
export const STARTING_XLSX_EXPORT_EVENT = "starting-xlsx-export-event";
export const SELECTABLE_TABLE_RESIZED_EVENT = "selectable-table-resized-event";

export type Events = {
    [SWITCH_QUERY_EVENT]: SwitchQueryEvent;
    [INITIALIZED_WITH_QUERY_EVENT]: InitializedWithQueryEvent;
    [REFRESH_ARTIFACTS_EVENT]: RefreshArtifactsEvent;
    [SEARCH_ARTIFACTS_EVENT]: void;
    [CREATE_NEW_QUERY_EVENT]: void;
    [EDIT_QUERY_EVENT]: EditQueryEvent;
    [QUERY_EDITED_EVENT]: EditedQueryEvent;
    [NEW_QUERY_CREATED_EVENT]: CreatedQueryEvent;
    [QUERY_DELETED_EVENT]: DeletedQueryEvent;
    [NOTIFY_FAULT_EVENT]: NotifyFaultEvent;
    [NOTIFY_SUCCESS_EVENT]: NotifySuccessEvent;
    [DISPLAY_QUERY_PREVIEW_EVENT]: DisplayQueryPreviewEvent;
    [TOGGLE_QUERY_DETAILS_EVENT]: ToggleQueryDetailsEvent;
    [STARTING_XLSX_EXPORT_EVENT]: void;
    [SELECTABLE_TABLE_RESIZED_EVENT]: void;
};

export type ToggleQueryDetailsEvent = {
    readonly display_query_details: boolean;
};

export type EditQueryEvent = {
    readonly query: Query;
};

export type CreatedQueryEvent = {
    readonly query: Query;
};

export type EditedQueryEvent = {
    readonly query: Query;
};

export type DeletedQueryEvent = {
    readonly deleted_query: Query;
};

export type SwitchQueryEvent = {
    readonly query: Query;
};

export type InitializedWithQueryEvent = {
    readonly query: Query;
};

export type RefreshArtifactsEvent = {
    readonly query: Query;
};

export type NotifyFaultEvent = {
    readonly fault: Fault;
    readonly tql_query?: string;
};

export type NotifySuccessEvent = {
    readonly message: string;
};

export type DisplayQueryPreviewEvent = {
    readonly query: QuerySuggestion;
};
