/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import type {
    PredefinedTimePeriodSelect,
    PredefinedTimePeriod,
} from "@tuleap/plugin-timetracking-predefined-time-periods";
import { TODAY } from "@tuleap/plugin-timetracking-predefined-time-periods";

export type PredefinedTimePeriodsStub = PredefinedTimePeriodSelect & {
    getCurrentlySelectedPredefinedTimePeriod(): PredefinedTimePeriod | "";
};

const noop = (): void => {
    // Do nothing
};

export const PredefinedTimePeriodsStub = {
    build: (): PredefinedTimePeriodsStub => {
        const initial_selection: PredefinedTimePeriod = TODAY;
        let selected_predefined_time_period: PredefinedTimePeriod | "" = initial_selection;

        return {
            onselection: noop,
            selected_time_period: initial_selection,
            resetSelection(): void {
                selected_predefined_time_period = "";
            },
            getCurrentlySelectedPredefinedTimePeriod: () => selected_predefined_time_period,
        };
    },
};
