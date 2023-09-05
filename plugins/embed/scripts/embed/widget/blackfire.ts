/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import insertWidget from "./insertWidget";

export const blackfire_pattern = /^https:\/\/blackfire.io\/profiles\/(?:.*)\/graph$/;

export function insertBlackfire(link: HTMLAnchorElement): void {
    if (!link.ownerDocument) {
        throw Error(
            "Embeddable link does not have a top-level document. Perhaps it is a document itself?",
        );
    }

    const widget = link.ownerDocument.createElement("iframe");
    widget.setAttribute("height", "650");
    widget.setAttribute("width", "100%");
    widget.setAttribute("allowfullscreen", "");
    widget.setAttribute("referrerpolicy", "same-origin");
    widget.setAttribute("sandbox", "allow-scripts allow-same-origin");

    insertWidget(link, widget);

    widget.src = link.href.replace(/graph$/, "embed");
}
