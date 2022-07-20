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

import { EmojiButton } from "@joeattardi/emoji-button";
import type { GetText } from "@tuleap/gettext";

export function buildIconPicker(gettext_provider: GetText, doc: Document): EmojiButton | null {
    const project_icons_div = doc.getElementById("form-group-name-icon-input-container");
    const project_icons = project_icons_div?.dataset.allProjectIcons;
    if (!project_icons || project_icons === "") {
        return null;
    }

    return new EmojiButton({
        categories: [
            "smileys",
            "animals",
            "food",
            "activities",
            "travel",
            "objects",
            "symbols",
            "flags",
        ],
        i18n: {
            search: gettext_provider.gettext("Search icon..."),
            categories: {
                recents: gettext_provider.gettext("Recent Icons"),
                smileys: gettext_provider.gettext("Smileys & Emotion"),
                people: gettext_provider.gettext("People & Body"),
                animals: gettext_provider.gettext("Animals & Nature"),
                food: gettext_provider.gettext("Food & Drink"),
                activities: gettext_provider.gettext("Activities"),
                travel: gettext_provider.gettext("Travel & Places"),
                objects: gettext_provider.gettext("Objects"),
                symbols: gettext_provider.gettext("Symbols"),
                flags: gettext_provider.gettext("Flags"),
                custom: gettext_provider.gettext("Custom"),
            },
            notFound: gettext_provider.gettext("No icons found"),
        },
        position: "bottom-start",
        zIndex: 10000, //avoid to be under the select2
        emojiData: JSON.parse(project_icons),
    });
}
