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
import { getClass } from "../../../helpers/class-getter";

export const renderEmojiButton = (
    host: InternalEmojiButton,
    gettext_provider: GetText,
): UpdateFunction<InternalEmojiButton> =>
    html`<button
        class="${getClass(host)}"
        data-role="popover-trigger"
        disabled="${host.is_disabled}"
        title="${gettext_provider.gettext("Insert emoji `Ctrl+;`")}"
        data-test="button-emoji"
    >
        <i class="prose-mirror-toolbar-button-icon fa-solid fa-face-smile"></i>
    </button>`;
