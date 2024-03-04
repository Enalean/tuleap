/**
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

import { shallowMount } from "@vue/test-utils";
import TodayIndicator from "./TodayIndicator.vue";
import { createRoadmapLocalVue } from "../../helpers/local-vue-for-test";
import { TimePeriodMonth } from "../../helpers/time-period-month";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { TimeperiodState } from "../../store/timeperiod/type";
import type { RootState } from "../../store/type";
import { DateTime, Settings } from "luxon";

Settings.defaultZone = "UTC";

describe("TodayIndicator", () => {
    it("Displays a div with a left position depending on the time period", async () => {
        const now = DateTime.fromISO("2020-04-14T22:00:00.000Z");
        const locale_bcp47 = "en-US";
        const wrapper = shallowMount(TodayIndicator, {
            localVue: await createRoadmapLocalVue(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        locale_bcp47,
                        now,
                        timeperiod: {} as TimeperiodState,
                    } as RootState,
                    getters: {
                        "timeperiod/time_period": new TimePeriodMonth(
                            DateTime.fromISO("2020-03-31T22:00:00.000Z"),
                            DateTime.fromISO("2020-04-30T22:00:00.000Z"),
                            locale_bcp47,
                        ),
                    },
                }),
            },
        });

        const expected_today_date = now.setLocale("en-US").toLocaleString({
            day: "numeric",
            month: "long",
            year: "numeric",
        });

        expect(wrapper.classes()).toContain("roadmap-gantt-today");
        expect((wrapper.element as HTMLElement).title).toBe(`Today: ${expected_today_date}`);
        expect((wrapper.element as HTMLElement).style.left).toBe("146px");
    });
});
