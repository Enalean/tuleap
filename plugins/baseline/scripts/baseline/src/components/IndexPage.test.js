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
import localVue from "../support/local-vue.ts";
import IndexPage from "./IndexPage.vue";
import { createStoreMock } from "../support/store-wrapper.test-helper";
import store_options from "../store/store_options";

describe("IndexPage", () => {
    let $store;
    let wrapper;

    beforeEach(() => {
        $store = createStoreMock(store_options);
        const router = new VueRouter();

        wrapper = shallowMount(IndexPage, {
            propsData: { project_id: 1 },
            localVue,
            router,
            mocks: {
                $store,
            },
            provide: () => ({ is_admin: true }),
        });
    });

    describe("when clicking on new baseline button", () => {
        beforeEach(() => wrapper.get('[data-test-action="new-baseline"]').trigger("click"));

        it("shows new modal", () => {
            expect($store.commit).toHaveBeenCalledWith(
                "dialog_interface/showModal",
                expect.any(Object),
            );
        });
    });

    describe("when some baselines are available", () => {
        beforeEach(() => {
            $store.state.baselines.baselines = [
                {
                    id: 1,
                    name: "Baseline label 1",
                    artifact_id: 9,
                    snapshot_date: "2019-03-22T10:01:48+00:00",
                    author_id: 3,
                },
                {
                    id: 2,
                    name: "Baseline label 2",
                    artifact_id: 9,
                    snapshot_date: "2019-03-22T10:01:48+00:00",
                    author_id: 3,
                },
            ];
            $store.state.baselines.are_baselines_loading = false;
        });

        describe("when clicking on show comparison button", () => {
            beforeEach(() => wrapper.get('[data-test-action="show-comparison"]').trigger("click"));

            it("shows new modal", () => {
                expect($store.commit).toHaveBeenCalledWith(
                    "dialog_interface/showModal",
                    expect.any(Object),
                );
            });
        });
    });
});
