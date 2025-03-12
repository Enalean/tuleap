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

import type { Emitter } from "mitt";
import type { Query } from "../type";
import type { Fault } from "@tuleap/fault";
import type { QuerySuggestion } from "../domain/SuggestedQueriesGetter";

export type EmitterProvider = Pick<Emitter<Events>, "off" | "on" | "emit">;

export const SWITCH_QUERY_EVENT = "switch-query";
export const REFRESH_ARTIFACTS_EVENT = "refresh-artifacts";
export const SEARCH_ARTIFACTS_EVENT = "search-artifact";
export const CREATE_NEW_QUERY_EVENT = "create-new-query";
export const NEW_QUERY_CREATED_EVENT = "new-query-created";
export const NOTIFY_FAULT_EVENT = "notify-fault";
export const NOTIFY_SUCCESS_EVENT = "notify-success";
export const CLEAR_FEEDBACK_EVENT = "clear-feedback";
export const DISPLAY_QUERY_PREVIEW_EVENT = "display_query_preview";
export const UPDATE_WIDGET_TITLE_EVENT = "update-widget-title";

export type Events = {
    [SWITCH_QUERY_EVENT]: SwitchQueryEvent;
    [REFRESH_ARTIFACTS_EVENT]: RefreshArtifactsEvent;
    [SEARCH_ARTIFACTS_EVENT]: void;
    [CREATE_NEW_QUERY_EVENT]: void;
    [NEW_QUERY_CREATED_EVENT]: CreatedQueryEvent;
    [NOTIFY_FAULT_EVENT]: NotifyFaultEvent;
    [NOTIFY_SUCCESS_EVENT]: NotifySuccessEvent;
    [CLEAR_FEEDBACK_EVENT]: void;
    [DISPLAY_QUERY_PREVIEW_EVENT]: DisplayQueryPreviewEvent;
    [UPDATE_WIDGET_TITLE_EVENT]: UpdateWidgetTitleEvent;
};

export type UpdateWidgetTitleEvent = {
    readonly new_title: string;
};

export type CreatedQueryEvent = {
    readonly created_query: Query;
};

export type SwitchQueryEvent = {
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
