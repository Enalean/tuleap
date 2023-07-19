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

const emitMock = jest.fn();

import { shallowMount, mount } from "@vue/test-utils";
import CriterionNumber from "./CriterionNumber.vue";
import { nextTick } from "vue";

jest.mock("../../../helpers/emitter", () => {
    return {
        emit: emitMock,
    };
});

describe("CriterionNumber", () => {
    it("should render the component", async () => {
        const wrapper = shallowMount(CriterionNumber, {
            props: {
                criterion: {
                    name: "id",
                    label: "Id",
                },
                value: "123",
            },
        });

        await nextTick();

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
