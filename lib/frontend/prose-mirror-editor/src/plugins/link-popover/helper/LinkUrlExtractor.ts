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

import type { DetectLinkNode } from "./LinkNodeDetector";
import type { FindEditorNodeAtPosition } from "./EditorNodeAtPositionFinder";

export type ExtractLinkUrl = {
    extractLinkUrl(position: number): string;
};

export const LinkUrlExtractor = (
    find_editor_node: FindEditorNodeAtPosition,
    detect_link: DetectLinkNode,
): ExtractLinkUrl => ({
    extractLinkUrl: (position): string => {
        const node = find_editor_node.findNodeAtPosition(position);
        if (!node || !detect_link.isLinkNode(node)) {
            return "";
        }

        const link_mark = node.marks.find((mark) => mark.type.name === "link");
        if (!link_mark) {
            return "";
        }

        return link_mark.attrs.href;
    },
});
