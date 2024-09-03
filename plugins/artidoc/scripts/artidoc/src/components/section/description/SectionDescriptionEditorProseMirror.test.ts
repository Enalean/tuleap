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
import type { ComponentPublicInstance } from "vue";
import SectionDescriptionEditorProseMirror from "./SectionDescriptionEditorProseMirror.vue";
import VueDOMPurifyHTML from "vue-dompurify-html";
import NotificationBar from "@/components/section/description/NotificationBar.vue";
import { UploadFileStub } from "@/helpers/stubs/UploadFileStub";
import * as editor from "@tuleap/prose-mirror-editor";
import type { UseEditorType } from "@tuleap/prose-mirror-editor";

describe("SectionDescriptionEditorProseMirror", () => {
    let wrapper: VueWrapper<ComponentPublicInstance>;

    beforeAll(() => {
        vi.spyOn(editor, "useEditor").mockResolvedValue({} as UseEditorType);
        wrapper = shallowMount(SectionDescriptionEditorProseMirror, {
            props: {
                editable_description: "<h1>description</h1>",
                input_current_description: vi.fn(),
                is_edit_mode: false,
                upload_file: UploadFileStub.uploadNotInProgress(),
            },
            global: {
                plugins: [VueDOMPurifyHTML],
            },
        });
    });
    it("should have a notification bar", () => {
        expect(wrapper.findComponent(NotificationBar).exists()).toBe(true);
    });
});
