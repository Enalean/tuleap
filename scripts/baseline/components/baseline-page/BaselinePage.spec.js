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
import { restore, rewire$getBaseline } from "../../api/rest-querier";
import { rewire$presentBaseline } from "../../presenters/baseline";
import BaselinePage from "./BaselinePage.vue";
import { create } from "../../support/factories";

describe("BaselinePage", () => {
    const error_message_selector = '[data-test-type="error-message"]';
    const baseline_header_skeleton_selector = '[data-test-type="baseline-header-skeleton"]';
    const baseline_header_selector = '[data-test-type="baseline-header"]';

    let getBaseline;
    let presentBaseline;
    let wrapper;

    const baseline = create("baseline", { name: "Baseline V1", snapshot_date: "09/02/1995" });
    const user = create("user", { id: baseline.author_id, username: "Alita" });
    const presented_baseline = create("baseline", { ...baseline, author: user });

    beforeEach(() => {
        getBaseline = jasmine.createSpy("getBaseline");
        getBaseline.and.returnValue(Promise.resolve(baseline));
        rewire$getBaseline(getBaseline);

        presentBaseline = jasmine.createSpy("presentBaseline");
        presentBaseline.and.returnValue(presented_baseline);
        rewire$presentBaseline(presentBaseline);

        wrapper = shallowMount(BaselinePage, {
            localVue,
            propsData: { baseline_id: 1 }
        });
    });

    afterEach(restore);

    describe("fetchBaseline", () => {
        let getBaselineResolve;
        let getBaselineReject;

        let presentBaselineResolve;
        let presentBaselineReject;

        beforeEach(() => {
            getBaseline.and.returnValue(
                new Promise((resolve, reject) => {
                    getBaselineResolve = resolve;
                    getBaselineReject = reject;
                })
            );

            presentBaseline.and.returnValue(
                new Promise((resolve, reject) => {
                    presentBaselineResolve = resolve;
                    presentBaselineReject = reject;
                })
            );

            wrapper.vm.fetchBaseline();
        });

        it("shows header skeleton", () => {
            expect(wrapper.contains(baseline_header_skeleton_selector)).toBeTruthy();
        });

        describe("when getBaseline() fail", () => {
            beforeEach(async () => {
                getBaselineReject("rejection");
                await wrapper.vm.$nextTick();
            });

            it("shows error message", () => {
                expect(wrapper.contains(error_message_selector)).toBeTruthy();
            });

            it("does not show header skeleton", () => {
                expect(wrapper.contains(baseline_header_skeleton_selector)).toBeFalsy();
            });
        });

        describe("when getBaseline() is successful", () => {
            describe("when presentBaseline() is successful", () => {
                beforeEach(async () => {
                    getBaselineResolve(baseline);
                    presentBaselineResolve(presented_baseline);

                    await wrapper.vm.$nextTick();
                });

                it("does not show error message", () => {
                    expect(wrapper.contains(error_message_selector)).toBeFalsy();
                });

                it("does not show header skeleton", () => {
                    expect(wrapper.contains(baseline_header_skeleton_selector)).toBeFalsy();
                });

                it("shows baseline header content", () => {
                    expect(wrapper.find(baseline_header_selector).text()).toEqual(
                        "Baseline #1 - Baseline V1 09/02/1995 Created by Alita"
                    );
                });
            });

            describe("when presentBaseline() fail", () => {
                beforeEach(async () => {
                    getBaselineResolve(baseline);
                    presentBaselineReject("rejection");

                    await wrapper.vm.$nextTick();
                });

                it("shows error message", () => {
                    expect(wrapper.contains(error_message_selector)).toBeTruthy();
                });

                it("does not show header skeleton", () => {
                    expect(wrapper.contains(baseline_header_skeleton_selector)).toBeFalsy();
                });
            });
        });
    });
});
