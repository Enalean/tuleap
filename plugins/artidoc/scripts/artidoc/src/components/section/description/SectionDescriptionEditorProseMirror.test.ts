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
import { beforeAll, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { Ref, ComponentPublicInstance } from "vue";
import { ref } from "vue";
import SectionDescriptionEditorProseMirror from "./SectionDescriptionEditorProseMirror.vue";
import VueDOMPurifyHTML from "vue-dompurify-html";
import * as upload_file from "@/composables/useUploadFile";
import NotificationBar from "@/components/section/description/NotificationBar.vue";
import type { OnGoingUploadFile } from "@tuleap/prose-mirror-editor";

describe("SectionDescriptionEditorProseMirror", () => {
    let wrapper: VueWrapper<ComponentPublicInstance>;

    beforeAll(() => {
        const upload_files: Ref<Map<number, OnGoingUploadFile>> = ref(new Map());
        vi.spyOn(upload_file, "useUploadFile").mockReturnValue({
            progress: ref(0),
            is_in_progress: ref(false),
            file_upload_options: {
                upload_url: "upload_url",
                max_size_upload: 1234,
                upload_files: upload_files.value,
                onErrorCallback: vi.fn(),
                onProgressCallback: vi.fn(),
                onSuccessCallback: vi.fn(),
            },
            error_message: ref(null),
            resetProgressCallback: vi.fn(),
        });
        wrapper = shallowMount(SectionDescriptionEditorProseMirror, {
            props: {
                editable_description: "<h1>description</h1>",
                input_current_description: vi.fn(),
                is_edit_mode: false,
                upload_url: "",
                is_image_upload_allowed: true,
                add_attachment_to_waiting_list: vi.fn(),
            },
            global: {
                plugins: [VueDOMPurifyHTML],
            },
        });
    });

    it("should display the editor", () => {
        const editorProseMirror = wrapper.find(".ProseMirror");
        expect(editorProseMirror.exists()).toBe(true);
    });
    it("should focus the editor", () => {
        expect(wrapper.find(".ProseMirror-focused").exists()).toBe(true);
    });
    it("should have a notification bar", () => {
        expect(wrapper.findComponent(NotificationBar).exists()).toBe(true);
    });
});
