/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import type { SelectWrappedByListPickerStoreType } from "./SelectWrappedByListPickerStore";
import { createListPicker } from "@tuleap/list-picker";

interface ListPickersCreatorType {
    listenToggleEditionEvents(): void;
    initListPickersInArtifactCreationView(): void;
    initListPickersPostUpdateErrorView(): void;
    initTrackerSelector(): void;
}

export const ListPickersCreator = (
    doc: Document,
    store: SelectWrappedByListPickerStoreType,
): ListPickersCreatorType => {
    const locale = doc.body.dataset.userLocale ?? "en_US";

    function listenToggleEditionEvents(): void {
        doc.querySelectorAll(
            ".tracker_artifact_field-sb > .tracker_formelement_edit, .tracker_artifact_field-msb > .tracker_formelement_edit",
        ).forEach((edit_button) => {
            if (!edit_button.parentElement) {
                return;
            }

            const select = edit_button.parentElement.querySelector("select");
            if (!(select instanceof HTMLSelectElement)) {
                return;
            }

            edit_button.addEventListener("click", () => {
                createListPickerForSelect(select);
                initTargetFieldsIfAny(select);
            });
        });
    }

    function initTargetFieldsIfAny(field: HTMLSelectElement): void {
        if (field.dataset.targetFieldsIds) {
            const target_fields = JSON.parse(field.dataset.targetFieldsIds);
            const ids = target_fields.map((id: string) => {
                return "#tracker_field_" + id;
            });

            doc.querySelectorAll(ids.join(", ")).forEach((field: HTMLSelectElement) => {
                initTargetFieldsIfAny(field);
                createListPickerForSelect(field);
            });
        }
    }

    function initListPickersInArtifactCreationView(): void {
        initListPickers(
            doc.querySelectorAll(
                ".tracker_artifact_field-sb > select, .tracker_artifact_field-msb > select",
            ),
        );
    }

    function initListPickersPostUpdateErrorView(): void {
        initListPickers(
            doc.querySelectorAll(
                ".tracker_artifact_field-sb.in-edition select, .tracker_artifact_field-msb.in-edition select",
            ),
        );
    }

    function initTrackerSelector(): void {
        initListPickers(doc.querySelectorAll("#tracker_select_tracker"));
    }

    function initListPickers(selects: NodeListOf<HTMLSelectElement>): void {
        selects.forEach((select) => {
            createListPickerForSelect(select);
        });
    }

    function createListPickerForSelect(select: HTMLSelectElement): void {
        if (store.isWrapped(select.id)) {
            return;
        }
        createListPicker(select, {
            locale,
            is_filterable: true,
            none_value: getNoneElement(select),
        });
        store.add(select.id);
    }

    return {
        listenToggleEditionEvents,
        initListPickersInArtifactCreationView,
        initListPickersPostUpdateErrorView,
        initTrackerSelector,
    };
};

function getNoneElement(select: HTMLSelectElement): string | null {
    for (const item of select.options) {
        if (item.value === "100") {
            return item.value;
        }
    }
    return null;
}
