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
*/

import { shallowMount } from "@vue/test-utils";
import localVue from "../support/local-vue.js";
import Router from "./Router.vue";
import BaselinesPage from "./BaselinesPage.vue";
import NotFoundPage from "./NotFoundPage.vue";

describe("Router", () => {
    let wrapper;

    beforeEach(() => {
        wrapper = shallowMount(Router, {
            localVue
        });
    });

    it("shows App component", () => {
        wrapper.setData({ current_route: "/plugins/baseline/project-name" });
        expect(wrapper.vm.route.component).toEqual(BaselinesPage);
    });

    it("shows Not found component when route does not exist", () => {
        wrapper.setData({ current_route: "/plugins/baselinetypo/project-name" });
        expect(wrapper.vm.route.component).toEqual(NotFoundPage);
    });

    it("shows Not found component when loading baseline details", () => {
        wrapper.setData({ current_route: "/plugins/baselines/2" });
        expect(wrapper.vm.route.component).toEqual(NotFoundPage);
    });
});
