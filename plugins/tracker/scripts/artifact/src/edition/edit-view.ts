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

import "./styles/edition.scss";
import { UploadImageFormFactory } from "@tuleap/plugin-tracker-artifact-ckeditor-image-upload";
import { RichTextEditorFactory } from "@tuleap/plugin-tracker-rich-text-editor";
import { RichTextEditorsCreator } from "@tuleap/plugin-tracker-rte-creator";
import { initGettext, getLocaleWithDefault, getPOFileFromLocale } from "@tuleap/gettext";
import { CommentEditor } from "./comments/CommentEditor";
import { LitHTMLAdapter } from "./comments/LitHTMLAdapter";
import { DOMAdapter } from "./comments/DOMAdapter";
import { TuleapAPIClient } from "./comments/TuleapAPIClient";
import { LinkFieldEditor } from "./link-field/LinkFieldEditor";
import { initListFields } from "../fields/list-fields";

// Do not use DOMContentLoaded event because it arrives after jQuery document ready event
// and it will cause the "submission bar" to stop working.
document.addEventListener("readystatechange", () => {
    if (document.readyState !== "interactive") {
        return;
    }
    const locale = getLocaleWithDefault(document);
    const creator = RichTextEditorsCreator(
        document,
        UploadImageFormFactory(document, locale),
        RichTextEditorFactory.forFlamingParrotWithFormatSelector(document, locale),
    );
    creator.createNewCommentEditor();
    creator.createTextFieldEditors();
});

function initLinkField(): void {
    const mount_point = document.querySelector("[data-link-field-id]");
    if (mount_point instanceof HTMLElement) {
        LinkFieldEditor(document).init(mount_point);
    }
}

async function initComments(): Promise<void> {
    const locale = getLocaleWithDefault(document);
    const gettext_provider = await initGettext(
        locale,
        "tracker_artifact",
        (locale) => import(`../../po/${getPOFileFromLocale(locale)}`),
    );
    const editor_creator = RichTextEditorsCreator(
        document,
        UploadImageFormFactory(document, locale),
        RichTextEditorFactory.forFlamingParrotWithFormatSelector(document, locale),
    );

    const dom_adapter = DOMAdapter(document);
    const editor = CommentEditor(
        LitHTMLAdapter(),
        dom_adapter,
        editor_creator,
        gettext_provider,
        TuleapAPIClient(document, window),
    );
    dom_adapter.findEditCommentButtons().forEach(editor.init);
}

document.addEventListener("DOMContentLoaded", async () => {
    initListFields();
    initLinkField();
    await initComments();
});

// Edition-switcher must execute after CKEditors are initialized, otherwise the
// "submission bar" won't work properly.
import "./TrackerArtifactEditionSwitcher.js";
import "./text-follow-up";
