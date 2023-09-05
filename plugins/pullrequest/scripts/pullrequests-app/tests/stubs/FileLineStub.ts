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
    RemovedFileLine,
    UnMovedFileLine,
} from "../../src/app/file-diff/types";

export const FileLineStub = {
    buildAddedLine: (unidiff_offset: number, new_offset: number, content = ""): AddedFileLine => ({
        unidiff_offset,
        new_offset,
        old_offset: null,
        content,
    }),
    buildRemovedLine: (
        unidiff_offset: number,
        old_offset: number,
        content = "",
    ): RemovedFileLine => ({
        unidiff_offset,
        new_offset: null,
        old_offset,
        content,
    }),
    buildUnMovedFileLine: (
        unidiff_offset: number,
        new_offset: number,
        old_offset: number,
        content = "",
    ): UnMovedFileLine => ({
        unidiff_offset,
        new_offset,
        old_offset,
        content,
    }),
};
