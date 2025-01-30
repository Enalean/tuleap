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

import type { SectionEditor } from "@/composables/useSectionEditor";
import { ref } from "vue";
import { noop } from "@/helpers/noop";

export const SectionEditorStub = {
    build: (): SectionEditor => ({
        editor_actions: {
            saveEditor: noop,
            forceSaveEditor: noop,
            cancelEditor: noop,
            refreshSection: noop,
            deleteSection: noop,
        },
        editor_section_content: {
            inputSectionContent: noop,
            editable_title: ref(""),
            editable_description: ref(""),
            getReadonlyDescription: () => "",
            resetContent: noop,
            is_there_any_change: ref(false),
        },
    }),
};
