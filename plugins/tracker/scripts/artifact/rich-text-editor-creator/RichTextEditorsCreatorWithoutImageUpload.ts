/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

import type { RichTextEditorFactory } from "@tuleap/plugin-tracker-rich-text-editor";

export class RichTextEditorsCreatorWithoutImageUpload {
    constructor(
        private readonly doc: Document,
        private readonly editor_factory: RichTextEditorFactory,
    ) {}

    public createTextFieldEditorForMassChange(): void {
        const follow_up_textarea = this.doc.getElementById("artifact_masschange_followup_comment");
        if (!(follow_up_textarea instanceof HTMLTextAreaElement)) {
            throw new Error("Follow-up textarea of the mass change not found");
        }

        const options = {
            format_selectbox_id: "mass_change",
        };

        this.editor_factory.createRichTextEditor(follow_up_textarea, options);
    }
}
