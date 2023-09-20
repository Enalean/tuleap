/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

import { get } from "@tuleap/tlp-fetch";
import { sanitize } from "dompurify";

document.addEventListener("DOMContentLoaded", () => {
    const markup_buttons = document.getElementsByClassName("toggle-diff");

    for (const diff_button of markup_buttons) {
        diff_button.addEventListener("click", handleClickDiffButton);
    }
});

const notification_placeholder_element = document.getElementById("notification-placeholder");
if (notification_placeholder_element) {
    notification_placeholder_element.addEventListener("change", () => {
        const diff_elements =
            notification_placeholder_element.getElementsByClassName("toggle-diff");
        for (const diff_button of diff_elements) {
            diff_button.addEventListener("click", handleClickDiffButton);
        }
    });
}

async function handleClickDiffButton(event: Event): Promise<void> {
    event.preventDefault();

    if (!event.target || !(event.target instanceof HTMLElement)) {
        throw new Error("No target for event on clicking diff button");
    }

    const diff_button = event.target;

    let changeset_id;
    let field_id;
    if (diff_button instanceof HTMLElement) {
        changeset_id = diff_button.dataset.changesetId;
        field_id = diff_button.dataset.fieldId;
    }

    if (!changeset_id) {
        throw new Error("Missing changeset id -" + changeset_id);
    }

    if (!field_id) {
        throw new Error("Missing field id -" + field_id);
    }

    await toggleDiffContent(
        diff_button,
        document,
        changeset_id,
        field_id,
        "strip-html",
        "show-diff-follow-up",
    );

    toggleIcon(diff_button);
    toggleMarkupButton(diff_button, changeset_id, field_id);
}

export async function toggleDiffContent(
    diff_button: Element,
    document: Document,
    changeset_id: string,
    field_id: string,
    format: string,
    spinner_to_toogle: string,
): Promise<void> {
    const diff = await document.getElementById(
        `tracker-changeset-diff-comment-${changeset_id}-${field_id}`,
    );
    if (!diff) {
        return;
    }

    if (diff instanceof HTMLElement && shouldLoadSomeContent(diff, diff_button, format)) {
        const spinner_icon = diff_button.getElementsByClassName(spinner_to_toogle);
        if (spinner_icon[0]) {
            spinner_icon[0].classList.add("fa-spin", "fa-circle-o-notch");
        }

        const artifact_id = diff.dataset.artifactId;
        if (!artifact_id) {
            throw new Error("Missing artifact id - " + changeset_id);
        }

        const field_id = diff.dataset.fieldId;
        if (!field_id) {
            throw new Error("Missing field id - " + changeset_id);
        }

        const only_formatted_message = getFormattedDiffDiv(changeset_id, field_id, document);
        only_formatted_message.classList.add("hide-only-formatted-diff-message");

        const error_message = document.getElementById(
            `tracker-changeset-diff-error-${changeset_id}-${field_id}`,
        );
        if (!error_message) {
            throw new Error("Missing error message field error-" + changeset_id);
        }
        error_message.classList.add("hide-diff-error-message");

        const url =
            "/plugins/tracker/changeset/" +
            encodeURI(changeset_id) +
            "/diff/" +
            encodeURI(format) +
            "/" +
            encodeURI(artifact_id) +
            "/" +
            encodeURI(field_id);

        try {
            const response = await get(url);

            const generated_diff = await response.json();
            diff.innerHTML = sanitize(generated_diff);

            if (generated_diff === "") {
                const only_formatted_message = getFormattedDiffDiv(
                    changeset_id,
                    field_id,
                    document,
                );
                only_formatted_message.classList.remove("hide-only-formatted-diff-message");
            }
            diff.setAttribute("last-load-by", format);
        } catch (e) {
            error_message.classList.remove("hide-diff-error-message");
            throw e;
        } finally {
            if (spinner_icon[0]) {
                spinner_icon[0].classList.remove("fa-spin", "fa-circle-o-notch");
            }
        }
    }

    if (format === "strip-html") {
        diff.classList.toggle("follow-up-diff");
    }
}

export function toggleIcon(diff_button: Element): void {
    const right_icon_list = diff_button.getElementsByClassName("fa-caret-right");
    const left_icon_list = diff_button.getElementsByClassName("fa-caret-down");
    const right_icon = right_icon_list[0];
    const left_icon = left_icon_list[0];
    if (right_icon) {
        right_icon.classList.remove("fa-caret-right");
        right_icon.classList.add("fa-caret-down");
    } else if (left_icon) {
        left_icon.classList.remove("fa-caret-down");
        left_icon.classList.add("fa-caret-right");
    }
}

export function shouldLoadSomeContent(
    diff: Element,
    diff_button: Element,
    new_format: string,
): boolean {
    const last_load_by = diff.getAttribute("last-load-by");

    if (!last_load_by) {
        return true;
    }

    if (last_load_by === "strip-html" && new_format !== "strip-html") {
        return true;
    }

    const left_icon_list = diff_button.getElementsByClassName("fa-caret-down");
    const left_icon = left_icon_list[0];
    let is_open = false;
    if (left_icon) {
        is_open = left_icon.classList.length > 0;
    }
    return last_load_by === "html" && new_format === "strip-html" && !is_open;
}

function toggleMarkupButton(diff_button: Element, changeset_id: string, field_id: string): void {
    const markup_button = document.getElementById(
        `tracker-changeset-markup-diff-button-${changeset_id}-${field_id}`,
    );

    if (!markup_button) {
        return;
    }

    const left_icon_list = diff_button.getElementsByClassName("fa-caret-down");
    const left_icon = left_icon_list[0];
    let is_open = false;
    if (left_icon) {
        is_open = left_icon.classList.length > 0;
    }

    if (is_open) {
        markup_button.classList.remove("markup-diff");
    } else {
        markup_button.classList.add("markup-diff");
    }

    markup_button.addEventListener("click", async function (event: Event) {
        event.preventDefault();
        if (markup_button instanceof HTMLElement) {
            const changeset_id = markup_button.dataset.changesetId;
            const field_id = markup_button.dataset.fieldId;
            if (!changeset_id) {
                throw new Error("Missing changeset id -" + changeset_id);
            }
            if (!field_id) {
                throw new Error("Missing field id -" + field_id);
            }

            try {
                await toggleDiffContent(
                    markup_button,
                    document,
                    changeset_id,
                    field_id,
                    "html",
                    "show-markup-diff-follow-up",
                );
            } finally {
                markup_button.classList.add("markup-diff");
            }
        }
    });
}

function getFormattedDiffDiv(
    changeset_id: string,
    field_id: string,
    document: Document,
): HTMLElement {
    const only_formatted_message = document.getElementById(
        `tracker-changeset-only-formatted-diff-info-${changeset_id}-${field_id}`,
    );
    if (!only_formatted_message) {
        throw new Error("Missing only formatted dff message" + changeset_id);
    }

    return only_formatted_message;
}
