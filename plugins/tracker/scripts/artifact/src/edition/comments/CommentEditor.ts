/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import type { GettextProvider } from "@tuleap/gettext";
import type { RichTextEditorsCreator } from "@tuleap/plugin-tracker-rte-creator";
import type { TextEditorInterface } from "@tuleap/plugin-tracker-rich-text-editor";
import { Option } from "@tuleap/option";
import { sanitize } from "dompurify";
import type { LitHTMLAdapter } from "./LitHTMLAdapter";
import { EditZone } from "./EditZone";
import type { DOMAdapter, InitDataFromBackend } from "./DOMAdapter";
import {
    FORMAT_HIDDEN_INPUT_ID_PREFIX,
    FORMAT_SELECTBOX_ID_PREFIX,
    TEXTAREA_ID_PREFIX,
} from "./DOMAdapter";
import type { TuleapAPIClient } from "./TuleapAPIClient";

export type CommentEditor = {
    init(edit_button: HTMLElement): void;
};

export const CommentEditor = (
    renderer: LitHTMLAdapter,
    dom_adapter: DOMAdapter,
    editor_creator: RichTextEditorsCreator,
    gettext_provider: GettextProvider,
    api_client: TuleapAPIClient,
): CommentEditor => {
    function openCommentEditZone(
        init_data_from_backend: InitDataFromBackend,
        edit_button: HTMLElement,
    ): void {
        const changeset_id = init_data_from_backend.changeset_id;
        const read_only_comment = init_data_from_backend.read_only_comment;
        const comment_format = dom_adapter.readCommentFormatOrDefault(
            FORMAT_HIDDEN_INPUT_ID_PREFIX + changeset_id,
            init_data_from_backend.user_preferred_format,
        );
        const comment_body = dom_adapter.readCommentBodyOrDefault(
            init_data_from_backend.follow_up_content,
            comment_format,
        );

        const textarea = dom_adapter.createTextArea({
            id: TEXTAREA_ID_PREFIX + changeset_id,
            project_id: init_data_from_backend.project_id,
            comment_body,
        });
        let maybe_editor: Option<TextEditorInterface> = Option.nothing();

        const edit_zone = EditZone(
            {
                textarea,
                are_notifications_enabled: init_data_from_backend.are_notifications_enabled,
                onSubmit() {
                    maybe_editor.apply((editor) => {
                        const format_at_submit = dom_adapter.readCommentFormatOrDefault(
                            FORMAT_SELECTBOX_ID_PREFIX + changeset_id,
                            init_data_from_backend.user_preferred_format,
                        );
                        api_client
                            .postComment(changeset_id, editor.getContent(), format_at_submit)
                            .then((response_html) => {
                                read_only_comment.innerHTML = sanitize(response_html);
                                dom_adapter.show(edit_button);
                                dom_adapter.show(read_only_comment);
                                dom_adapter.highlight(read_only_comment);
                                editor.destroy();

                                renderer.render({
                                    is_in_edition: false,
                                    edit_zone,
                                    mount_point: init_data_from_backend.follow_up_content,
                                    render_before: read_only_comment,
                                });
                            });
                    });
                },
                onCancel() {
                    dom_adapter.show(edit_button);
                    dom_adapter.show(read_only_comment);
                    maybe_editor.apply((editor) => editor.destroy());
                    renderer.render({
                        is_in_edition: false,
                        edit_zone,
                        mount_point: init_data_from_backend.follow_up_content,
                        render_before: read_only_comment,
                    });
                },
            },
            gettext_provider,
        );

        dom_adapter.hide(edit_button);
        dom_adapter.hide(read_only_comment);
        renderer.render({
            is_in_edition: true,
            edit_zone,
            mount_point: init_data_from_backend.follow_up_content,
            render_before: read_only_comment,
        });
        const editor = editor_creator.createEditCommentEditor(
            textarea,
            changeset_id,
            comment_format,
        );
        maybe_editor = Option.fromValue(editor);
    }

    return {
        init(edit_button): void {
            dom_adapter.readInitDataFromBackend(edit_button).apply((init_data_from_backend) => {
                edit_button.addEventListener("click", (event) => {
                    // Prevent expand/collapse on parent
                    event.stopPropagation();
                    openCommentEditZone(init_data_from_backend, edit_button);
                });
            });
        },
    };
};
