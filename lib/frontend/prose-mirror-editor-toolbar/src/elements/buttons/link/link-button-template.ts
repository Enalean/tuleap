/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import { getClass } from "../../../helpers/class-getter";
import type { InternalLinkButtonElement } from "./link";
import type { GetText } from "@tuleap/gettext";

export const renderLinkButtonElement = (
    host: InternalLinkButtonElement,
    gettext_provider: GetText,
): UpdateFunction<InternalLinkButtonElement> => {
    const button_class = getClass(host);

    return html`
        <button
            class="${button_class}"
            data-role="popover-trigger"
            disabled="${host.is_disabled}"
            title="${gettext_provider.gettext("Create or edit link `Ctrl+k`")}"
            data-test="button-link"
        >
            <i class="prose-mirror-toolbar-button-icon fa-solid fa-link"></i>
        </button>
    `;
};
