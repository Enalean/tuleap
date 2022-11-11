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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import localVue from "../../../../../helpers/local-vue";
import OtherInformationPropertiesForCreate from "./OtherInformationPropertiesForCreate.vue";
import { TYPE_FILE } from "../../../../../constants";
import type { ItemFile, Property } from "../../../../../type";

jest.mock("../../../../../helpers/emitter");

describe("OtherInformationPropertiesForCreate", () => {
    let store = {
        dispatch: jest.fn(),
    };

    function createWrapper(
        value: string,
        is_obsolescence_date_property_used: boolean,
        has_loaded_properties: boolean
    ): Wrapper<OtherInformationPropertiesForCreate> {
        store = createStoreMock({
            state: {
                configuration: { is_obsolescence_date_property_used },
                properties: { has_loaded_properties },
            },
        });
        const properties: Array<Property> = [];
        return shallowMount(OtherInformationPropertiesForCreate, {
            localVue,
            propsData: {
                currentlyUpdatedItem: {
                    properties: properties,
                    obsolescence_date: null,
                    type: TYPE_FILE,
                    title: "title",
                } as ItemFile,
                value,
            },
            mocks: {
                $store: store,
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
            wrapper.find("[data-test=document-other-information-spinner]").exists()
        ).toBeTruthy();
    });

    it("Load project properties at first load", () => {
        createWrapper("", true, false);

        expect(store.dispatch).toHaveBeenCalledWith("properties/loadProjectProperties");
    });
});
