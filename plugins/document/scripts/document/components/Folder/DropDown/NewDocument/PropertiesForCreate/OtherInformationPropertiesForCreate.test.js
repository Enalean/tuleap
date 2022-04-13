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
import localVue from "../../../../../helpers/local-vue";
import OtherInformationPropertiesForCreate from "./OtherInformationPropertiesForCreate.vue";
import { TYPE_FILE } from "../../../../../constants";
import emitter from "../../../../../helpers/emitter";

jest.mock("../../../../../helpers/emitter");

describe("OtherInformationPropertiesForCreate", () => {
    let factory, store;
    beforeEach(() => {
        store = createStoreMock(
            {},
            {
                properties: { has_loaded_properties: true },
                configuration: { is_obsolescence_date_property_used: true },
            }
        );

        factory = (props = {}) => {
            return shallowMount(OtherInformationPropertiesForCreate, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });
    it(`Given obsolescence date is enabled for project
        Then we should display the obsolescence date component`, async () => {
        const wrapper = factory(
            {
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
                value: "",
            },
            { parent: 102 }
        );

        await wrapper.vm.$nextTick().then(() => {});

        store.state = {
            configuration: { is_obsolescence_date_property_used: true },
            properties: {
                has_loaded_properties: true,
            },
        };

        expect(wrapper.find("[data-test=document-other-information]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-other-information-spinner]").exists()).toBeFalsy();
    });

    it(`Given obsolescence date is disabled for project
        Then obsolescence date component is not rendered`, async () => {
        const wrapper = factory({
            currentlyUpdatedItem: {
                properties: null,
                status: 100,
                type: TYPE_FILE,
                title: "title",
            },
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
        expect(wrapper.find("[data-test=document-other-information-spinner]").exists()).toBeFalsy();
    });

    it(`Given custom component are loading
        Then it displays spinner`, async () => {
        const wrapper = factory({
            currentlyUpdatedItem: {
                properties: [],
                status: 100,
                type: TYPE_FILE,
                title: "title",
            },
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

        const wrapper = factory({
            currentlyUpdatedItem: {
                properties: [],
                status: 100,
                type: TYPE_FILE,
                title: "title",
            },
            value: "",
        });

        emitter.emit("show-new-document-modal", {
            detail: { parent: store.state.current_folder },
        });
        await wrapper.vm.$nextTick().then(() => {});

        expect(store.dispatch).toHaveBeenCalledWith("properties/loadProjectProperties");
    });
});
