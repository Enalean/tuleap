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
import CustomMetadataText from "./CustomMetadataText.vue";
import type { Metadata } from "../../../../store/metadata/module";
import { createDocumentLocalVue } from "../../../../helpers/local-vue-for-test";

describe("CustomMetadataText", () => {
    async function createWrapper(metadata: Metadata): Promise<Wrapper<CustomMetadataText>> {
        return shallowMount(CustomMetadataText, {
            localVue: await createDocumentLocalVue(),
            propsData: { currentlyUpdatedItemMetadata: metadata },
        });
    }

    it(`renders an input with a required value`, async () => {
        const currentlyUpdatedItemMetadata = {
            value: "text value",
            is_required: true,
            name: "field",
            type: "text",
            short_name: "short_name",
        } as Metadata;
        const wrapper = await createWrapper(currentlyUpdatedItemMetadata);
        const text_input = wrapper.get("[data-test=document-text-input]");

        if (!(text_input.element instanceof HTMLTextAreaElement)) {
            throw new Error("Could not find text element in component");
        }
        expect(text_input.element.value).toEqual("text value");
        expect(text_input.element.required).toBe(true);
        expect(
            wrapper.find("[data-test=document-custom-metadata-is-required]").exists()
        ).toBeTruthy();
    });

    it(`renders an input with an empty value`, async () => {
        const currentlyUpdatedItemMetadata = {
            value: "",
            is_required: false,
            name: "field",
            type: "text",
            short_name: "short_name",
        } as Metadata;
        const wrapper = await createWrapper(currentlyUpdatedItemMetadata);
        const text_input = wrapper.get("[data-test=document-text-input]");

        if (!(text_input.element instanceof HTMLTextAreaElement)) {
            throw new Error("Could not find text element in component");
        }
        expect(text_input.element.value).toEqual("");
        expect(text_input.element.required).toBe(false);
        expect(
            wrapper.find("[data-test=document-custom-metadata-is-required]").exists()
        ).toBeFalsy();
    });

    it(`Given custom text metadata
        Then it renders the corresponding component`, async () => {
        const currentlyUpdatedItemMetadata = {
            value: "",
            is_required: false,
            name: "field",
            short_name: "text",
            type: "text",
        } as Metadata;
        const wrapper = await createWrapper(currentlyUpdatedItemMetadata);

        expect(wrapper.find("[data-test=document-custom-metadata-text]").exists()).toBeTruthy();
    });
});
