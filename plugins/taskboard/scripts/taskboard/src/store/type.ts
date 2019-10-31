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
 */

import { UserState } from "./user/type";
import { ColumnDefinition } from "../type";
import { ErrorState } from "./error/type";
import { FullscreenState } from "./fullscreen/type";
import { SwimlaneState } from "./swimlane/type";

export interface State {
    admin_url: string;
    has_content: boolean;
    columns: Array<ColumnDefinition>;
    milestone_id: number;
    milestone_title: string;
    are_closed_items_displayed: boolean;
}

export interface RootState extends State {
    readonly error: ErrorState;
    readonly fullscreen: FullscreenState;
    readonly swimlane: SwimlaneState;
    readonly user: UserState;
}
