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
import { shallowMount, mount } from "@vue/test-utils";
import CriterionNumber from "./CriterionNumber.vue";
import emitter from "../../../helpers/emitter";

describe("CriterionNumber", () => {
    let emitMock: Mock;

    beforeEach(() => {
        emitMock = vi.spyOn(emitter, "emit");
    });

    it("should render the component", () => {
        const wrapper = shallowMount(CriterionNumber, {
            props: {
                criterion: {
                    name: "id",
                    label: "Id",
                },
                value: "123",
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("should warn parent component when user is changing text", () => {
        const wrapper = mount(CriterionNumber, {
            props: {
                criterion: {
                    name: "id",
                    label: "Id",
                },
                value: "123",
            },
        });

        wrapper.find("[data-test=document-criterion-number-id]").setValue("256");
        expect(emitMock).toHaveBeenCalledWith("update-criteria", {
            criteria: "id",
            value: "256",
        });
    });
});
