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

export type Query = {
    readonly id: string;
    readonly tql_query: string;
    readonly title: string;
    readonly description: string;
    readonly is_default: boolean;
};

export type WidgetData = {
    readonly widget_id: number;
    readonly is_widget_admin: boolean;
    readonly documentation_base_url: string;
    readonly dashboard_type: string;
    readonly title_attribute: string;
    readonly default_title: string;
    readonly can_display_artifact_link: boolean;
};
