/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
 *
 * Originally written by Nicolas Terray, 2008
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

/* global
    $$:readonly
    $:readonly
    Effect:readonly
    Ajax:readonly
*/

import CKEDITOR from "ckeditor4";
import codendi from "codendi";
import {
    getFormatOrDefault,
    getLocaleFromBody,
    getProjectId,
    getTextAreaValue,
} from "./edit-follow-up-comment-helpers";
import { RichTextEditorFactory } from "@tuleap/plugin-tracker-rich-text-editor";
import { RichTextEditorsCreator } from "@tuleap/plugin-tracker-rte-creator";
import { UploadImageFormFactory } from "@tuleap/plugin-tracker-artifact-ckeditor-image-upload";

document.observe("dom:loaded", function () {
    $$(".tracker_artifact_followup_comment_controls_edit button").each(function (edit) {
        var id = edit.up(".tracker_artifact_followup").id;

        if (id && id.match(/_\d+$/)) {
            id = id.match(/_(\d+)$/)[1];

            const locale = getLocaleFromBody(document);
            const editor_factory = RichTextEditorFactory.forFlamingParrotWithFormatSelector(
                document,
                locale,
            );
            edit.observe("click", function (evt) {
                Event.stop(evt);
                const followup_body = $("followup_" + id);
                var comment_panel = followup_body.down(".tracker_artifact_followup_comment");
                if (comment_panel.visible()) {
                    var textarea = new Element("textarea", {
                        id: "tracker_followup_comment_edit_" + id,
                        class: "user-mention",
                    });
                    const format = getFormatOrDefault(document, id);
                    textarea.value = getTextAreaValue(comment_panel, format);
                    textarea.dataset.projectId = getProjectId(followup_body);

                    var rteSpan = new Element("span", { style: "text-align: left;" }).update(
                        textarea,
                    );
                    var edit_panel = new Element("div", { style: "text-align: right;" }).update(
                        rteSpan,
                    );
                    comment_panel.insert({ before: edit_panel });
                    const creator = new RichTextEditorsCreator(
                        document,
                        new UploadImageFormFactory(document, locale),
                        editor_factory,
                    );
                    creator.createEditFollowupEditor(id, format);

                    var nb_rows_displayed = 5;
                    var nb_rows_content = textarea.value.split(/\n/).length;
                    if (nb_rows_content > nb_rows_displayed) {
                        nb_rows_displayed = nb_rows_content;
                    }
                    textarea.rows = nb_rows_displayed;

                    comment_panel.hide();
                    textarea.focus();

                    var button = new Element("button", { class: "btn btn-primary" })
                        .update(codendi.locales.tracker_artifact.edit_followup_ok)
                        .observe("click", function (evt) {
                            var content;
                            if (
                                CKEDITOR.instances &&
                                CKEDITOR.instances["tracker_followup_comment_edit_" + id]
                            ) {
                                content =
                                    CKEDITOR.instances[
                                        "tracker_followup_comment_edit_" + id
                                    ].getData();
                            } else {
                                content = $("tracker_followup_comment_edit_" + id).getValue();
                            }
                            var format = $("rte_format_selectbox" + id).value;
                            //eslint-disable-next-line @typescript-eslint/no-unused-vars
                            var req = new Ajax.Request(location.href, {
                                parameters: {
                                    func: "update-comment",
                                    changeset_id: id,
                                    content: content,
                                    comment_format: format,
                                },
                                onSuccess: function (transport) {
                                    if (CKEDITOR.instances["tracker_followup_comment_edit_" + id]) {
                                        CKEDITOR.instances[
                                            "tracker_followup_comment_edit_" + id
                                        ].destroy(true);
                                    }
                                    edit_panel.remove();
                                    comment_panel.update(transport.responseText).show();
                                    var e = new Effect.Highlight(comment_panel); //eslint-disable-line @typescript-eslint/no-unused-vars
                                },
                            });
                            edit.show();
                            Event.stop(evt);
                            return false;
                        });

                    edit.hide();
                    var cancel = new Element("a", {
                        href: "#cancel",
                        class: "btn",
                    })
                        .update(codendi.locales.tracker_artifact.edit_followup_cancel)
                        .observe("click", function (evt) {
                            if (CKEDITOR.instances["tracker_followup_comment_edit_" + id]) {
                                CKEDITOR.instances["tracker_followup_comment_edit_" + id].destroy(
                                    true,
                                );
                            }
                            edit_panel.remove();
                            comment_panel.show();
                            Event.stop(evt);
                            edit.show();
                        });
                    edit_panel
                        .insert(button)
                        .insert(new Element("span").update("&nbsp;"))
                        .insert(cancel);
                }
                Event.stop(evt);
            });
        }
    });
});
