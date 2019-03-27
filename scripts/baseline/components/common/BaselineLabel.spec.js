/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

describe("BaselineLabel", () => {
    let wrapper;

    beforeEach(() => {
        DateFormatter.setOptions({
            user_locale: "fr_FR",
            user_timezone: "Europe/Paris",
            format: "d/m/Y H:i"
        });

        const past_snapshot_date = new Date();
        past_snapshot_date.setDate(past_snapshot_date.getDate() - 4);

        wrapper = mount(BaselineLabel, {
            localVue,
            propsData: {
                baseline: create("baseline", {
                    id: 1,
                    name: "Baseline V1",
                    snapshot_date: past_snapshot_date.toISOString(),
                    author: create("user", { username: "Alita" })
                })
            }
        });
    });

    it("shows baseline information", () => {
        expect(wrapper.text()).toMatch(
            /Baseline #1 - Baseline V1\s*Created by\s*Alita\s*il y a 4 jours/
        );
    });
});
