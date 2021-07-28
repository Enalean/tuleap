/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import { initMentions } from "@tuleap/mention";

export function initMentionsOnEditorDataReady(ckeditor: CKEDITOR.editor): void {
    // This MUST be called after "dataReady" event because calling setData() on CKEditor will kill the event listeners of @tuleap/mention
    if (!ckeditor.document) {
        return;
    }
    const ckeditor_document = ckeditor.document.getBody().$;
    // Set the ckeditor's iframe document <body> to contentEditable=true otherwise @tuleap/mention will filter it out
    ckeditor_document.contentEditable = "true";
    initMentions(ckeditor_document);
}
