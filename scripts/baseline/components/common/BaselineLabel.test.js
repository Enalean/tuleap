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
import localVue from "../../support/local-vue.js";
import BaselineLabel from "./BaselineLabel.vue";
import { create } from "../../support/factories";
import DateFormatter from "../../support/date-utils";
import { createStoreMock } from "../../support/store-wrapper.test-helper";
import store_options from "../../store/store_options";

describe("BaselineLabel", () => {
    let $store;
    let wrapper;

    beforeEach(() => {
        $store = createStoreMock(store_options);
        $store.getters.findUserById = () => create("user", { display_name: "Alita" });

        DateFormatter.setOptions({
            user_locale: "en_EN",
            user_timezone: "Europe/London",
            format: "d/m/Y H:i",
        });

        const past_snapshot_date = new Date("2019-05-02T06:48:22+00:00");

        wrapper = mount(BaselineLabel, {
            propsData: {
                baseline: create("baseline", {
                    id: 1,
                    name: "Baseline V1",
                    snapshot_date: past_snapshot_date.toISOString(),
                    author_id: 9,
                }),
            },
            localVue,
            mocks: { $store },
        });
    });

    it("shows baseline information", () => {
        expect(wrapper.text()).toMatch(
            /Baseline #1 - Baseline V1\s*Created by\s*Alita\s*on\s*May 2, 2019 7:48 AM/
        );
    });
});
