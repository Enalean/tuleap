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

import localVue from "../../../helpers/local-vue.js";
import { shallowMount } from "@vue/test-utils";
import TitleMetadata from "./TitleMetadata.vue";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import { TYPE_FILE, TYPE_FOLDER } from "../../../constants.js";

describe("TitleMetadata", () => {
    let title_metadata_factory,
        store,
        existing_folder_name,
        existing_document_name,
        updated_document_name;
    beforeEach(() => {
        existing_folder_name = "Existing folder";
        existing_document_name = "Existing file";
        updated_document_name = "my file";
        const state = {
            folder_content: [
                {
                    id: 2,
                    title: existing_folder_name,
                    type: TYPE_FOLDER,
                    parent_id: 3,
                },
                {
                    id: 20,
                    title: existing_document_name,
                    type: TYPE_FILE,
                    parent_id: 3,
                },
                {
                    id: 10,
                    title: updated_document_name,
                    type: TYPE_FILE,
                    parent_id: 3,
                },
            ],
        };

        const store_options = {
            state,
        };

        store = createStoreMock(store_options);

        title_metadata_factory = (props = {}) => {
            return shallowMount(TitleMetadata, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });

    it(`Title can not be updated for root folder`, async () => {
        const value = "A new folder title";
        const isInUpdateContext = true;
        const parent = {};
        const currentlyUpdatedItem = {
            type: TYPE_FOLDER,
            parent_id: 0,
        };

        const wrapper = title_metadata_factory({
            value,
            isInUpdateContext,
            parent,
            currentlyUpdatedItem,
        });
        wrapper.setProps({ value: value });

        await wrapper.vm.$nextTick().then(() => {});
        const input = wrapper.get("[data-test=document-new-item-title]");

        expect(input.element.disabled).toBe(true);
        expect(wrapper.get("[data-test=document-new-item-title-form-element]").classes()).toContain(
            "tlp-form-element-disabled"
        );
    });

    it(`Title can be updated for other items`, async () => {
        const value = "A new folder title";
        const isInUpdateContext = true;
        const parent = {};
        const currentlyUpdatedItem = {
            type: TYPE_FOLDER,
            parent_id: 3,
        };

        const wrapper = title_metadata_factory({
            value,
            isInUpdateContext,
            parent,
            currentlyUpdatedItem,
        });
        wrapper.setProps({ value: value });

        await wrapper.vm.$nextTick().then(() => {});
        const input = wrapper.get("[data-test=document-new-item-title]");

        expect(input.element.disabled).toBe(false);
    });

    describe("Folder creation", () => {
        it(`Title is valid when no other folder has the same name`, async () => {
            const value = "A new folder title";
            const isInUpdateContext = false;
            const parent = {
                id: 3,
            };
            const currentlyUpdatedItem = {
                type: TYPE_FOLDER,
            };

            const wrapper = title_metadata_factory({
                value,
                isInUpdateContext,
                parent,
                currentlyUpdatedItem,
            });
            wrapper.setProps({ value: value });

            await wrapper.vm.$nextTick().then(() => {});
            expect(wrapper.contains("[data-test=title-error-message]")).toBeFalsy();
        });

        it(`Error is rendered if folder title is already used`, async () => {
            const value = "";
            const isInUpdateContext = false;
            const parent = {
                id: 3,
            };
            const currentlyUpdatedItem = {
                type: TYPE_FOLDER,
            };

            const wrapper = title_metadata_factory({
                value,
                isInUpdateContext,
                parent,
                currentlyUpdatedItem,
            });
            wrapper.setProps({ value: existing_folder_name });

            await wrapper.vm.$nextTick().then(() => {});

            expect(wrapper.contains("[data-test=title-error-message]")).toBeTruthy();
        });
    });

    describe("Document creation", () => {
        it(`Title is valid when not other folder has the same name`, async () => {
            const value = "A new document title";
            const isInUpdateContext = false;
            const parent = {
                id: 3,
            };
            const currentlyUpdatedItem = {
                type: TYPE_FILE,
            };

            const wrapper = title_metadata_factory({
                value,
                isInUpdateContext,
                parent,
                currentlyUpdatedItem,
            });
            wrapper.setProps({ value: value });

            await wrapper.vm.$nextTick().then(() => {});
            expect(wrapper.contains("[data-test=title-error-message]")).toBeFalsy();
        });

        it(`Error is rendered if folder title is already used`, async () => {
            const value = "";
            const isInUpdateContext = false;
            const parent = {
                id: 3,
            };
            const currentlyUpdatedItem = {
                type: TYPE_FILE,
            };

            const wrapper = title_metadata_factory({
                value,
                isInUpdateContext,
                parent,
                currentlyUpdatedItem,
            });
            wrapper.setProps({ value: existing_document_name });

            await wrapper.vm.$nextTick().then(() => {});
            expect(wrapper.contains("[data-test=title-error-message]")).toBeTruthy();
        });
    });

    describe("Document update", () => {
        it(`Title is valid when no other document has the same name`, async () => {
            const value = "old title";
            const isInUpdateContext = true;
            const parent = {
                id: 3,
            };
            const currentlyUpdatedItem = {
                type: TYPE_FILE,
            };

            const wrapper = title_metadata_factory({
                value,
                isInUpdateContext,
                parent,
                currentlyUpdatedItem,
            });
            wrapper.setProps({ value: "updated title" });

            await wrapper.vm.$nextTick().then(() => {});
            expect(wrapper.contains("[data-test=title-error-message]")).toBeFalsy();
        });

        it(`Error is rendered if folder title is already used`, async () => {
            const value = updated_document_name;
            const isInUpdateContext = true;
            const parent = {
                id: 3,
            };
            const currentlyUpdatedItem = {
                type: TYPE_FILE,
            };

            const wrapper = title_metadata_factory({
                value,
                isInUpdateContext,
                parent,
                currentlyUpdatedItem,
            });
            wrapper.setProps({ value: existing_document_name });

            await wrapper.vm.$nextTick().then(() => {});
            expect(wrapper.contains("[data-test=title-error-message]")).toBeTruthy();
        });
    });
});
