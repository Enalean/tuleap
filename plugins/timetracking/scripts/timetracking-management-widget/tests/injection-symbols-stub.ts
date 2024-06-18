/**
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

import type { StrictInjectionKey } from "@tuleap/vue-strict-inject";
import type { PredefinedTimePeriod } from "@tuleap/plugin-timetracking-predefined-time-periods";
import { TODAY } from "@tuleap/plugin-timetracking-predefined-time-periods";
import type { Query, TimetrackingManagementQuery } from "../src/query/QueryRetriever";
import { RETRIEVE_QUERY } from "../src/injection-symbols";

let start_date = "2024-06-05";
let end_date = "2024-06-05";
let predefined_time_selected: PredefinedTimePeriod | "" = TODAY;

export let injected_query: Query = {
    getQuery: (): TimetrackingManagementQuery => {
        return {
            start_date: start_date,
            end_date: end_date,
            predefined_time_period: predefined_time_selected,
        };
    },
    setQuery: (start: string, end: string, period: "" | PredefinedTimePeriod): void => {
        start_date = start;
        end_date = end;
        predefined_time_selected = period;
    },
};

type StrictInjectImplementation = (key: StrictInjectionKey<unknown>) => unknown;

const injection_symbols: StrictInjectImplementation = (key): unknown => {
    switch (key) {
        case RETRIEVE_QUERY:
            return injected_query;
        default:
            throw new Error("Tried to strictInject a value while it was not mocked");
    }
};

export const StubInjectionSymbols = {
    withDefaults: (): StrictInjectImplementation => {
        start_date = "2024-06-05";
        end_date = "2024-06-05";
        predefined_time_selected = TODAY;
        injected_query = {
            getQuery: (): TimetrackingManagementQuery => {
                return {
                    start_date: start_date,
                    end_date: end_date,
                    predefined_time_period: predefined_time_selected,
                };
            },
            setQuery: (start: string, end: string, period: "" | PredefinedTimePeriod): void => {
                start_date = start;
                end_date = end;
                predefined_time_selected = period;
            },
        };

        return injection_symbols;
    },
};
