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

import type { StrictInjectionKey } from "@tuleap/vue-strict-inject";
import type { IntlFormatter } from "@tuleap/date-helper";
import type { Ref } from "vue";
import type { RetrieveArtifactsTable } from "./domain/RetrieveArtifactsTable";
import type { ReportState } from "./domain/ReportState";
import type { NotifyFaultCallback, ClearFeedbacksCallback } from "./composables/useFeedbacks";
import type { GetColumnName } from "./domain/ColumnNameGetter";
import type { EmitterProvider } from "./helpers/emitter-provider";
import type { SuggestedQueriesGetter } from "./domain/SuggestedQueriesGetter";
import type { DashboardType } from "./domain/DashboardType";

export const DATE_FORMATTER: StrictInjectionKey<IntlFormatter> = Symbol();
export const DATE_TIME_FORMATTER: StrictInjectionKey<IntlFormatter> = Symbol();
export const RETRIEVE_ARTIFACTS_TABLE: StrictInjectionKey<RetrieveArtifactsTable> = Symbol();
export const REPORT_STATE: StrictInjectionKey<Ref<ReportState>> = Symbol();
export const NOTIFY_FAULT: StrictInjectionKey<NotifyFaultCallback> = Symbol();
export const CLEAR_FEEDBACKS: StrictInjectionKey<ClearFeedbacksCallback> = Symbol();
export const IS_EXPORT_ALLOWED: StrictInjectionKey<Ref<boolean>> = Symbol();
export const WIDGET_ID: StrictInjectionKey<number> = Symbol();
export const IS_USER_ADMIN: StrictInjectionKey<boolean> = Symbol();
export const DOCUMENTATION_BASE_URL: StrictInjectionKey<string> = Symbol();
export const GET_COLUMN_NAME: StrictInjectionKey<GetColumnName> = Symbol();
export const EMITTER: StrictInjectionKey<EmitterProvider> = Symbol();
export const IS_MULTIPLE_QUERY_SUPPORTED: StrictInjectionKey<boolean> = Symbol();
export const GET_SUGGESTED_QUERIES: StrictInjectionKey<SuggestedQueriesGetter> = Symbol();
export const DASHBOARD_TYPE: StrictInjectionKey<DashboardType> = Symbol();
