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
import localVue from "../support/local-vue.js";
import App from "./App.vue";
import router from "../router";

describe("App", () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(App, {
            localVue,
            router
        });
    });

    describe("when navigating to BaselinesPage", () => {
        beforeEach(() =>
            router.push({ name: "BaselinesPage", params: { project_name: "my project" } }));

        it("shows only one breadcrumb item", () => {
            expect(wrapper.findAll(".breadcrumb-item").length).toEqual(1);
        });
    });

    describe("when navigating to BaselineContentPage", () => {
        beforeEach(() =>
            router.push({ name: "BaselineContentPage", params: { baseline_id: "1" } }));

        it("shows 2 breadcrumb items", () => {
            expect(wrapper.findAll(".breadcrumb-item").length).toEqual(2);
        });
    });

    describe("#changeTitle", () => {
        beforeEach(() => wrapper.vm.changeTitle("new title"));

        it('changes document title and suffix with "Tuleap"', () => {
            expect(document.title).toEqual("new title - Tuleap");
        });
    });
});
