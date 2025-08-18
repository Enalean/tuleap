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
import { getGlobalTestOptions } from "../support/global-options-for-tests";
import IndexPage from "./IndexPage.vue";

describe("IndexPage", () => {
    let wrapper, show_modal_mock;

    beforeEach(() => {
        show_modal_mock = jest.fn();

        const baselines = [
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

        wrapper = shallowMount(IndexPage, {
            props: { project_id: 1 },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        dialog_interface: {
                            namespaced: true,
                            mutations: {
                                showModal: show_modal_mock,
                            },
                        },
                        baselines: {
                            namespaced: true,
                            state: {
                                baselines,
                                are_baselines_loading: false,
                            },
                        },
                        comparisons: {
                            namespaced: true,
                            state: {
                                comparisons: [],
                            },
                        },
                    },
                }),
                provide: { is_admin: true },
            },
        });
    });

    describe("when clicking on new baseline button", () => {
        it("shows new modal", () => {
            wrapper.get("[data-test-action=new-baseline]").trigger("click");
            expect(show_modal_mock).toHaveBeenCalled();
        });
    });

    describe("when some baselines are available", () => {
        describe("when clicking on show comparison button", () => {
            it("shows new modal", () => {
                wrapper.get("[data-test-action=show-comparison]").trigger("click");
                expect(show_modal_mock).toHaveBeenCalled();
            });
        });
    });
});
