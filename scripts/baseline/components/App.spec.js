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
import { restore, rewire$getBaseline } from "../api/rest-querier";
import App from "./App.vue";
import SkeletonBaseline from "./SkeletonBaseline.vue";
import SimplifiedBaseline from "./SimplifiedBaseline.vue";

describe("Baseline", () => {
    let gettext;
    let getBaseline;

    let getBaselineResolve;
    let getBaselineReject;

    let wrapper;

    beforeEach(() => {
        gettext = jasmine.createSpy("gettext");
        getBaseline = jasmine.createSpy("getBaseline");
        rewire$getBaseline(getBaseline);
    });

    beforeEach(async () => {
        getBaseline.and.returnValue(
            new Promise((resolve, reject) => {
                getBaselineResolve = resolve;
                getBaselineReject = reject;
            })
        );

        wrapper = shallowMount(App, {
            localVue,
            mocks: {
                $gettext: gettext
            },
            propsData: {
                artifact_id: 1,
                date: "2019-03-24"
            }
        });

        await wrapper.vm.$nextTick();
    });

    afterEach(restore);

    describe("when fetching baseline", () => {
        it("does not show error message", () => {
            expect(wrapper.contains('[data-test-type="error-message"]')).toBeFalsy();
        });

        it("shows skeleton", () => {
            expect(wrapper.contains(SkeletonBaseline)).toBeTruthy();
        });
    });

    describe("when baseline fetch fail", () => {
        beforeEach(async () => {
            gettext.withArgs("Cannot fetch data").and.returnValue("Cannot fetch data (translated)");
            getBaselineReject("rejection");
            await wrapper.vm.$nextTick();
        });

        it("shows error message", () => {
            expect(wrapper.find('[data-test-type="error-message"]').text()).toContain(
                "Cannot fetch data (translated)"
            );
        });
        it("does not show skeleton", () => {
            expect(wrapper.contains(SkeletonBaseline)).toBeFalsy();
        });
        it("does not show simplified baseline", () => {
            expect(wrapper.contains(SimplifiedBaseline)).toBeFalsy();
        });
    });

    describe("when baseline fetch is successful", () => {
        beforeEach(async () => {
            getBaselineResolve({
                artifact_title: "I want to",
                last_modification_date_before_baseline_date: 1234
            });
            await wrapper.vm.$nextTick();
        });

        it("does not show error message", () => {
            expect(wrapper.contains('[data-test-type="error-message"]')).toBeFalsy();
        });

        it("does not show skeleton", () => {
            expect(wrapper.contains(SkeletonBaseline)).toBeFalsy();
        });

        it("shows simplified baseline", () => {
            expect(wrapper.contains(SimplifiedBaseline)).toBeTruthy();
        });
    });
});
