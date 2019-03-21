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

import { shallowMount } from "@vue/test-utils";
import localVue from "../../support/local-vue.js";
import BaselineLabel from "./BaselineLabel.vue";
import { create } from "../../support/factories";

describe("BaselineLabel", () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(BaselineLabel, {
            localVue,
            propsData: {
                baseline: create("baseline", {
                    id: 1,
                    name: "Baseline V1",
                    snapshot_date: "09/02/1995",
                    author: create("user", { username: "Alita" })
                })
            }
        });
    });

    it("shows baseline information", () => {
        expect(wrapper.text()).toEqual("Baseline #1 - Baseline V1 09/02/1995 Created by Alita");
    });
});
