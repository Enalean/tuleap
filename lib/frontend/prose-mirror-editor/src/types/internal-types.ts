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

/**
 * Reexport the prose-mirror Node type as EditorNode to avoid
 * the confusion with the DOM Node type.
 */
import type { Node } from "prosemirror-model";
export type EditorNode = Node;

export type LinkProperties = {
    readonly href: string;
    readonly title: string;
};

export type ImageProperties = {
    readonly src: string;
    readonly title: string;
};

export type Extents = {
    from: number;
    to: number;
};
