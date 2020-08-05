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

import { get } from "../../../../src/themes/tlp/src/js/fetch-wrapper";
import { sanitize } from "dompurify";

document.addEventListener("DOMContentLoaded", async () => {
    await showDiffDirectlyIfInUrl();

    const toggle_diff_buttons = document.getElementsByClassName("toggle-diff");

    for (const diff_button of toggle_diff_buttons) {
        diff_button.addEventListener("click", async function (event: Event) {
            event.preventDefault();
            toggleIcon(diff_button);
            await toggleDiffContent(diff_button, document);
        });
    }
});

export async function toggleDiffContent(diff_button: Element, document: Document): Promise<void> {
    const diff = await diff_button.nextElementSibling;
    if (!diff) {
        return;
    }

    if (diff instanceof HTMLElement && diff.innerHTML === "") {
        const spinner_icon = diff_button.getElementsByClassName("show-diff-follow-up");
        if (spinner_icon[0]) {
            spinner_icon[0].classList.add("fa-spin", "fa-circle-o-notch");
        }

        const changeset_id = diff.dataset.changesetId;

        if (!changeset_id) {
            throw new Error("Missing changeset id -" + changeset_id);
        }

        const artifact_id = diff.dataset.artifactId;
        if (!artifact_id) {
            throw new Error("Missing artifact id - " + changeset_id);
        }
        const field_id = diff.dataset.fieldId;
        if (!field_id) {
            throw new Error("Missing field id - " + changeset_id);
        }
        const format = diff.dataset.format;
        if (!format) {
            throw new Error("Missing format - " + changeset_id);
        }

        const error_message = document.getElementById(
            `tracker-changeset-diff-error-${changeset_id}-${field_id}`
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

            diff.innerHTML = sanitize(await response.json());
        } catch (e) {
            error_message.classList.remove("hide-diff-error-message");
            throw e;
        } finally {
            if (spinner_icon[0]) {
                spinner_icon[0].classList.remove("fa-spin", "fa-circle-o-notch");
            }
        }
    }

    diff.classList.toggle("follow-up-diff");
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

async function showDiffDirectlyIfInUrl(): Promise<void> {
    const url = document.location.toString(),
        reg_ex = /#followup_(\d+)/,
        matches = url.match(reg_ex);

    if (!matches) {
        return;
    }

    const followup_id = matches[1];

    const follow_up = document.getElementById("followup_" + followup_id);
    if (!follow_up) {
        throw Error("Follow up " + followup_id + " not foud in DOM");
    }

    const toggle_diff_button = follow_up.getElementsByClassName("toggle-diff");

    if (!toggle_diff_button[0]) {
        throw Error("Changeset " + followup_id + "does not have a diff button");
    }
    toggleIcon(toggle_diff_button[0]);
    await toggleDiffContent(toggle_diff_button[0], document);
}
