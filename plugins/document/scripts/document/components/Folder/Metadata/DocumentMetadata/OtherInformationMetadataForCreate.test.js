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
import { createStoreMock } from "../../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import localVue from "../../../../helpers/local-vue.js";
import OtherInformationMetadataForCreate from "./OtherInformationMetadataForCreate.vue";
import { TYPE_FILE } from "../../../../constants.js";
import EventBus from "../../../../helpers/event-bus.js";

describe("OtherInformationMetadataForCreate", () => {
    let factory, store;
    beforeEach(() => {
        store = createStoreMock(
            { is_obsolescence_date_metadata_used: true },
            { metadata: { has_loaded_metadata: true } }
        );

        factory = (props = {}) => {
            return shallowMount(OtherInformationMetadataForCreate, {
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
                    metadata: [
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
            is_obsolescence_date_metadata_used: true,
            metadata: {
                has_loaded_metadata: true,
            },
        };

        expect(wrapper.find("[data-test=document-other-information]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-other-information-spinner]").exists()).toBeFalsy();
    });

    it(`Given obsolescence date is disabled for project
        Then obsolescence date component is not rendered`, () => {
        const wrapper = factory({
            currentlyUpdatedItem: {
                metadata: null,
                status: 100,
                type: TYPE_FILE,
                title: "title",
            },
            value: "",
        });

        store.state = {
            is_obsolescence_date_metadata_used: false,
            metadata: {
                has_loaded_metadata: true,
            },
        };

        expect(wrapper.find("[data-test=document-other-information]").exists()).toBeFalsy();
        expect(wrapper.find("[data-test=document-other-information-spinner]").exists()).toBeFalsy();
    });

    it(`Given custom component are loading
        Then it displays spinner`, async () => {
        const wrapper = factory({
            currentlyUpdatedItem: {
                metadata: [],
                status: 100,
                type: TYPE_FILE,
                title: "title",
            },
            value: "",
        });

        store.state = {
            is_obsolescence_date_metadata_used: true,
            metadata: {
                has_loaded_metadata: false,
            },
        };
        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=document-other-information]").exists()).toBeTruthy();
        expect(
            wrapper.find("[data-test=document-other-information-spinner]").exists()
        ).toBeTruthy();
    });

    it("Load project metadata at first load", async () => {
        store.state.metadata = {
            has_loaded_metadata: false,
        };

        const wrapper = factory({
            currentlyUpdatedItem: {
                metadata: [],
                status: 100,
                type: TYPE_FILE,
                title: "title",
            },
            value: "",
        });

        EventBus.$emit("show-new-document-modal", {
            detail: { parent: store.state.current_folder },
        });
        await wrapper.vm.$nextTick().then(() => {});

        expect(store.dispatch).toHaveBeenCalledWith("metadata/loadProjectMetadata", [store]);
    });
});
