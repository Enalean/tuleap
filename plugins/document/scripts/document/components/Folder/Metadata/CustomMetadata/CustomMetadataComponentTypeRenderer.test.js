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
import CustomMetadataComponentTypeRenderer from "./CustomMetadataComponentTypeRenderer.vue";

describe("CustomMetadataComponentTypeRenderer", () => {
    let factory;
    beforeEach(() => {
        factory = (props = {}) => {
            return shallowMount(CustomMetadataComponentTypeRenderer, {
                localVue,
                propsData: { ...props },
            });
        };
    });

    it(`Given custom string metadata
        Then it renders the corresponding component`, () => {
        const itemMetadata = {
            short_name: "string",
            type: "string",
        };
        const wrapper = factory({ itemMetadata });

        expect(wrapper.contains("[data-test=document-custom-metadata-text]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-custom-metadata-string]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-custom-metadata-list-single]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-custom-metadata-list-multiple]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-custom-metadata-date]")).toBeFalsy();
    });
    it(`Given custom text metadata
        Then it renders the corresponding component`, () => {
        const itemMetadata = {
            short_name: "text",
            type: "text",
        };
        const wrapper = factory({ itemMetadata });

        expect(wrapper.contains("[data-test=document-custom-metadata-text]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-custom-metadata-string]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-custom-metadata-list-single]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-custom-metadata-list-multiple]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-custom-metadata-date]")).toBeFalsy();
    });
    it(`Given list with only one value metadata
        Then it renders the corresponding component`, () => {
        const itemMetadata = {
            short_name: "list",
            type: "list",
            is_multiple_value_allowed: false,
        };
        const wrapper = factory({ itemMetadata });

        expect(wrapper.contains("[data-test=document-custom-metadata-text]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-custom-metadata-string]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-custom-metadata-list-single]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-custom-metadata-list-multiple]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-custom-metadata-date]")).toBeFalsy();
    });

    it(`Given a list with multiple value metadata
        Then it renders the corresponding component`, () => {
        const itemMetadata = {
            short_name: "list",
            type: "list",
            is_multiple_value_allowed: true,
        };
        const wrapper = factory({ itemMetadata });

        expect(wrapper.contains("[data-test=document-custom-metadata-text]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-custom-metadata-string]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-custom-metadata-list-single]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-custom-metadata-list-multiple]")).toBeTruthy();
        expect(wrapper.contains("[data-test=document-custom-metadata-date]")).toBeFalsy();
    });

    it(`Given a date value metadata
        Then it renders the corresponding component`, () => {
        const itemMetadata = {
            short_name: "date",
            type: "date",
            is_multiple_value_allowed: false,
            value: "",
        };
        const wrapper = factory({ itemMetadata });

        expect(wrapper.contains("[data-test=document-custom-metadata-text]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-custom-metadata-string]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-custom-metadata-list-single]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-custom-metadata-list-multiple]")).toBeFalsy();
        expect(wrapper.contains("[data-test=document-custom-metadata-date]")).toBeTruthy();
    });
});
