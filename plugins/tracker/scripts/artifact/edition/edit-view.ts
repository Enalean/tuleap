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

import { UploadImageFormFactory } from "@tuleap/plugin-tracker-artifact-ckeditor-image-upload";
import { RichTextEditorFactory } from "@tuleap/plugin-tracker-rich-text-editor";
import { RichTextEditorsCreator } from "@tuleap/plugin-tracker-rte-creator";

// Do not use DOMContentLoaded event because it arrives after jQuery document ready event
// and it will cause the "submission bar" to stop working.
document.addEventListener("readystatechange", () => {
    if (document.readyState !== "interactive") {
        return;
    }
    const locale = document.body.dataset.userLocale;
    if (locale === undefined) {
        return;
    }
    const creator = new RichTextEditorsCreator(
        document,
        new UploadImageFormFactory(document, locale),
        RichTextEditorFactory.forFlamingParrotWithFormatSelector(document, locale),
    );
    creator.createNewFollowupEditor();
    creator.createTextFieldEditors();
});

// Edition-switcher must execute after CKEditors are initialized, otherwise the
// "submission bar" won't work properly.
import "./TrackerArtifactEditionSwitcher.js";
import "./text-follow-up";
import "../tracker-email-copy-paste-fp";
import "./edit-follow-up-comment.js";
