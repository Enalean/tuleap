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

import { shallowMount } from "@vue/test-utils";
import CriterionList from "./CriterionList.vue";
import type { ConfigurationState } from "../../../store/configuration";
import type { SearchCriterionList } from "../../../type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

jest.mock("../../../helpers/emitter", () => {
    return {
        emit: emitMock,
    };
});

describe("CriterionList", () => {
    const criterion: SearchCriterionList = {
        name: "type",
        label: "Type",
        type: "list",
        options: [
            { value: "", label: "Any" },
            { value: "folder", label: "Folder" },
            { value: "wiki", label: "Wiki" },
        ],
    };
    it("should render the component", () => {
        const wrapper = shallowMount(CriterionList, {
            props: {
                criterion,
                value: "folder",
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                user_can_create_wiki: true,
                            } as unknown as ConfigurationState,
                            namespaced: true,
                        },
                    },
                }),
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("should warn parent component when user is changing selection", () => {
        const wrapper = shallowMount(CriterionList, {
            props: {
                criterion,
                value: "wiki",
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                user_can_create_wiki: true,
                            } as unknown as ConfigurationState,
                            namespaced: true,
                        },
                    },
                }),
            },
        });

        wrapper.find("[data-test=option-folder]").setSelected();
        expect(emitMock).toHaveBeenCalledWith("update-criteria", {
            criteria: "type",
            value: "folder",
        });
    });
});
