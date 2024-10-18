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
import type { InternalHeadingsItem } from "./text-style";
import type { GetText } from "@tuleap/gettext";

export const renderPreformattedTextOption = (
    host: InternalHeadingsItem,
    gettext_provider: GetText,
): UpdateFunction<InternalHeadingsItem> => {
    if (!host.style_elements.preformatted) {
        return html``;
    }

    const onClickApplyPreformattedText = (): void => {
        if (host.is_preformatted_text_activated) {
            return;
        }

        host.toolbar_bus.preformattedText();
    };

    return html`
        <option
            selected="${host.is_preformatted_text_activated}"
            title="${gettext_provider.gettext("Change to preformatted text")}"
            onclick="${onClickApplyPreformattedText}"
        >
            ${gettext_provider.gettext("Preformatted")}
        </option>
    `;
};
