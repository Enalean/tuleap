/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { describe, beforeEach, expect, it, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import SectionDescription from "./SectionDescription.vue";
import SectionDescriptionSkeleton from "./SectionDescriptionSkeleton.vue";
import { InjectedSectionsStoreStub } from "@/helpers/stubs/InjectSectionsStoreStub";
import SectionDescriptionReadOnly from "./SectionDescriptionReadOnly.vue";
import { SECTIONS_STORE } from "@/stores/sections-store-injection-key";
import { UploadFileStub } from "@/helpers/stubs/UploadFileStub";
import { CAN_USER_EDIT_DOCUMENT } from "@/can-user-edit-document-injection-key";

describe("SectionDescription", () => {
    let are_sections_loading: boolean, can_user_edit_document: boolean, is_print_mode: boolean;

    beforeEach(() => {
        are_sections_loading = false;
        can_user_edit_document = true;
        is_print_mode = false;
    });

    const getWrapper = (): VueWrapper => {
        const sections_store = are_sections_loading
            ? InjectedSectionsStoreStub.withLoadingSections([])
            : InjectedSectionsStoreStub.withLoadedSections([]);

        return shallowMount(SectionDescription, {
            global: {
                provide: {
                    [SECTIONS_STORE.valueOf()]: sections_store,
                    [CAN_USER_EDIT_DOCUMENT.valueOf()]: can_user_edit_document,
                },
                stubs: {
                    async_editor: {
                        template: "<span/>",
                    },
                },
            },
            props: {
                editable_description: "Lorem ipsum",
                readonly_description: "Lorem ipsum",
                is_edit_mode: false,
                is_print_mode,
                upload_url: "/file/upload",
                add_attachment_to_waiting_list: vi.fn(),
                input_current_description: vi.fn(),
                is_image_upload_allowed: true,
                upload_file: UploadFileStub.uploadNotInProgress(),
                project_id: 101,
                references: [],
            },
        });
    };

    it("When sections are loading, Then it should display the skeleton", () => {
        are_sections_loading = true;

        const wrapper = getWrapper();

        expect(wrapper.findComponent(SectionDescriptionReadOnly).exists()).toBe(false);
        expect(wrapper.findComponent(SectionDescriptionSkeleton).exists()).toBe(true);
        expect(wrapper.find("[data-test=editor]").exists()).toBe(false);
    });

    it("When the section is in print mode, then it should display a readonly description", () => {
        is_print_mode = true;

        const wrapper = getWrapper();

        expect(wrapper.findComponent(SectionDescriptionReadOnly).exists()).toBe(true);
        expect(wrapper.findComponent(SectionDescriptionSkeleton).exists()).toBe(false);
        expect(wrapper.find("[data-test=editor]").exists()).toBe(false);
    });

    it("When the current user cannot edit the document, then it should display a readonly description", () => {
        can_user_edit_document = false;

        const wrapper = getWrapper();

        expect(wrapper.findComponent(SectionDescriptionReadOnly).exists()).toBe(true);
        expect(wrapper.findComponent(SectionDescriptionSkeleton).exists()).toBe(false);
        expect(wrapper.find("[data-test=editor]").exists()).toBe(false);
    });

    it("When the current user can edit the document, then the editor should be displayed", () => {
        const wrapper = getWrapper();

        expect(wrapper.findComponent(SectionDescriptionReadOnly).exists()).toBe(false);
        expect(wrapper.findComponent(SectionDescriptionSkeleton).exists()).toBe(false);
        expect(wrapper.find("[data-test=editor]").exists()).toBe(true);
    });
});
