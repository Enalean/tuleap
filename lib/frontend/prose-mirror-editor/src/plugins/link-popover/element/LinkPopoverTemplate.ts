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
import type { InternalLinkPopoverElement } from "./LinkPopoverElement";

const renderButtons = (
    host: InternalLinkPopoverElement,
): UpdateFunction<InternalLinkPopoverElement> => html`
    <section data-role="popover" class="tlp-popover prose-mirror-editor-popover-links">
        <div class="tlp-popover-arrow"></div>
        <div class="tlp-popover-body">
            <div class="tlp-button-bar">${host.buttons_renderer.render(host)}</div>
        </div>
    </section>
`;

export const renderLinkPopover = (
    host: InternalLinkPopoverElement,
): UpdateFunction<InternalLinkPopoverElement> => {
    if (host.is_in_edition_mode) {
        return html`${host.edition_form_renderer.render(host)}`;
    }

    return renderButtons(host);
};
