/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 *
 */

export interface ColumnDefinition {
    id: number;
    label: string;
    color: string;
}

export interface Swimlane {
    card: Card;
}

export interface Card {
    id: number;
    label: string;
    xref: string;
    rank: number;
    color: string;
    background_color: string;
    artifact_html_uri: string;
    assignees: Array<User>;
}

interface RootState {
    admin_url: string;
    has_content: boolean;
    columns: Array<ColumnDefinition>;
    milestone_id: number;
}

interface User {
    id: number;
    avatar_url: string;
    display_name: string;
}
