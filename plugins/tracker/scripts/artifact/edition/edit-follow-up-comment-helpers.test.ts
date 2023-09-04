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

import {
    getFormatOrDefault,
    getLocaleFromBody,
    getTextAreaValue,
    getProjectId,
} from "./edit-follow-up-comment-helpers";
import {
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "@tuleap/plugin-tracker-constants";

describe(`edit-follow-up-comment-helpers`, () => {
    let doc: Document;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    describe(`getFormatOrDefault()`, () => {
        it(`when the hidden input can't be found, it will default to "commonmark" format`, () => {
            expect(getFormatOrDefault(doc, "123")).toStrictEqual(TEXT_FORMAT_COMMONMARK);
        });

        it(`when the hidden input's value is not a valid format, it will default to "commonmark" format`, () => {
            createHiddenInput(doc, "invalid");

            expect(getFormatOrDefault(doc, "123")).toStrictEqual(TEXT_FORMAT_COMMONMARK);
        });

        it.each([[TEXT_FORMAT_HTML], [TEXT_FORMAT_TEXT], [TEXT_FORMAT_COMMONMARK]])(
            `when the hidden input's value is %s, it will return it`,
            (expected_format: string) => {
                createHiddenInput(doc, expected_format);
                expect(getFormatOrDefault(doc, "123")).toStrictEqual(expected_format);
            },
        );
    });

    describe(`getTextAreaValue()`, () => {
        let comment_panel: Element;
        beforeEach(() => {
            comment_panel = doc.createElement("div");
            doc.body.append(comment_panel);
        });

        it(`when the comment body element can't be found, it will default to empty string`, () => {
            expect(getTextAreaValue(comment_panel, TEXT_FORMAT_TEXT)).toBe("");
        });

        it(`when the given format is html, it returns the comment body's trimmed innerHTML`, () => {
            comment_panel.insertAdjacentHTML(
                "beforeend",
                `<div class="tracker_artifact_followup_comment_body">
                        <p>Some <strong>HTML</strong> content</p>
                    </div>`,
            );

            expect(getTextAreaValue(comment_panel, TEXT_FORMAT_HTML)).toBe(
                `<p>Some <strong>HTML</strong> content</p>`,
            );
        });

        describe(`when the given format is text`, () => {
            it(`returns the comment body's trimmed textContent`, () => {
                comment_panel.insertAdjacentHTML(
                    "beforeend",
                    `<div class="tracker_artifact_followup_comment_body">
                            Some Text content
                        </div>`,
                );
                expect(getTextAreaValue(comment_panel, TEXT_FORMAT_TEXT)).toBe("Some Text content");
            });

            it(`defaults the textContent to empty string`, () => {
                comment_panel.insertAdjacentHTML(
                    "beforeend",
                    `<div class="tracker_artifact_followup_comment_body"></div>`,
                );

                expect(getTextAreaValue(comment_panel, TEXT_FORMAT_TEXT)).toBe("");
            });
        });

        describe(`when the given format is commonmark`, () => {
            it(`returns the comment body's data-commonmark-source attribute`, () => {
                comment_panel.insertAdjacentHTML(
                    "beforeend",
                    `<div
                            class="tracker_artifact_followup_comment_body"
                            data-commonmark-source="Some **Markdown** content"
                        ><p>Some <strong>Markdown</strong> content</p></div>`,
                );

                expect(getTextAreaValue(comment_panel, TEXT_FORMAT_COMMONMARK)).toBe(
                    "Some **Markdown** content",
                );
            });

            it(`defaults the attribute to empty string`, () => {
                comment_panel.insertAdjacentHTML(
                    "beforeend",
                    `<div class="tracker_artifact_followup_comment_body"><p>Some <strong>Markdown</strong> content</p></div>`,
                );

                expect(getTextAreaValue(comment_panel, TEXT_FORMAT_COMMONMARK)).toBe("");
            });
        });
    });

    describe(`getProjectId()`, () => {
        it(`when the followup body has no [data-project-id], it will throw an error`, () => {
            const followups = doc.createElement("ul");
            const followup_body = doc.createElement("li");
            followups.append(followup_body);
            doc.body.append(followups);

            expect(() => getProjectId(followup_body)).toThrow();
        });

        it(`will return the value of the [data-project-id] from the followup body`, () => {
            const followups = doc.createElement("ul");
            const followup_body = doc.createElement("li");
            followup_body.dataset.projectId = "128";
            followups.append(followup_body);
            doc.body.append(followups);

            expect(getProjectId(followup_body)).toBe("128");
        });
    });

    describe(`getLocaleFromBody()`, () => {
        it(`returns the body's data-user-locale attribute`, () => {
            doc.body.dataset.userLocale = "fr_FR";
            expect(getLocaleFromBody(doc)).toBe("fr_FR");
        });

        it(`defaults to en_US`, () => {
            expect(getLocaleFromBody(doc)).toBe("en_US");
        });
    });
});

function createHiddenInput(doc: Document, value: string): void {
    doc.body.insertAdjacentHTML(
        "beforeend",
        `<input id="tracker_artifact_followup_comment_body_format_123" value="${value}">`,
    );
}
