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

import { shallowMount } from "@vue/test-utils";
import VueRouter from "vue-router";
import NewComparisonModal from "./NewComparisonModal.vue";
import { createLocalVueForTests } from "../../support/local-vue.ts";

describe("NewComparisonModal", () => {
    let baseline_on_same_artifact, router, wrapper;

    beforeEach(async () => {
        router = new VueRouter({
            mode: "abstract",
            routes: [
                { path: "/" },
                { name: "TransientComparisonPage", path: "/path/to/comparison" },
            ],
        });

        const baseline = { id: 1, artifact_id: 10 };
        const baseline_on_other_artifact = { id: 2, artifact_id: 11 };
        baseline_on_same_artifact = { id: 3, artifact_id: 10 };

        wrapper = shallowMount(NewComparisonModal, {
            propsData: {
                baselines: [baseline, baseline_on_other_artifact, baseline_on_same_artifact],
            },
            localVue: await createLocalVueForTests(),
            router,
        });
    });

    const submit_selector = '[data-test-action="submit"]';
    const no_baseline_to_compare_message_selector =
        '[data-test-type="no-baseline-to-compare-message"]';

    it("disables submit", () => {
        expect(wrapper.get(submit_selector).attributes("disabled")).toBe("disabled");
    });

    describe("when user choose a reference baseline", () => {
        beforeEach(() => wrapper.setData({ base_baseline_id: 1 }));

        it("still disable submit", () => {
            expect(wrapper.get(submit_selector).attributes("disabled")).toBe("disabled");
        });

        describe("when no other baseline with same artifact", () => {
            beforeEach(() => wrapper.setData({ base_baseline_id: 2 }));

            it("shows no baseline to compare message", () => {
                expect(wrapper.find(no_baseline_to_compare_message_selector).exists()).toBeTruthy();
            });
        });

        describe("when other baseline with same artifact exists", () => {
            beforeEach(() => wrapper.setData({ base_baseline_id: 1 }));

            it("does not show no baseline to compare message", () => {
                expect(wrapper.find(no_baseline_to_compare_message_selector).exists()).toBeFalsy();
            });

            it("shows all baselines on same artifact", () => {
                expect(wrapper.vm.baselines_to_compare).toEqual([baseline_on_same_artifact]);
            });

            describe("when user choose a baseline to compare", () => {
                beforeEach(() => {
                    wrapper.setData({ baseline_to_compare_id: baseline_on_same_artifact.id });
                    // Seems to be a Vue-test-utils bug.
                    // See https://github.com/vuejs/vue-test-utils/issues/514
                    // TODO Try to upgrade Vue-test-utils to remove this statement
                    wrapper.vm.$forceUpdate();
                });

                it("enables submit", () => {
                    expect(wrapper.get(submit_selector).attributes("disabled")).not.toBe(
                        "disabled",
                    );
                });

                describe("when user submit the form", () => {
                    beforeEach(() => wrapper.get("form").trigger("submit.prevent"));

                    it("navigates to comparison page", () => {
                        expect(router.currentRoute.name).toBe("TransientComparisonPage");
                    });
                });
            });
        });
    });
});
