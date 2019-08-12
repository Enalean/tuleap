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

import localVue from "../../../../helpers/local-vue.js";
import { shallowMount } from "@vue/test-utils";
import CustomMeatdata from "./CustomMetadata.vue";

describe("CustomMeatdata", () => {
    let factory;
    beforeEach(() => {
        factory = (props = {}) => {
            return shallowMount(CustomMeatdata, {
                localVue,
                propsData: { ...props }
            });
        };
    });

    it(`Given custom string metadata
        Then it renders the corresponding component`, () => {
        const currentlyUpdatedItem = {
            id: 42,
            metadata: [
                {
                    short_name: "string",
                    type: "string"
                }
            ]
        };
        const wrapper = factory({ currentlyUpdatedItem });

        expect(wrapper.contains("[data-test=document-custom-metadata-text]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-custom-metadata-string]")).toBeTruthy();
    });
    it(`Given custom text metadata
        Then it renders the corresponding component`, () => {
        const currentlyUpdatedItem = {
            id: 42,
            metadata: [
                {
                    short_name: "text",
                    type: "text"
                }
            ]
        };
        const wrapper = factory({ currentlyUpdatedItem });

        expect(wrapper.contains("[data-test=document-custom-metadata-text]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-custom-metadata-string]")).toBeFalsy();
    });
});
