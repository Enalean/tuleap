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
import WidgetModalTable from "./WidgetModalTable.vue";
import type { Artifact, PersonalTime } from "@tuleap/plugin-timetracking-rest-api-types";
import { getGlobalTestOptions } from "../../../tests/global-options-for-tests";

describe("Given a personal timetracking widget modal", () => {
    let is_add_mode: boolean;
    let current_times: PersonalTime[];

    function getWidgetModalTableInstance(): VueWrapper {
        return shallowMount(WidgetModalTable, {
            global: {
                ...getGlobalTestOptions({
                    initialState: {
                        root: {
                            is_add_mode: is_add_mode,
                            current_times: current_times,
                        },
                    },
                }),
            },
            props: {
                artifact: {} as Artifact,
                timeData: {} as PersonalTime,
            },
        });
    }

    beforeEach(() => {
        is_add_mode = false;
        current_times = [{ minutes: 660 }] as PersonalTime[];
    });

    it("When add mode is false, then complete table should be displayed", () => {
        const wrapper = getWidgetModalTableInstance();
        expect(wrapper.find("[data-test=table-body-with-row]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=edit-time-with-row]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=table-body-without-row]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=edit-time-without-row]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=table-foot]").exists()).toBeTruthy();
    });

    it("When add mode is true, then table edit and rows should be displayed", () => {
        is_add_mode = true;
        const wrapper = getWidgetModalTableInstance();
        expect(wrapper.find("[data-test=table-body-with-row]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=edit-time-with-row]").exists()).toBeTruthy();
    });

    describe("Given an empty state", () => {
        beforeEach(() => {
            current_times.length = 0;
        });

        it("When add mode is false, then empty table should be displayed", () => {
            const wrapper = getWidgetModalTableInstance();
            expect(wrapper.find("[data-test=table-body-with-row]").exists()).toBeFalsy();
            expect(wrapper.find("[data-test=table-body-without-row]").exists()).toBeTruthy();
        });

        it("When in add mode, then edit row should be displayed", () => {
            is_add_mode = true;
            const wrapper = getWidgetModalTableInstance();
            expect(wrapper.find("[data-test=edit-time-without-row]").exists()).toBeTruthy();
            expect(wrapper.find("[data-test=table-foot]").exists()).toBeFalsy();
        });
    });
});
