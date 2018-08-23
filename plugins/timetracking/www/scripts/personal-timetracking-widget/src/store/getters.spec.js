/*
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

import initial_state from "./state.js";
import * as getters from "./getters.js";
import mutations from "./mutations";

describe("Widget", () => {
    let state;
    beforeEach(() => {
        state = { ...initial_state };
    });

    describe("Call sums", () => {
        it("Given a widget with state initialisation, Then we add times, times must change too", () => {
            let times = [
                [
                    {
                        artifact: {},
                        project: {},
                        minutes: 20
                    },
                    {
                        artifact: {},
                        project: {},
                        minutes: 20
                    }
                ]
            ];

            mutations.loadAChunkOfTimes(state, [times, times.length]);
            expect(getters.get_formatted_total_sum(state)).toBe("00:40");
        });

        it("Given a widget with state initialisation, Then we add times, times must change too", () => {
            let times = [
                {
                    artifact: {},
                    project: {},
                    minutes: 20
                },
                {
                    artifact: {},
                    project: {},
                    minutes: 20
                }
            ];

            expect(getters.get_formatted_aggregated_time()(times)).toBe("00:40");
        });
    });
});
