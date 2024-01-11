/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import { mount } from "@vue/test-utils";
import { createLocalVueForTests } from "../../support/local-vue";
import BaselineLabel from "./BaselineLabel.vue";
import DateFormatter from "../../support/date-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { Baseline, User } from "../../type";

describe("BaselineLabel", () => {
    it("shows baseline information", async () => {
        DateFormatter.setOptions({
            user_locale: "en_EN",
            user_timezone: "Europe/London",
            format: "d/m/Y H:i",
        });

        const past_snapshot_date = new Date("2019-05-02T06:48:22+00:00");

        const wrapper = mount(BaselineLabel, {
            propsData: {
                baseline: {
                    id: 1,
                    name: "Baseline V1",
                    snapshot_date: past_snapshot_date.toISOString(),
                    author_id: 9,
                } as Baseline,
            },
            localVue: await createLocalVueForTests(),
            mocks: {
                $store: createStoreMock({
                    state: {
                        users_by_id: {
                            9: { display_name: "Alita" } as User,
                        },
                    },
                }),
            },
        });

        expect(wrapper.text()).toMatch(
            /Baseline #1 - Baseline V1\s*Created by\s*Alita\s*on\s*May 2, 2019 7:48 AM/,
        );
    });
});
