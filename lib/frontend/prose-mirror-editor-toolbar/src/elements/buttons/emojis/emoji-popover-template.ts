/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import type { UpdateFunction } from "hybrids";
import { html } from "hybrids";
import type { InternalEmojiButton } from "./emojis";
import type { GetText } from "@tuleap/gettext";
import { getEmojiDB } from "@tuleap/prose-mirror-editor";

const onClick = (host: InternalEmojiButton, event: MouseEvent): void => {
    event.preventDefault();
    const target = event.target;
    if (!(target instanceof HTMLButtonElement)) {
        return;
    }
    host.popover_instance.hide();
    host.toolbar_bus.emoji({ emoji: target.value });
};

const onFilter = (host: InternalEmojiButton, event: InputEvent): void => {
    const target = event.target;
    if (!(target instanceof HTMLInputElement)) {
        return;
    }
    const search_string = target.value;
    document.querySelectorAll<HTMLButtonElement>(".emoji-button").forEach((button) => {
        if (!button.getAttribute("data-name")?.includes(search_string.toLowerCase())) {
            button.classList.add("emoji-button-hide");
        } else {
            button.classList.remove("emoji-button-hide");
        }
    });
};

const getEmojisList = (db: ReadonlyMap<string, string>): Array<[string, string]> => {
    const keys = Array.from(db.keys()).filter((key) => !key.includes("_"));
    return keys.map((key): [string, string] => [
        key.replaceAll("-", " ").toLowerCase(),
        db.get(key) ?? "",
    ]);
};

const renderEmojiButton = (name: string, emoji: string): UpdateFunction<InternalEmojiButton> =>
    html`<button
        class="emoji-button"
        value="${emoji}"
        data-name="${name}"
        title="${name}"
        onclick="${onClick}"
        data-test="emoji-button"
    >
        ${emoji}
    </button>`;

export const renderEmojiPopover = (
    host: InternalEmojiButton,
    gettext_provider: GetText,
): UpdateFunction<InternalEmojiButton> =>
    html` <div
        data-role="popover"
        class="tlp-popover prose-mirror-toolbar-popover"
        data-test="toolbar-emoji-popover-form"
    >
        <div class="tlp-popover-arrow"></div>
        <div class="tlp-popover-header">
            <h1 class="tlp-popover-title">${gettext_provider.gettext("Insert new emoji")}</h1>
        </div>
        <div class="tlp-popover-body">
            <div class="tlp-form-element">
                <input
                    type="text"
                    id="search"
                    name="search"
                    class="tlp-input"
                    placeholder="${gettext_provider.gettext("Search...")}"
                    oninput="${onFilter}"
                    data-test="emoji-filter"
                />
            </div>
        </div>
        <div class="tlp-popover-body popover-scroll emoji-grid" data-test="emoji-grid">
            ${getEmojisList(getEmojiDB()).map(([name, emoji]) => renderEmojiButton(name, emoji))}
        </div>
    </div>`;
