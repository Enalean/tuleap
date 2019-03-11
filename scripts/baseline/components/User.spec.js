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
import { restore, rewire$getUser } from "../api/rest-querier";
import User from "./User.vue";

describe("User", () => {
    const skeleton_selector = '[data-test-type="skeleton"]';
    const loading_error_selector = '[data-test-type="loading-error"]';
    let getUserResolve;
    let getUserReject;
    let wrapper;

    beforeEach(async () => {
        const getUser = jasmine.createSpy("getUser");
        getUser.and.returnValue(
            new Promise((resolve, reject) => {
                getUserResolve = resolve;
                getUserReject = reject;
            })
        );
        rewire$getUser(getUser);

        wrapper = shallowMount(User, {
            localVue,
            propsData: { id: 1 }
        });
        await wrapper.vm.$nextTick();
    });

    afterEach(restore);

    it("shows skeleton", () => {
        expect(wrapper.contains(skeleton_selector)).toBeTruthy();
    });

    describe("when loading is successful", () => {
        beforeEach(async () => {
            getUserResolve({
                id: 1,
                username: "John Doe"
            });
            await wrapper.vm.$nextTick();
        });

        it("does not show skeleton", () => {
            expect(wrapper.contains(skeleton_selector)).toBeFalsy();
        });

        it("shows user name", () => {
            expect(wrapper.text()).toContain("John Doe");
        });
    });

    describe("when loading fail", () => {
        beforeEach(async () => {
            getUserReject();
            await wrapper.vm.$nextTick();
        });

        it("does not show skeleton", () => {
            expect(wrapper.contains(skeleton_selector)).toBeFalsy();
        });

        it("shows loading error", () => {
            expect(wrapper.contains(loading_error_selector)).toBeTruthy();
        });
    });
});
