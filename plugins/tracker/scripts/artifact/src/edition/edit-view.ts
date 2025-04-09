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
import type { LocaleString } from "@tuleap/gettext";
import {
    getLocaleWithDefault,
    getPOFileFromLocaleWithoutExtension,
    initGettext,
} from "@tuleap/gettext";
import { CommentEditor } from "./comments/CommentEditor";
import { LitHTMLAdapter } from "./comments/LitHTMLAdapter";
import { DOMAdapter } from "./comments/DOMAdapter";
import { TuleapAPIClient } from "./comments/TuleapAPIClient";
import { initLinkField } from "../fields/LinkFieldEditor";
import { initListFields } from "../fields/list-fields";
import { initEditionSwitcher } from "./TrackerArtifactEditionSwitcher";
import "./text-follow-up";

function initTextFields(user_locale: LocaleString): void {
    const creator = RichTextEditorsCreator(
        document,
        UploadImageFormFactory(document, user_locale),
        RichTextEditorFactory.forFlamingParrotWithFormatSelector(document, user_locale),
    );
    creator.createNewCommentEditor();
    creator.createTextFieldEditors();
}

async function initComments(user_locale: LocaleString): Promise<void> {
    const gettext_provider = await initGettext(
        user_locale,
        "tracker_artifact",
        (locale) => import(`../../po/${getPOFileFromLocaleWithoutExtension(locale)}.po`),
    );
    const editor_creator = RichTextEditorsCreator(
        document,
        UploadImageFormFactory(document, user_locale),
        RichTextEditorFactory.forFlamingParrotWithFormatSelector(document, user_locale),
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
    const user_locale = getLocaleWithDefault(document);
    initTextFields(user_locale);
    initListFields();
    await initComments(user_locale);
    // The "submission bar" init must be AFTER init of text fields and comments, otherwise it will not detect the
    // changes in CKEditors
    const edition_switcher = initEditionSwitcher();

    initLinkField(user_locale, edition_switcher);
});
