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

import { describe, expect, it, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import OutdatedSectionWarning from "@/components/OutdatedSectionWarning.vue";
import { createGettext } from "vue3-gettext";
import type { use_section_editor_actions_type } from "@/composables/useSectionEditor";

describe("OutdatedSectionWarning", () => {
    it("should force save", async () => {
        const refreshSection: use_section_editor_actions_type["refreshSection"] = vi.fn();
        const forceSaveEditor: use_section_editor_actions_type["forceSaveEditor"] = vi.fn();

        const wrapper = shallowMount(OutdatedSectionWarning, {
            global: { plugins: [createGettext({ silent: true })] },
            props: {
                editor_actions: {
                    forceSaveEditor,
                    refreshSection,
                },
            },
        });

        await wrapper.find("[data-test=force-save]").trigger("click");

        expect(forceSaveEditor).toHaveBeenCalled();
        expect(refreshSection).not.toHaveBeenCalled();
    });

    it("should refresh section", async () => {
        const refreshSection: use_section_editor_actions_type["refreshSection"] = vi.fn();
        const forceSaveEditor: use_section_editor_actions_type["forceSaveEditor"] = vi.fn();

        const wrapper = shallowMount(OutdatedSectionWarning, {
            global: { plugins: [createGettext({ silent: true })] },
            props: {
                editor_actions: {
                    forceSaveEditor,
                    refreshSection,
                },
            },
        });

        await wrapper.find("[data-test=refresh]").trigger("click");

        expect(forceSaveEditor).not.toHaveBeenCalled();
        expect(refreshSection).toHaveBeenCalled();
    });
});
