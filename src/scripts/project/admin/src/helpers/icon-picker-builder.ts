/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

import type { PopupPickerController } from "@picmo/popup-picker";
import { createPopup } from "@picmo/popup-picker";
import type { GetText } from "@tuleap/gettext";

export function buildIconPicker(
    gettext_provider: GetText,
    doc: Document
): PopupPickerController | null {
    const icon_input = doc.getElementById("icon-input");
    if (!icon_input) {
        return null;
    }

    const project_icons_div = doc.getElementById("form-group-name-icon-input-container");
    const project_icons = project_icons_div?.dataset.allProjectIcons;
    if (!project_icons || project_icons === "") {
        return null;
    }

    return createPopup(
        {
            categories: [
                "smileys-emotion",
                "animals-nature",
                "food-drink",
                "activities",
                "travel-places",
                "objects",
                "symbols",
                "flags",
            ],
            emojiData: JSON.parse(project_icons),
            i18n: {
                "categories.activities": gettext_provider.gettext("Activities"),
                "categories.animals-nature": gettext_provider.gettext("Animals & Nature"),
                "categories.custom": gettext_provider.gettext("Custom"),
                "categories.flags": gettext_provider.gettext("Flags"),
                "categories.food-drink": gettext_provider.gettext("Food & Drink"),
                "categories.objects": gettext_provider.gettext("Objects"),
                "categories.people-body": gettext_provider.gettext("People & Body"),
                "categories.recents": gettext_provider.gettext("Recent Icons"),
                "categories.smileys-emotion": gettext_provider.gettext("Smileys & Emotion"),
                "categories.symbols": gettext_provider.gettext("Symbols"),
                "categories.travel-places": gettext_provider.gettext("Travel & Places"),

                // Shown if there is an error creating or accessing the local emoji database.
                "error.load": gettext_provider.gettext("Failed to load icons"),

                // Messages for the Recents category.
                "recents.clear": gettext_provider.gettext("Clear recent icons"),
                "recents.none": gettext_provider.gettext("You haven't selected any icons yet."),

                // A retry button shown on the error view.
                retry: gettext_provider.gettext("Try again"),

                // Tooltip/title for the clear search button in the search field.
                "search.clear": gettext_provider.gettext("Clear search"),

                // Shown when there is an error searching the emoji database.
                "search.error": gettext_provider.gettext("Failed to search icons"),

                // Shown when no emojis match the search query.
                "search.notFound": gettext_provider.gettext("No icons found"),

                // Placeholder for the search field.
                search: gettext_provider.gettext("Search icon..."),
            },
        },
        {
            triggerElement: icon_input,
            referenceElement: icon_input,
            position: "bottom-start",
            className: "project-admin-icon-picker",
        }
    );
}
