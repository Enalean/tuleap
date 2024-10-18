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

import { describe, it, expect, vi } from "vitest";
import type { EditorState } from "prosemirror-state";
import { DetectSingleListInSelectionStub } from "./stubs/DetectSingleListInSelectionStub";
import { ListNodeInserter } from "./ListInserter";

describe("ListNodeInserter", () => {
    it("When the selection already contains a list of the target type, then it should lift its items", () => {
        const state = {} as unknown as EditorState;
        const lift_mock = vi.fn();
        const wrap_mock = vi.fn();
        const dispatch = vi.fn();

        ListNodeInserter(
            state,
            dispatch,
            DetectSingleListInSelectionStub.withOnlyOneListOfTargetType(),
            lift_mock,
            wrap_mock,
        ).insertList();

        expect(lift_mock).toHaveBeenCalledOnce();
        expect(wrap_mock).not.toHaveBeenCalled();
    });

    it("When the selection does not contain a list yet, then it should wrap its content in a list", () => {
        const state = {} as unknown as EditorState;
        const lift_mock = vi.fn();
        const wrap_mock = vi.fn();
        const dispatch = vi.fn();

        ListNodeInserter(
            state,
            dispatch,
            DetectSingleListInSelectionStub.withNoList(),
            lift_mock,
            wrap_mock,
        ).insertList();

        expect(lift_mock).not.toHaveBeenCalled();
        expect(wrap_mock).toHaveBeenCalledOnce();
    });
});
