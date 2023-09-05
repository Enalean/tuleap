/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { describe, it, expect, beforeEach } from "vitest";
import type { FeedbackLevel } from "./feedback";
import { addFeedback, clearAllFeedbacks } from "./feedback";

const MESSAGE = "A feedback message";
const MESSAGE_WITH_HTML_LINK = 'A feedback message with <a href="#">link</a>';

describe(`Feedback`, () => {
    let doc: Document;
    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    const insertFeedbackElement = (): HTMLDivElement => {
        const feedback = doc.createElement("div");
        feedback.id = "feedback";
        doc.body.append(feedback);
        return feedback;
    };

    describe(`addFeedback`, () => {
        it(`does nothing if there is no element with id #feedback`, () => {
            addFeedback(doc, "info", MESSAGE);

            expect(doc.body.childNodes).toHaveLength(0);
        });

        it(`renders the given feedback message`, () => {
            const feedback = insertFeedbackElement();
            addFeedback(doc, "warning", MESSAGE);

            expect(feedback.firstChild?.textContent).toBe(MESSAGE);
        });

        it(`renders the given feedback message with HTML links`, () => {
            const feedback = insertFeedbackElement();
            addFeedback(doc, "info", MESSAGE_WITH_HTML_LINK);

            expect(feedback.getElementsByTagName("li").item(0)?.innerHTML).toBe(
                MESSAGE_WITH_HTML_LINK,
            );
        });

        const info: FeedbackLevel = "info";
        const error: FeedbackLevel = "error";
        const warning: FeedbackLevel = "warning";

        it.each([info, error, warning])(
            `creates a CSS classname with level %s and sets it to the ul inside the feedback`,
            (level: FeedbackLevel) => {
                const feedback = insertFeedbackElement();
                addFeedback(doc, level, MESSAGE);

                const ul_element = feedback.querySelector("ul");
                if (!(ul_element instanceof HTMLUListElement)) {
                    throw new Error("Expected element not found");
                }
                expect(ul_element.classList.contains(`feedback_${level}`)).toBe(true);
            },
        );
    });

    describe(`clearAllFeedbacks()`, () => {
        it(`does nothing if there is no element with id #feedback`, () => {
            doc.body.insertAdjacentText("beforeend", "Some text");
            clearAllFeedbacks(doc);

            expect(doc.body.childNodes).toHaveLength(1);
        });

        it(`clears all children of the #feedback element`, () => {
            const feedback = insertFeedbackElement();
            addFeedback(doc, "warning", MESSAGE);

            expect(feedback.childNodes).toHaveLength(1);
            clearAllFeedbacks(doc);

            expect(feedback.childNodes).toHaveLength(0);
        });
    });
});
