/*
 * Copyright Enalean (c) 2018 - present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
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

import { createPinia, setActivePinia } from "pinia";
import { usePersonalTimetrackingWidgetStore } from "./index";

describe("Widget", () => {
    let store;
    beforeEach(() => {
        setActivePinia(createPinia());
        store = usePersonalTimetrackingWidgetStore();
    });

    describe("Call sums", () => {
        describe("Given a widget with state initialisation", () => {
            it("Then we add times, total sum must change too", () => {
                let times = [
                    [
                        {
                            artifact: {},
                            project: {},
                            minutes: 20,
                        },
                        {
                            artifact: {},
                            project: {},
                            minutes: 20,
                        },
                    ],
                ];

                store.loadAChunkOfTimes([times, times.length]);
                expect(store.get_formatted_total_sum).toBe("00:40");
            });

            it("Then we add times, aggregated time must change too", () => {
                let times = [
                    {
                        artifact: {},
                        project: {},
                        minutes: 20,
                    },
                    {
                        artifact: {},
                        project: {},
                        minutes: 20,
                    },
                ];

                expect(store.get_formatted_aggregated_time(times)).toBe("00:40");
            });
        });
    });
});
