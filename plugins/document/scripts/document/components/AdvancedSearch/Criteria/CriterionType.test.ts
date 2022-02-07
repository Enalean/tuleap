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
import CriterionType from "./CriterionType.vue";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import localVue from "../../../helpers/local-vue";
import type { ConfigurationState } from "../../../store/configuration";

describe("CriterionType", () => {
    it("should render the component", () => {
        const wrapper = shallowMount(CriterionType, {
            localVue,
            propsData: {
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

    it("should omit wiki option if there is no wiki activated in the project", () => {
        const wrapper = shallowMount(CriterionType, {
            localVue,
            propsData: {
                value: "folder",
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            user_can_create_wiki: false,
                        } as ConfigurationState,
                    },
                }),
            },
        });

        expect(wrapper.find("[data-test=type-wiki]").exists()).toBe(false);
    });

    it("should warn parent component when user is changing selection", () => {
        const wrapper = shallowMount(CriterionType, {
            localVue,
            propsData: {
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

        wrapper.find("[data-test=type-folder]").setSelected();
        expect(wrapper.emitted().input).toStrictEqual([["folder"]]);
    });
});
