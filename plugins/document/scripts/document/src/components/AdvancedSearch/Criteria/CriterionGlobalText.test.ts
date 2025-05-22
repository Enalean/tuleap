/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { Mock } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import CriterionGlobalText from "./CriterionGlobalText.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import emitter from "../../../helpers/emitter";

describe("CriterionGlobalText", () => {
    let emitMock: Mock;

    beforeEach(() => {
        emitMock = vi.spyOn(emitter, "emit");
    });

    it("should render the component", () => {
        const wrapper = shallowMount(CriterionGlobalText, {
            props: { value: "Lorem" },
            global: { ...getGlobalTestOptions({}) },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("should warn parent component when user is changing text", () => {
        const wrapper = shallowMount(CriterionGlobalText, {
            props: { value: "Lorem" },
            global: { ...getGlobalTestOptions({}) },
        });

        wrapper.find("[data-test=global-search]").setValue("Lorem ipsum");
        expect(emitMock).toHaveBeenCalledWith("update-global-criteria", "Lorem ipsum");
    });
});
