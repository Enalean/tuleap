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

import { shallowMount } from "@vue/test-utils";
import CriterionList from "./CriterionList.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import localVue from "../../../helpers/local-vue";
import type { ConfigurationState } from "../../../store/configuration";
import type { SearchCriterionList } from "../../../type";

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
            localVue,
            propsData: {
                criterion,
                value: "folder",
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            user_can_create_wiki: true,
                        } as ConfigurationState,
                    },
                }),
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("should warn parent component when user is changing selection", () => {
        const wrapper = shallowMount(CriterionList, {
            localVue,
            propsData: {
                criterion,
                value: "wiki",
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            user_can_create_wiki: true,
                        } as ConfigurationState,
                    },
                }),
            },
        });

        wrapper.find("[data-test=option-folder]").setSelected();
        expect(wrapper.emitted().input).toStrictEqual([["folder"]]);
    });
});
