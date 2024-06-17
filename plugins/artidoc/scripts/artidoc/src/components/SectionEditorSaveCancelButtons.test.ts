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

import { describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { ComponentPublicInstance } from "vue";
import { createGettext } from "vue3-gettext";
import { SectionEditorStub } from "@/helpers/stubs/SectionEditorStub";
import type { SectionEditor } from "@/composables/useSectionEditor";
import SectionEditorSaveCancelButtons from "@/components/SectionEditorSaveCancelButtons.vue";
import { mockStrictInject } from "@/helpers/mock-strict-inject";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";
import { ConfigurationStoreStub } from "@/helpers/stubs/ConfigurationStoreStub";

describe("SectionEditorSaveCancelButtons", () => {
    function getWrapper(editor: SectionEditor): VueWrapper<ComponentPublicInstance> {
        return shallowMount(SectionEditorSaveCancelButtons, {
            propsData: {
                editor,
            },
            global: { plugins: [createGettext({ silent: true })] },
        });
    }

    describe("when the edit mode is off", () => {
        it("should hide buttons", () => {
            mockStrictInject([
                [CONFIGURATION_STORE, ConfigurationStoreStub.withoutAllowedTrackers()],
            ]);
            expect(
                getWrapper(SectionEditorStub.withEditableSection()).find("button").exists(),
            ).toBe(false);
        });
    });

    describe("when the edit mode is on", () => {
        it("should display buttons", () => {
            mockStrictInject([
                [CONFIGURATION_STORE, ConfigurationStoreStub.withoutAllowedTrackers()],
            ]);
            expect(getWrapper(SectionEditorStub.inEditMode()).find("button").exists()).toBe(true);
        });
    });
});
