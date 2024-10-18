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

import { describe, it, expect } from "vitest";
import type { EditorState } from "prosemirror-state";
import { DetectListsInSelectionStub } from "./stubs/DetectListsInSelectionStub";
import { DetectSingleListInSelectionStub } from "./stubs/DetectSingleListInSelectionStub";
import type { DetectSingleListInSelection } from "./SingleListInSelectionDetector";
import type { DetectListsInSelection } from "./ListsInSelectionDetector";
import { ListState } from "./ListState";
import { ListStateBuilder } from "./ListStateBuilder";

describe("ListStateBuilder", () => {
    let target_list_detector: DetectSingleListInSelection, lists_detector: DetectListsInSelection;

    const getListState = (): ListState =>
        ListStateBuilder({} as EditorState, target_list_detector, lists_detector).build();

    it("When the selection contains only a list of the target type, then it should return an activated state", () => {
        target_list_detector = DetectSingleListInSelectionStub.withOnlyOneListOfTargetType();
        lists_detector = DetectListsInSelectionStub.withAtLeastOneList();

        expect(getListState()).toStrictEqual(ListState.activated());
    });

    it("When the selection contains multiple lists, then it should return a disabled state", () => {
        target_list_detector = DetectSingleListInSelectionStub.withNoList();
        lists_detector = DetectListsInSelectionStub.withAtLeastOneList();

        expect(getListState()).toStrictEqual(ListState.disabled());
    });

    it("Else, it should return an enabled state", () => {
        target_list_detector = DetectSingleListInSelectionStub.withNoList();
        lists_detector = DetectListsInSelectionStub.withoutAnyList();

        expect(getListState()).toStrictEqual(ListState.enabled());
    });
});
