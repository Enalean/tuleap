/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

export function selfClosingInfo(message) {
    const duration_in_ms = 5000;
    const feedback_element = document.getElementById("feedback");
    if (!feedback_element) {
        return;
    }

    const section = document.createElement("section");
    const content = document.createElement("p");
    content.insertAdjacentText("beforeend", message);
    content.classList.add("tlp-alert-info");
    section.appendChild(content);

    feedback_element.appendChild(section);
    window.setTimeout(() => section.remove(), duration_in_ms);
}

export function addFeedback(level, message) {
    const feedback_element = document.getElementById("feedback");
    if (!feedback_element) {
        return;
    }

    const ul_element = document.createElement("ul");
    ul_element.classList.add(`feedback_${level}`);
    const feedback_content_element = document.createElement("li");
    feedback_content_element.insertAdjacentText("beforeend", message);
    ul_element.appendChild(feedback_content_element);

    feedback_element.appendChild(ul_element);
}

export function clearAllFeedbacks() {
    const feedback_element = document.getElementById("feedback");
    if (!feedback_element) {
        return;
    }

    while (feedback_element.firstChild) {
        feedback_element.removeChild(feedback_element.firstChild);
    }
}
