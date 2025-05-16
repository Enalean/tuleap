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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import OtherInformationPropertiesForCreate from "./OtherInformationPropertiesForCreate.vue";
import { TYPE_FILE } from "../../../../../constants";
import type { ItemFile, Property } from "../../../../../type";
import { getGlobalTestOptions } from "../../../../../helpers/global-options-for-test";
import type { ConfigurationState } from "../../../../../store/configuration";
import type { PropertiesState } from "../../../../../store/properties/module";

jest.mock("../../../../../helpers/emitter");

describe("OtherInformationPropertiesForCreate", () => {
    let load_properties: jest.Mock;

    beforeEach(() => {
        load_properties = jest.fn();
    });

    function createWrapper(
        value: string,
        is_obsolescence_date_property_used: boolean,
        has_loaded_properties: boolean,
    ): VueWrapper<InstanceType<typeof OtherInformationPropertiesForCreate>> {
        load_properties.mockReset();
        const properties: Array<Property> = [];
        return shallowMount(OtherInformationPropertiesForCreate, {
            props: {
                currentlyUpdatedItem: {
                    properties: properties,
                    obsolescence_date: null,
                    type: TYPE_FILE,
                    title: "title",
                } as ItemFile,
                value,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                is_obsolescence_date_property_used,
                            } as unknown as ConfigurationState,
                            namespaced: true,
                        },
                        properties: {
                            state: {
                                has_loaded_properties,
                            } as unknown as PropertiesState,
                            namespaced: true,
                            actions: {
                                loadProjectProperties: load_properties,
                            },
                        },
                    },
                }),
            },
        });
    }

    it(`Given obsolescence date is enabled for project
        Then we should display the obsolescence date component`, () => {
        const wrapper = createWrapper("", true, true);

        expect(wrapper.find("[data-test=document-other-information]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-other-information-spinner]").exists()).toBeFalsy();
    });

    it(`Given obsolescence date is disabled for project
        Then obsolescence date component is not rendered`, () => {
        const wrapper = createWrapper("", false, true);

        expect(wrapper.find("[data-test=document-other-information]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-other-information-spinner]").exists()).toBeFalsy();
    });

    it(`Given custom component are loading
        Then it displays spinner`, () => {
        const wrapper = createWrapper("", true, false);

        expect(wrapper.find("[data-test=document-other-information]").exists()).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-other-information-spinner]").exists(),
        ).toBeTruthy();
    });

    it("Load project properties at first load", () => {
        createWrapper("", true, false);

        expect(load_properties).toHaveBeenCalled();
    });
});
