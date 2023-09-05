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

import RunJobAction from "./RunJobAction.vue";
import { createLocalVueForTests } from "../../../support/local-vue.js";
import { create } from "../../../support/factories";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

describe("RunJobAction", () => {
    let store;
    const post_action = create("post_action", "presented", { job_url: "http://my-url.test" });
    let wrapper;

    beforeEach(async () => {
        const store_options = {
            state: {
                current_tracker: create("tracker", { project: { id: 1 } }),
                transitionModal: {
                    current_transition: create("transition"),
                    is_modal_save_running: false,
                },
            },
        };

        store = createStoreMock(store_options);

        wrapper = shallowMount(RunJobAction, {
            mocks: { $store: store },
            propsData: { post_action },
            localVue: await createLocalVueForTests(),
        });
    });

    afterEach(() => store.reset());

    const jobUrlInputSelector = '[data-test-type="job-url"]';

    it("shows job url of action", () => {
        expect(wrapper.get(jobUrlInputSelector).element.value).toBe("http://my-url.test");
    });

    describe("when modifying job url", () => {
        beforeEach(() => {
            wrapper.get(jobUrlInputSelector).setValue("http://new-url.test");
        });

        it("updates store", () => {
            expect(store.commit).toHaveBeenCalledWith(
                "transitionModal/updateRunJobPostActionJobUrl",
                {
                    post_action: post_action,
                    job_url: "http://new-url.test",
                },
            );
        });
    });
});
