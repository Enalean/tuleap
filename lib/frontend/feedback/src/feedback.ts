/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
import { ERROR } from "./main";

export type FeedbackLevel = "success" | "error" | "info" | "warning";

export function addFeedback(doc: Document, level: FeedbackLevel, message: string): void {
    const feedback_element = doc.getElementById("feedback");
    if (!feedback_element) {
        return;
    }

    const div_element = doc.createElement("div");
    div_element.classList.add(level === ERROR ? `tlp-alert-danger` : `tlp-alert-${level}`);
    const message_element = dompurify.sanitize(message, {
        RETURN_DOM_FRAGMENT: true,
        ALLOWED_TAGS: ["a"],
    });
    div_element.appendChild(message_element);

    feedback_element.appendChild(div_element);
}

export function clearAllFeedbacks(doc: Document): void {
    const feedback_element = doc.getElementById("feedback");
    if (!feedback_element) {
        return;
    }

    feedback_element.replaceChildren();
}
