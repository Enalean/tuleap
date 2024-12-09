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

import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";
import {
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "@tuleap/plugin-tracker-constants";
import { selectOrThrow } from "@tuleap/dom";
import {
    DOMAdapter,
    FORMAT_HIDDEN_INPUT_ID_PREFIX,
    FORMAT_SELECTBOX_ID_PREFIX,
    HIDE_CLASS,
    HIGHLIGHT_CLASS,
} from "./DOMAdapter";

describe(`DOMAdapter`, () => {
    let doc: Document, dom_adapter: DOMAdapter;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        dom_adapter = DOMAdapter(doc);
    });

    function* generateFormats(): Generator<[TextFieldFormat]> {
        yield [TEXT_FORMAT_HTML];
        yield [TEXT_FORMAT_TEXT];
        yield [TEXT_FORMAT_COMMONMARK];
    }

    describe(`findEditCommentButtons()`, () => {
        it(`returns all edit button elements`, () => {
            doc.body.insertAdjacentHTML(
                "beforeend",
                `<button data-edit-comment-button></button><button data-edit-comment-button>`,
            );
            const buttons = dom_adapter.findEditCommentButtons();
            expect(buttons).toHaveLength(2);
        });
    });

    describe(`readInitDataFromBackend()`, () => {
        it(`returns the elements and data needed to edit a comment`, () => {
            const changeset_id = "51";
            const project_id = "140";

            doc.body.insertAdjacentHTML(
                "beforeend",
                `<div data-follow-up>
                    <div><button data-edit-comment-button></button></div>
                    <div
                        data-follow-up-content
                        data-changeset-id="${changeset_id}"
                        data-project-id="${project_id}"
                        data-notifications-disabled
                        data-user-preferred-format="${TEXT_FORMAT_HTML}"
                    >
                        <div data-read-only-comment>Comment body</div>
                    </div>
                </div>`,
            );
            const edit_button = selectOrThrow(doc, "[data-edit-comment-button]");

            const result = dom_adapter.readInitDataFromBackend(edit_button);
            const init_data = result.unwrapOr(null);
            if (!init_data) {
                throw Error("Expected to retrieve the init data");
            }
            expect(init_data.follow_up_content).toBe(doc.querySelector("[data-follow-up-content]"));
            expect(init_data.read_only_comment).toBe(doc.querySelector("[data-read-only-comment]"));
            expect(init_data.changeset_id).toBe(changeset_id);
            expect(init_data.project_id).toBe(project_id);
            expect(init_data.are_notifications_enabled).toBe(false);
            expect(init_data.user_preferred_format).toBe(TEXT_FORMAT_HTML);
        });

        function* generateInvalidHTML(): Generator<[string]> {
            // edit button has no ancestor follow-up
            yield [`<div><button data-edit-comment-button></button></div>`];
            // no follow-up content element in the follow-up
            yield [
                `<div data-follow-up>
                    <div><button data-edit-comment-button></button></div>
                </div>`,
            ];
            // no read-only comment element in the follow-up content
            yield [
                `<div data-follow-up>
                    <div><button data-edit-comment-button></button></div>
                    <div
                        data-follow-up-content
                        data-changeset-id="140"
                        data-project-id="387"
                        data-user-preferred-format="text"
                    ></div>
                </div>`,
            ];
        }

        it.each([...generateInvalidHTML()])(
            `returns nothing when it cannot find the expected HTML structure around the edit button`,
            (html) => {
                doc.body.insertAdjacentHTML("beforeend", html);
                const edit_button = selectOrThrow(doc, "[data-edit-comment-button]");

                const result = dom_adapter.readInitDataFromBackend(edit_button);
                expect(result.isNothing()).toBe(true);
            },
        );
    });

    describe(`createTextArea()`, () => {
        it(`create a textarea element for the user to edit the comment`, () => {
            const id = "tracker_followup_comment_edit_378";
            const project_id = "390";
            const comment_body = "syrt mygalid";

            const textarea = dom_adapter.createTextArea({ id, project_id, comment_body });
            expect(textarea).toBeInstanceOf(HTMLTextAreaElement);
            expect(textarea.id).toBe(id);
            expect(textarea.getAttribute("data-project-id")).toBe(project_id);
            expect(textarea.getAttribute("data-test")).toBe("edit-comment-textarea");
            expect(textarea.rows).toBe(5);
            expect(textarea.textContent).toBe(comment_body);
        });

        it(`when the comment body is longer than 5 lines, it increases the number of rows`, () => {
            const textarea = dom_adapter.createTextArea({
                id: "tracker_followup_comment_edit_920",
                project_id: "922",
                comment_body: `Lorem ipsum
                    Maximusmaximus
                    Maecenascras
                    Nostratincidunt
                    Adipiscingsollicitudin
                    Sagittisdapibus
                    Magnisleo`,
            });

            expect(textarea.rows).toBe(7);
        });
    });

    function createHiddenInput(value: string): void {
        doc.body.insertAdjacentHTML(
            "beforeend",
            `<input id="tracker_artifact_followup_comment_body_format_123" value="${value}">`,
        );
    }

    describe(`readCommentFormatOrDefault()`, () => {
        it(`when the hidden input can't be found, it will default to the user's preferred format`, () => {
            expect(
                dom_adapter.readCommentFormatOrDefault(
                    FORMAT_HIDDEN_INPUT_ID_PREFIX + "123",
                    TEXT_FORMAT_HTML,
                ),
            ).toBe(TEXT_FORMAT_HTML);
        });

        it(`when the hidden input's value is not a valid format, it will default to "commonmark" format`, () => {
            createHiddenInput("invalid");

            expect(
                dom_adapter.readCommentFormatOrDefault(
                    FORMAT_HIDDEN_INPUT_ID_PREFIX + "123",
                    TEXT_FORMAT_HTML,
                ),
            ).toBe(TEXT_FORMAT_COMMONMARK);
        });

        it.each([...generateFormats()])(
            `when the hidden input's value is %s, it will return it`,
            (expected_format) => {
                createHiddenInput(expected_format);
                expect(
                    dom_adapter.readCommentFormatOrDefault(
                        FORMAT_HIDDEN_INPUT_ID_PREFIX + "123",
                        TEXT_FORMAT_HTML,
                    ),
                ).toBe(expected_format);
            },
        );

        it.each([...generateFormats()])(
            `finds the selectbox from the rich text editor and returns its format`,
            (expected_format) => {
                doc.body.insertAdjacentHTML(
                    "beforeend",
                    `<select id="rte_format_selectbox603"><option value="${expected_format}" selected></option></select>`,
                );
                expect(
                    dom_adapter.readCommentFormatOrDefault(
                        FORMAT_SELECTBOX_ID_PREFIX + "603",
                        TEXT_FORMAT_HTML,
                    ),
                ).toBe(expected_format);
            },
        );
    });

    describe(`readCommentBodyOrDefault()`, () => {
        let follow_up_content: HTMLElement;
        beforeEach(() => {
            follow_up_content = doc.createElement("div");
            doc.body.append(follow_up_content);
        });

        it(`when the comment body element can't be found, it will default to empty string`, () => {
            expect(dom_adapter.readCommentBodyOrDefault(follow_up_content, TEXT_FORMAT_TEXT)).toBe(
                "",
            );
        });

        it(`when the given format is html, it returns the comment body's trimmed innerHTML`, () => {
            follow_up_content.insertAdjacentHTML(
                "beforeend",
                `<div data-comment-body>
                        <p>Some <strong>HTML</strong> content</p>
                    </div>`,
            );

            expect(dom_adapter.readCommentBodyOrDefault(follow_up_content, TEXT_FORMAT_HTML)).toBe(
                `<p>Some <strong>HTML</strong> content</p>`,
            );
        });

        describe(`when the given format is text`, () => {
            it(`returns the comment body's trimmed textContent`, () => {
                follow_up_content.insertAdjacentHTML(
                    "beforeend",
                    `<div data-comment-body>
                            Some Text content
                        </div>`,
                );
                expect(
                    dom_adapter.readCommentBodyOrDefault(follow_up_content, TEXT_FORMAT_TEXT),
                ).toBe("Some Text content");
            });

            it(`defaults the textContent to empty string`, () => {
                follow_up_content.insertAdjacentHTML("beforeend", `<div data-comment-body></div>`);

                expect(
                    dom_adapter.readCommentBodyOrDefault(follow_up_content, TEXT_FORMAT_TEXT),
                ).toBe("");
            });
        });

        describe(`when the given format is commonmark`, () => {
            it(`returns the comment body's data-commonmark-source attribute`, () => {
                follow_up_content.insertAdjacentHTML(
                    "beforeend",
                    `<div
                            data-comment-body
                            data-commonmark-source="Some **Markdown** content"
                        ><p>Some <strong>Markdown</strong> content</p></div>`,
                );

                expect(
                    dom_adapter.readCommentBodyOrDefault(follow_up_content, TEXT_FORMAT_COMMONMARK),
                ).toBe("Some **Markdown** content");
            });

            it(`defaults the attribute to empty string`, () => {
                follow_up_content.insertAdjacentHTML(
                    "beforeend",
                    `<div data-comment-body><p>Some <strong>Markdown</strong> content</p></div>`,
                );

                expect(
                    dom_adapter.readCommentBodyOrDefault(follow_up_content, TEXT_FORMAT_COMMONMARK),
                ).toBe("");
            });
        });
    });

    describe(`hide/show`, () => {
        it(`adds and removes the CSS class from the given element to hide/show it`, () => {
            const element = doc.createElement("div");
            dom_adapter.hide(element);
            expect(element.classList.contains(HIDE_CLASS)).toBe(true);
            dom_adapter.show(element);
            expect(element.classList.contains(HIDE_CLASS)).toBe(false);
        });
    });

    describe(`highlight()`, () => {
        it(`adds a CSS class to the given element to show a highlight animation`, () => {
            const element = doc.createElement("div");
            dom_adapter.highlight(element);
            expect(element.classList.contains(HIGHLIGHT_CLASS)).toBe(true);
        });
    });
});
