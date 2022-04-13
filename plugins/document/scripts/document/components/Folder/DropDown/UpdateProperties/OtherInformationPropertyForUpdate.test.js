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

import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import localVue from "../../../../helpers/local-vue";
import OtherInformationPropertiesForUpdate from "./OtherInformationPropertiesForUpdate.vue";
import { TYPE_FILE } from "../../../../constants";
import emitter from "../../../../helpers/emitter";

jest.mock("../../../../helpers/emitter");

describe("OtherInformationPropertiesForUpdate", () => {
    let other_properties, store;
    beforeEach(() => {
        store = createStoreMock(
            {},
            {
                properties: { has_loaded_properties: true },
                configuration: { is_obsolescence_date_property_used: true },
            }
        );

        other_properties = (props = {}) => {
            return shallowMount(OtherInformationPropertiesForUpdate, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });
    describe("Custom properties", () => {
        it(`Given custom component are loading
        Then it displays spinner`, async () => {
            const wrapper = other_properties({
                currentlyUpdatedItem: {
                    properties: [],
                    status: 100,
                    type: TYPE_FILE,
                    title: "title",
                },
                propertyToUpdate: [],
                value: "",
            });

            store.state = {
                configuration: { is_obsolescence_date_property_used: true },
                properties: {
                    has_loaded_properties: false,
                },
            };
            await wrapper.vm.$nextTick();

            expect(wrapper.find("[data-test=document-other-information]").exists()).toBeTruthy();
            expect(
                wrapper.find("[data-test=document-other-information-spinner]").exists()
            ).toBeTruthy();
        });

        it("Load project properties at first load", async () => {
            store.state.properties = {
                has_loaded_properties: false,
            };

            const wrapper = other_properties({
                currentlyUpdatedItem: {
                    properties: [],
                    status: 100,
                    type: TYPE_FILE,
                    title: "title",
                },
                propertyToUpdate: [],
                value: "",
            });

            emitter.emit("show-new-document-modal", {
                detail: { parent: store.state.current_folder },
            });
            await wrapper.vm.$nextTick().then(() => {});

            expect(store.dispatch).toHaveBeenCalledWith("properties/loadProjectProperties");
        });
    });

    describe("Other information display", () => {
        it(`Given obsolescence date is enabled for project
            Then we should display the obsolescence date component`, async () => {
            const wrapper = other_properties({
                currentlyUpdatedItem: {
                    properties: [
                        {
                            short_name: "obsolescence_date",
                            value: null,
                        },
                    ],
                    obsolescence_date: null,
                    type: TYPE_FILE,
                    title: "title",
                },
                propertyToUpdate: [],
                value: "",
            });

            store.state = {
                configuration: { is_obsolescence_date_property_used: true },
                properties: {
                    has_loaded_properties: true,
                },
            };
            await wrapper.vm.$nextTick();

            expect(wrapper.find("[data-test=document-other-information]").exists()).toBeTruthy();
        });

        it(`Given project has custom properties
            Then we should display the other information section`, () => {
            const wrapper = other_properties({
                currentlyUpdatedItem: {
                    properties: [
                        {
                            short_name: "field_1234",
                            list_value: [
                                {
                                    id: 103,
                                },
                            ],
                            type: "list",
                            is_multiple_value_allowed: false,
                        },
                    ],
                    status: 100,
                    type: TYPE_FILE,
                    title: "title",
                },
                propertyToUpdate: [{ id: 1 }],
                value: "",
            });

            store.state = {
                configuration: { is_obsolescence_date_property_used: false },
                properties: {
                    has_loaded_properties: true,
                },
            };

            expect(wrapper.find("[data-test=document-other-information]").exists()).toBeTruthy();
        });

        it(`Given obsolescence date is disabled for project and given no properties are provided
            Then other information section is not rendered`, async () => {
            const wrapper = other_properties({
                currentlyUpdatedItem: {
                    properties: [],
                    status: 100,
                    type: TYPE_FILE,
                    title: "title",
                },
                propertyToUpdate: [],
                value: "",
            });

            store.state = {
                configuration: { is_obsolescence_date_property_used: false },
                properties: {
                    has_loaded_properties: true,
                },
            };
            await wrapper.vm.$nextTick();

            expect(wrapper.find("[data-test=document-other-information]").exists()).toBeFalsy();
        });
    });
});
