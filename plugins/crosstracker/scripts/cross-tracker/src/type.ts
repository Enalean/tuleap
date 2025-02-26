/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import type { TrackerProjectRepresentation } from "@tuleap/plugin-tracker-rest-api-types";

export type Query = {
    readonly id: string;
    readonly tql_query: string;
    readonly title: string;
    readonly description: string;
};

export type Artifact = {
    readonly id: number;
    readonly title: string;
    readonly badge: {
        readonly uri: string;
        readonly cross_ref: string;
        readonly color: string;
    };
    formatted_last_update_date?: string;
    readonly last_update_date: string;
    readonly status: string;
    readonly submitted_by: User;
    readonly assigned_to: ReadonlyArray<User>;
    readonly project: TrackerProjectRepresentation;
};

export type User = {
    readonly id: number;
    readonly display_name: string;
    readonly user_url: string;
};

export type WidgetData = {
    readonly widget_id: number;
    readonly is_widget_admin: boolean;
    readonly documentation_base_url: string;
    readonly is_multiple_query_supported: boolean;
    readonly dashboard_type: string;
};
