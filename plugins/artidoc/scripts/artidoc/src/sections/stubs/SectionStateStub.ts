/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import { ref, computed } from "vue";
import type { SectionState } from "@/sections/states/SectionStateBuilder";

const initial_state = {
    is_image_upload_allowed: computed(() => true),
    is_section_editable: computed(() => true),
    is_save_allowed: computed(() => true),
    is_section_in_edit_mode: ref(false),
    is_just_refreshed: ref(false),
    is_being_saved: ref(false),
    is_just_saved: ref(false),
    is_in_error: ref(false),
    is_outdated: ref(false),
    is_not_found: ref(false),
    error_message: ref(""),
    edited_title: ref(""),
    edited_description: ref(""),
    is_editor_reset_needed: ref(false),
};

export const SectionStateStub = {
    withDefaults: (): SectionState => initial_state,
    inEditMode: (): SectionState => ({
        ...initial_state,
        is_section_in_edit_mode: ref(true),
    }),
    notEditable: (): SectionState => ({
        ...initial_state,
        is_section_editable: computed(() => false),
    }),
    withDisallowedSave: (): SectionState => ({
        ...initial_state,
        is_section_in_edit_mode: ref(true),
        is_save_allowed: computed(() => false),
    }),
    withEditedContent: (
        new_title = "new title",
        new_description = "new description",
    ): SectionState => ({
        ...initial_state,
        edited_title: ref(new_title),
        edited_description: ref(new_description),
        is_section_in_edit_mode: ref(true),
        is_editor_reset_needed: ref(true),
    }),
};
