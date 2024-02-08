/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { describe, beforeEach, it, expect } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import WidgetArtifactTable from "./WidgetArtifactTable.vue";
import type { PersonalTime } from "@tuleap/plugin-timetracking-rest-api-types";
import { getGlobalTestOptions } from "../../tests/global-options-for-tests";

describe("Given a personal timetracking widget", () => {
    let times: PersonalTime[][];
    let is_loading: boolean;
    let error_message: string;
    let pagination_offset: number;
    let total_times: number;
    let is_loaded: boolean;

    function getWidgetArtifactTableInstance(): VueWrapper {
        return shallowMount(WidgetArtifactTable, {
            global: {
                ...getGlobalTestOptions({
                    initialState: {
                        root: {
                            times: times,
                            is_loading: is_loading,
                            error_message: error_message,
                            pagination_offset: pagination_offset,
                            total_times: total_times,
                            is_loaded: is_loaded,
                        },
                    },
                }),
            },
        });
    }

    beforeEach(() => {
        times = [[{ step: "time" }]] as PersonalTime[][];
        is_loading = false;
        error_message = "";
        pagination_offset = 10;
        total_times = 5;
        is_loaded = true;
        error_message = "";
    });
    it("When no error and result can be displayed, then complete table should be displayed", () => {
        const wrapper = getWidgetArtifactTableInstance();
        expect(wrapper.find("[data-test=alert-danger]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=timetracking-loader]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=artifact-table]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=load-more]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=table-foot]").exists()).toBeTruthy();
    });

    it("When rest error and more times can be load, then danger message and load more button should be displayed", () => {
        error_message = "error";
        pagination_offset = 3;
        const wrapper = getWidgetArtifactTableInstance();
        expect(wrapper.find("[data-test=alert-danger]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=load-more]").exists()).toBeTruthy();
    });

    it("When widget is loading and result can't be displayed, then loader should be displayed but not table", () => {
        is_loading = true;
        is_loaded = false;
        const wrapper = getWidgetArtifactTableInstance();
        expect(wrapper.find("[data-test=timetracking-loader]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=artifact-table]").exists()).toBeFalsy();
    });

    it("When no times, then table with empty tab should be displayed", () => {
        times = [];
        const wrapper = getWidgetArtifactTableInstance();
        expect(wrapper.find("[data-test=empty-tab]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=table-foot]").exists()).toBeFalsy();
    });
});
