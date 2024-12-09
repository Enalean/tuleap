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

import type { TextEditorInterface } from "@tuleap/plugin-tracker-rich-text-editor";
import type { RichTextEditorsCreator } from "@tuleap/plugin-tracker-rte-creator";
import { TEXT_FORMAT_COMMONMARK, TEXT_FORMAT_HTML } from "@tuleap/plugin-tracker-constants";
import { selectOrThrow } from "@tuleap/dom";
import type { GettextProvider } from "@tuleap/gettext";
import { CommentEditor } from "./CommentEditor";
import { LitHTMLAdapter } from "./LitHTMLAdapter";
import { DOMAdapter, HIDE_CLASS, HIGHLIGHT_CLASS } from "./DOMAdapter";
import { TuleapAPIClient } from "./TuleapAPIClient";

function noop(): void {
    //Do nothing
}

jest.useFakeTimers();

describe(`CommentEditor`, () => {
    describe(`init()`, () => {
        let doc: Document,
            edit_button: HTMLButtonElement,
            read_only_comment: HTMLElement,
            editor_content: string,
            fetch_text_response: string,
            api_client: TuleapAPIClient;
        const changeset_id = "145";
        const project_id = "222";
        const comment_body = "Initial comment";
        const rendered_comment = "Rendered comment";

        beforeEach(() => {
            doc = document.implementation.createHTMLDocument();
            doc.body.insertAdjacentHTML(
                "beforeend",
                `<div data-follow-up>
                    <div><button data-edit-comment-button></button></div>
                    <div
                        data-follow-up-content
                        data-changeset-id="${changeset_id}"
                        data-project-id="${project_id}"
                        data-user-preferred-format="${TEXT_FORMAT_COMMONMARK}"
                    >
                        <div data-read-only-comment>
                            <input
                                type="hidden"
                                id="tracker_artifact_followup_comment_body_format_${changeset_id}"
                                value="${TEXT_FORMAT_COMMONMARK}"
                            />
                            <div data-comment-body data-commonmark-source="${comment_body}">
                                ${rendered_comment}
                            </div>
                        </div>
                    </div>
                </div>`,
            );
            edit_button = selectOrThrow(doc, "[data-edit-comment-button]", HTMLButtonElement);
            read_only_comment = selectOrThrow(doc, "[data-read-only-comment]");
            editor_content = comment_body;
            fetch_text_response = comment_body;
        });

        function init(): void {
            const fake_editor = {
                getContent: () => editor_content,
                destroy: noop,
            } as TextEditorInterface;
            const editor_creator = {
                createEditCommentEditor: (textarea, changeset_id, comment_format) => {
                    if (textarea || changeset_id || comment_format) {
                        //Do nothing
                    }
                    return fake_editor;
                },
            } as RichTextEditorsCreator;
            const gettext_provider = {
                gettext: (english: string) => english,
            } as GettextProvider;

            const fetchStub = (): Promise<Response> =>
                Promise.resolve({
                    text: () => Promise.resolve(fetch_text_response),
                } as Response);
            const fetcher = {
                fetch: fetchStub as typeof global.fetch,
            };
            api_client = TuleapAPIClient(doc, fetcher);

            CommentEditor(
                LitHTMLAdapter(),
                DOMAdapter(doc),
                editor_creator,
                gettext_provider,
                api_client,
            ).init(edit_button);
        }

        it(`sets up a click listener on the given edit button,
            when clicked it will open the edit zone with a textarea`, () => {
            init();
            const event = new Event("click", { cancelable: true });
            const stopPropagation = jest.spyOn(event, "stopPropagation");
            edit_button.dispatchEvent(event);

            expect(stopPropagation).toHaveBeenCalled();
            const textarea = selectOrThrow(
                doc,
                "[data-test=edit-comment-textarea]",
                HTMLTextAreaElement,
            );
            expect(textarea.id).toBe("tracker_followup_comment_edit_" + changeset_id);
            expect(textarea.getAttribute("data-project-id")).toBe(project_id);
            expect(textarea.textContent).toBe(comment_body);
            expect(edit_button.classList.contains(HIDE_CLASS)).toBe(true);
            expect(read_only_comment.classList.contains(HIDE_CLASS)).toBe(true);
        });

        it(`when the edit zone is submitted, it posts the comment
            and will remove the edit zone and show and highlight the read-only comment`, async () => {
            const modified_comment_body = "<p>Modified comment</p>";
            editor_content = modified_comment_body;
            fetch_text_response = modified_comment_body;

            init();
            const postComment = jest.spyOn(api_client, "postComment");
            edit_button.click();

            // As we don't create a real rich text editor, simulate the selectbox for the format
            read_only_comment.insertAdjacentHTML(
                "beforebegin",
                `<select id="rte_format_selectbox${changeset_id}"><option value=${TEXT_FORMAT_HTML} selected></option></select>`,
            );

            selectOrThrow(doc, "[data-test=edit-comment-submit]", HTMLButtonElement).click();
            await jest.runOnlyPendingTimersAsync();

            expect(postComment).toHaveBeenCalledWith(
                changeset_id,
                editor_content,
                TEXT_FORMAT_HTML,
            );
            expect(read_only_comment.innerHTML).toContain(modified_comment_body);
            expect(edit_button.classList.contains(HIDE_CLASS)).toBe(false);
            expect(read_only_comment.classList.contains(HIDE_CLASS)).toBe(false);
            expect(read_only_comment.classList.contains(HIGHLIGHT_CLASS)).toBe(true);
        });

        it(`when the edit zone is cancelled, it will remove the edit zone and show the read-only comment`, () => {
            init();
            edit_button.click();
            selectOrThrow(doc, "[data-test=edit-comment-cancel]", HTMLButtonElement).click();

            expect(read_only_comment.innerHTML).toContain(rendered_comment);
            expect(edit_button.classList.contains(HIDE_CLASS)).toBe(false);
            expect(read_only_comment.classList.contains(HIDE_CLASS)).toBe(false);
            expect(read_only_comment.classList.contains(HIGHLIGHT_CLASS)).toBe(false);
        });
    });
});
