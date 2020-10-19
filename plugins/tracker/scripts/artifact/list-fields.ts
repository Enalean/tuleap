/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { createListPicker } from "@tuleap/list-picker/src/list-picker";

document.addEventListener("DOMContentLoaded", () => {
    listenToggleEditionEvents(document);
    initListPickersInArtifactCreationView(document);
    initListPickersPostUpdateErrorView(document);
    initTrackerSelector(document);
});

export function listenToggleEditionEvents(doc: HTMLDocument): void {
    doc.querySelectorAll(
        ".tracker_artifact_field-sb > .tracker_formelement_edit, .tracker_artifact_field-msb > .tracker_formelement_edit"
    ).forEach((edit_button) => {
        if (!edit_button.parentElement) {
            return;
        }

        const select = edit_button.parentElement.querySelector("select");

        if (!select) {
            return;
        }

        edit_button.addEventListener("click", async () => {
            await createListPicker(select, {
                is_filterable: true,
            });
        });
    });
}

export function initListPickersInArtifactCreationView(doc: HTMLDocument): void {
    initListPickers(
        doc.querySelectorAll(
            ".tracker_artifact_field-sb > select, .tracker_artifact_field-msb > select"
        )
    );
}

export function initListPickersPostUpdateErrorView(doc: HTMLDocument): void {
    initListPickers(
        doc.querySelectorAll(
            ".tracker_artifact_field-sb.in-edition select, .tracker_artifact_field-msb.in-edition select"
        )
    );
}

function initListPickers(selects: NodeListOf<HTMLSelectElement>): void {
    selects.forEach(async (select) => {
        await createListPicker(select, {
            is_filterable: true,
        });
    });
}

function initTrackerSelector(document: HTMLDocument): void {
    initListPickers(document.querySelectorAll("#tracker_select_tracker"));
}
