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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper.js";
import localVue from "../../../helpers/local-vue.js";
import OtherInformationMetadata from "./OtherInformationMetadataForUpdate.vue";
import { TYPE_FILE } from "../../../constants.js";

describe("OtherInformationMetadata", () => {
    let other_metadata, state, store;
    beforeEach(() => {
        state = {
            is_obsolescence_date_metadata_used: false
        };

        const store_options = { state };

        store = createStoreMock(store_options);

        other_metadata = (props = {}) => {
            return shallowMount(OtherInformationMetadata, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store }
            });
        };
    });
    it(`Given obsolescence date is enabled for project
        Then we should display the obsolescence date component`, () => {
        const wrapper = other_metadata(
            {
                currentlyUpdatedItem: {
                    metadata: [
                        {
                            short_name: "obsolescence_date",
                            value: null
                        }
                    ],
                    obsolescence_date: null,
                    type: TYPE_FILE,
                    title: "title"
                }
            },
            { parent: 102 }
        );

        store.state.is_obsolescence_date_metadata_used = true;

        expect(wrapper.find("[data-test=document-other-information]").exists()).toBeTruthy();
    });

    it(`Given obsolescence date is disabled for project
        Then obsolescence date component is not rendered`, () => {
        const wrapper = other_metadata(
            {
                currentlyUpdatedItem: {
                    metadata: [],
                    status: 100,
                    type: TYPE_FILE,
                    title: "title"
                }
            },
            { parent: 102 }
        );

        store.state.is_obsolescence_date_metadata_used = false;

        expect(wrapper.find("[data-test=document-other-information]").exists()).toBeFalsy();
    });
});
