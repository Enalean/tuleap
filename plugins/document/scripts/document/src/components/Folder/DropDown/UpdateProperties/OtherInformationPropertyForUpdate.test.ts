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

import type { MockInstance } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import OtherInformationPropertiesForUpdate from "./OtherInformationPropertiesForUpdate.vue";
import { TYPE_FILE } from "../../../../constants";
import type { Item, ItemFile, ListValue, Property } from "../../../../type";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import { IS_OBSOLESCENCE_DATE_PROPERTY_USED, PROJECT } from "../../../../configuration-keys";
import { ProjectBuilder } from "../../../../../tests/builders/ProjectBuilder";
import { PROJECT_PROPERTIES } from "../../../../injection-keys";
import { ref } from "vue";
import { okAsync } from "neverthrow";

vi.mock("../../../../helpers/emitter");

describe("OtherInformationPropertiesForUpdate", () => {
    let load_properties: MockInstance;

    beforeEach(() => {
        load_properties = vi.fn();
        load_properties.mockReset();
    });

    function createWrapper(
        is_obsolescence_date_property_used: boolean,
        has_loaded_properties: boolean,
        item: Item,
        propertyToUpdate: Array<Property>,
    ): VueWrapper<InstanceType<typeof OtherInformationPropertiesForUpdate>> {
        return shallowMount(OtherInformationPropertiesForUpdate, {
            props: {
                currentlyUpdatedItem: item,
                value: "",
                propertyToUpdate,
                document_properties: { loadProjectProperties: load_properties },
            },
            global: {
                ...getGlobalTestOptions({}),
                provide: {
                    [PROJECT.valueOf()]: new ProjectBuilder(101).build(),
                    [IS_OBSOLESCENCE_DATE_PROPERTY_USED.valueOf()]:
                        is_obsolescence_date_property_used,
                    [PROJECT_PROPERTIES.valueOf()]: ref(has_loaded_properties ? [] : null),
                },
            },
        });
    }

    describe("Custom properties", () => {
        it(`Given custom component are loading
        Then it displays spinner`, () => {
            const properties: Array<Property> = [];
            const item = {
                properties,
                type: TYPE_FILE,
                title: "title",
            } as ItemFile;
            load_properties.mockReturnValue(okAsync([]));
            const wrapper = createWrapper(true, false, item, []);

            expect(wrapper.find("[data-test=document-other-information]").exists()).toBeTruthy();
            expect(
                wrapper.find("[data-test=document-other-information-spinner]").exists(),
            ).toBeTruthy();
        });

        it("Load project properties at first load", () => {
            const properties: Array<Property> = [];
            const item = {
                properties,
                type: TYPE_FILE,
                title: "title",
            } as ItemFile;
            load_properties.mockReturnValue(okAsync([]));
            createWrapper(true, false, item, []);

            expect(load_properties).toHaveBeenCalled();
        });
    });

    describe("Other information display", () => {
        it(`Given obsolescence date is enabled for project
            Then we should display the obsolescence date component`, () => {
            const properties: Array<Property> = [
                {
                    short_name: "obsolescence_date",
                    value: null,
                } as Property,
            ];
            const item = {
                properties,
                type: TYPE_FILE,
                title: "title",
            } as ItemFile;

            const wrapper = createWrapper(true, true, item, []);

            expect(wrapper.find("[data-test=document-other-information]").exists()).toBeTruthy();
        });

        it(`Given project has custom properties
            Then we should display the other information section`, () => {
            const list_value: Array<ListValue> = [
                {
                    id: 103,
                } as ListValue,
            ];
            const properties: Array<Property> = [
                {
                    short_name: "field_1234",
                    list_value,
                    type: "list",
                    is_multiple_value_allowed: false,
                } as Property,
            ];
            const item = {
                properties,
                type: TYPE_FILE,
                title: "title",
            } as ItemFile;

            const property = { short_name: "field_1234" } as Property;
            const wrapper = createWrapper(false, true, item, [property]);

            expect(wrapper.find("[data-test=document-other-information]").exists()).toBeTruthy();
        });

        it(`Given obsolescence date is disabled for project and given no properties are provided
            Then other information section is not rendered`, () => {
            const properties: Array<Property> = [];
            const item = {
                properties,
                type: TYPE_FILE,
                title: "title",
            } as ItemFile;

            const wrapper = createWrapper(false, true, item, []);

            expect(wrapper.find("[data-test=document-other-information]").exists()).toBeFalsy();
        });
    });
});
