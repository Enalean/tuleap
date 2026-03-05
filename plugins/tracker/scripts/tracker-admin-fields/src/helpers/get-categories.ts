/*
 * Copyright (c) Enalean, 2026-present. All Rights Reserved.
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

import type { Ref } from "vue";
import type { StructureFields } from "@tuleap/plugin-tracker-rest-api-types";
import type { CategoryOfPaletteFields, PaletteField } from "../components/Sidebar/Palette/type";
import { getIconFromFieldType } from "./get-icon-from-field-type";
import {
    ARTIFACT_ID_FIELD,
    ARTIFACT_ID_IN_TRACKER_FIELD,
    ARTIFACT_LINK_FIELD,
    CHECKBOX_FIELD,
    COMPUTED_FIELD,
    CONTAINER_FIELDSET,
    CROSS_REFERENCE_FIELD,
    DATE_FIELD,
    FILE_FIELD,
    FLOAT_FIELD,
    INT_FIELD,
    LAST_UPDATE_DATE_FIELD,
    LAST_UPDATED_BY_FIELD,
    MULTI_SELECTBOX_FIELD,
    OPEN_LIST_FIELD,
    PERMISSION_FIELD,
    PRIORITY_FIELD,
    RADIO_BUTTON_FIELD,
    SELECTBOX_FIELD,
    SEPARATOR,
    STATIC_RICH_TEXT,
    STRING_FIELD,
    SUBMISSION_DATE_FIELD,
    SUBMITTED_BY_FIELD,
    TEXT_FIELD,
} from "@tuleap/plugin-tracker-constants";
import type { VueGettextProvider } from "./vue-gettext-provider";

export function getCategories(
    unused_fields: Ref<StructureFields[]>,
    gettext_provider: VueGettextProvider,
): CategoryOfPaletteFields[] {
    const unused_fields_palette: PaletteField[] = unused_fields.value.map((field) => {
        return { label: field.label, icon: getIconFromFieldType(field.type) };
    });

    const base_categories = [
        {
            label: gettext_provider.$gettext("Fields"),
            fields: [
                {
                    label: gettext_provider.$gettext("String"),
                    icon: getIconFromFieldType(STRING_FIELD),
                },
                {
                    label: gettext_provider.$gettext("Text"),
                    icon: getIconFromFieldType(TEXT_FIELD),
                },
                {
                    label: gettext_provider.$gettext("Integer"),
                    icon: getIconFromFieldType(INT_FIELD),
                },
                {
                    label: gettext_provider.$gettext("Float"),
                    icon: getIconFromFieldType(FLOAT_FIELD),
                },
                {
                    label: gettext_provider.$gettext("Date"),
                    icon: getIconFromFieldType(DATE_FIELD),
                },
                {
                    label: gettext_provider.$gettext("Selectbox"),
                    icon: getIconFromFieldType(SELECTBOX_FIELD),
                },
                {
                    label: gettext_provider.$gettext("Multi selectbox"),
                    icon: getIconFromFieldType(MULTI_SELECTBOX_FIELD),
                },
                {
                    label: gettext_provider.$gettext("Open list"),
                    icon: getIconFromFieldType(OPEN_LIST_FIELD),
                },
                {
                    label: gettext_provider.$gettext("Radio"),
                    icon: getIconFromFieldType(RADIO_BUTTON_FIELD),
                },
                {
                    label: gettext_provider.$gettext("Checkbox"),
                    icon: getIconFromFieldType(CHECKBOX_FIELD),
                },
                {
                    label: gettext_provider.$gettext("File upload"),
                    icon: getIconFromFieldType(FILE_FIELD),
                },
                {
                    label: gettext_provider.$gettext("Artifact link"),
                    icon: getIconFromFieldType(ARTIFACT_LINK_FIELD),
                },
                {
                    label: gettext_provider.$gettext("Permissions on artifact"),
                    icon: getIconFromFieldType(PERMISSION_FIELD),
                },
                {
                    label: gettext_provider.$gettext("Shared field"),
                    icon: getIconFromFieldType("shared"),
                },
                {
                    label: gettext_provider.$gettext("Last update by"),
                    icon: getIconFromFieldType(LAST_UPDATED_BY_FIELD),
                },
                {
                    label: gettext_provider.$gettext("Last update date"),
                    icon: getIconFromFieldType(LAST_UPDATE_DATE_FIELD),
                },
                {
                    label: gettext_provider.$gettext("Submitted by"),
                    icon: getIconFromFieldType(SUBMITTED_BY_FIELD),
                },
                {
                    label: gettext_provider.$gettext("Submitted on"),
                    icon: getIconFromFieldType(SUBMISSION_DATE_FIELD),
                },
                {
                    label: gettext_provider.$gettext("Artifact id"),
                    icon: getIconFromFieldType(ARTIFACT_ID_FIELD),
                },
                {
                    label: gettext_provider.$gettext("Per tracker id"),
                    icon: getIconFromFieldType(ARTIFACT_ID_IN_TRACKER_FIELD),
                },
                {
                    label: gettext_provider.$gettext("Cross references"),
                    icon: getIconFromFieldType(CROSS_REFERENCE_FIELD),
                },
                {
                    label: gettext_provider.$gettext("Computed value"),
                    icon: getIconFromFieldType(COMPUTED_FIELD),
                },
                {
                    label: gettext_provider.$gettext("Rank"),
                    icon: getIconFromFieldType(PRIORITY_FIELD),
                },
            ].toSorted((a, b) => a.label.localeCompare(b.label)),
        },
        {
            label: gettext_provider.$gettext("Layout & structure"),
            fields: [
                {
                    label: gettext_provider.$gettext("Fieldset"),
                    icon: getIconFromFieldType(CONTAINER_FIELDSET),
                },
                {
                    label: gettext_provider.$gettext("Separator"),
                    icon: getIconFromFieldType(SEPARATOR),
                },
                {
                    label: gettext_provider.$gettext("Static text"),
                    icon: getIconFromFieldType(STATIC_RICH_TEXT),
                },
            ].toSorted((a, b) => a.label.localeCompare(b.label)),
        },
    ];

    if (unused_fields_palette.length > 0) {
        base_categories.push({
            label: gettext_provider.$gettext("Unused fields"),
            fields: unused_fields_palette.toSorted((a, b) => a.label.localeCompare(b.label)),
        });
    }

    return base_categories;
}
