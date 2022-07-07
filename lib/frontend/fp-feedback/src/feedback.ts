/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import dompurify from "dompurify";

export type FeedbackLevel = "error" | "info" | "warning";

export function addFeedback(doc: Document, level: FeedbackLevel, message: string): void {
    const feedback_element = doc.getElementById("feedback");
    if (!feedback_element) {
        return;
    }

    const ul_element = doc.createElement("ul");
    ul_element.classList.add(`feedback_${level}`);
    const feedback_content_element = doc.createElement("li");
    const message_element = dompurify.sanitize(message, {
        RETURN_DOM_FRAGMENT: true,
        ALLOWED_TAGS: ["a"],
    });
    feedback_content_element.appendChild(message_element);
    ul_element.appendChild(feedback_content_element);

    feedback_element.appendChild(ul_element);
}

export function clearAllFeedbacks(doc: Document): void {
    const feedback_element = doc.getElementById("feedback");
    if (!feedback_element) {
        return;
    }

    feedback_element.replaceChildren();
}
