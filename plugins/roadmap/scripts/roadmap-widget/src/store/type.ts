/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import type { TasksState } from "./tasks/type";
import type { IterationsState } from "./iterations/type";
import type { TimeperiodState } from "./timeperiod/type";
import type { VueGettextProvider } from "../helpers/vue-gettext-provider";
import type { DateTime } from "luxon";

export interface State {
    readonly locale_bcp47: string;
    readonly should_load_lvl1_iterations: boolean;
    readonly should_load_lvl2_iterations: boolean;
    readonly now: DateTime;
    readonly gettext_provider: VueGettextProvider;
    is_loading: boolean;
    should_display_empty_state: boolean;
    should_display_error_state: boolean;
    error_message: string;
    show_closed_elements: boolean;
}

export interface RootState extends State {
    readonly tasks: TasksState;
    readonly iterations: IterationsState;
    readonly timeperiod: TimeperiodState;
}
