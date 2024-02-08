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
import { getGlobalTestOptions } from "../../../tests/global-options-for-tests";
import WidgetModalContent from "./WidgetModalContent.vue";
import { usePersonalTimetrackingWidgetStore } from "../../store/root";
import type { Artifact, PersonalTime } from "@tuleap/plugin-timetracking-rest-api-types";
import type { ProjectResponse } from "@tuleap/core-rest-api-types";

describe("Given a personal timetracking widget modal", () => {
    let rest_feedback: { message: string; type: string }, is_add_mode: boolean;

    function getWidgetModalContentInstance(): VueWrapper {
        return shallowMount(WidgetModalContent, {
            props: {
                artifact: {} as Artifact,
                project: {} as ProjectResponse,
                timeData: {} as PersonalTime,
            },
            global: {
                ...getGlobalTestOptions({
                    initialState: {
                        root: {
                            rest_feedback: rest_feedback,
                            is_add_mode: is_add_mode,
                        },
                    },
                }),
            },
        });
    }

    beforeEach(() => {
        rest_feedback = { message: "", type: "" };
        is_add_mode = false;
    });

    it("When there is no REST feedback, then feedback message should not be displayed", () => {
        const wrapper = getWidgetModalContentInstance();
        expect(wrapper.find("[data-test=feedback]").exists()).toBeFalsy();
    });

    it("When there is REST feedback, then feedback message should be displayed", () => {
        rest_feedback.type = "success";
        const wrapper = getWidgetModalContentInstance();
        expect(wrapper.find("[data-test=feedback]").exists()).toBeTruthy();
    });

    it("When add mode button is triggered, then setAddMode should be called", () => {
        const wrapper = getWidgetModalContentInstance();
        const store = usePersonalTimetrackingWidgetStore();

        wrapper.get("[data-test=button-set-add-mode]").trigger("click");
        expect(store.setAddMode).toHaveBeenCalledWith(true);
    });
});
