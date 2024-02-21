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

import { describe, it, expect } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import WidgetModalTimes from "./WidgetModalTimes.vue";
import type { Artifact, PersonalTime } from "@tuleap/plugin-timetracking-rest-api-types";
import { getGlobalTestOptions } from "../../../tests/global-options-for-tests";
import type { ProjectResponse } from "@tuleap/core-rest-api-types";

describe("Given a personal timetracking widget modal", () => {
    let current_artifact: Artifact | null;

    function getWidgetModalTimesInstance(): VueWrapper {
        return shallowMount(WidgetModalTimes, {
            global: {
                ...getGlobalTestOptions({}),
            },
            props: {
                artifact: current_artifact,
                project: {} as ProjectResponse,
                times: [] as PersonalTime[],
            },
        });
    }

    it("When current artifact is not empty, then modal content should be displayed", () => {
        current_artifact = {} as Artifact;
        const wrapper = getWidgetModalTimesInstance();
        expect(wrapper.find("[data-test=modal-content]").exists()).toBeTruthy();
    });

    it("When current artifact is empty, then modal content should not be displayed", () => {
        current_artifact = null;
        const wrapper = getWidgetModalTimesInstance();
        expect(wrapper.find("[data-test=modal-content]").exists()).toBeFalsy();
    });
});
