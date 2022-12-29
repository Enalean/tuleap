/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type {
    AddedFileLine,
    GroupOfLines,
    RemovedFileLine,
    UnMovedFileLine,
} from "../../src/app/file-diff/types";
import { ADDED_GROUP, DELETED_GROUP, UNMOVED_GROUP } from "../../src/app/file-diff/types";

export const GroupOfLinesStub = {
    buildGroupOfRemovedLines: (lines: RemovedFileLine[]): GroupOfLines => ({
        type: DELETED_GROUP,
        unidiff_offsets: lines.map((line) => line.unidiff_offset),
    }),
    buildGroupOfAddedLines: (lines: AddedFileLine[]): GroupOfLines => ({
        type: ADDED_GROUP,
        unidiff_offsets: lines.map((line) => line.unidiff_offset),
    }),
    buildGroupOfUnMovedLines: (lines: UnMovedFileLine[]): GroupOfLines => ({
        type: UNMOVED_GROUP,
        unidiff_offsets: lines.map((line) => line.unidiff_offset),
    }),
};
