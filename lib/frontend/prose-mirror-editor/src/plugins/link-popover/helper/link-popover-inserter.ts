/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import DOMPurify from "dompurify";
import type { GetText } from "@tuleap/gettext";

function buildLinkPopoverId(editor_id: string): string {
    return `link-popover-${editor_id}`;
}

export function removeLinkPopover(doc: Document, editor_id: string): void {
    const existing_menu = doc.getElementById(buildLinkPopoverId(editor_id));
    if (!existing_menu) {
        return;
    }
    existing_menu.remove();
}

export function insertLinkPopover(
    doc: Document,
    gettext_provider: GetText,
    editor_id: string,
    link_href: string,
): HTMLElement | null {
    const menu_id = buildLinkPopoverId(editor_id);

    const popover = DOMPurify.sanitize(
        `
        <section id="${menu_id}" class="tlp-popover prose-mirror-editor-popover-links">
            <div class="tlp-popover-arrow"></div>
            <div class="tlp-popover-body">
                <div class="tlp-button-bar">
                    <div class="tlp-button-bar-item">
                        <a
                            class="tlp-button-outline tlp-button-secondary tlp-button-small"
                            title="${gettext_provider.gettext("Open link")}"
                            href="${link_href}"
                            target="_blank"
                            data-test="open-link-button"
                        >
                            <i class="fa-solid fa-external-link-alt" role="img"></i>
                        </a>
                    </div>
                </div>
        </section>
    `,
        { RETURN_DOM: true, ADD_ATTR: ["target"] },
    );

    doc.body.appendChild(popover);

    return doc.getElementById(menu_id);
}
