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
import { ListNodeInserter } from "./ListInserter";
import { CheckIsSelectionAListStub } from "./stubs/IsSelectionAListChecker";
import { custom_schema } from "../../../custom_schema";

describe("ListNodeInserter", () => {
    it("WhenSelection is a list then it should lift the list", () => {
        const state = {} as unknown as EditorState;
        const lift_mock = vi.fn();
        const wrap_mock = vi.fn();
        const dispatch = vi.fn();
        ListNodeInserter(
            state,
            dispatch,
            CheckIsSelectionAListStub.withSelectionWithListType(),
            custom_schema.nodes.bullet_list,
            lift_mock,
            wrap_mock,
        ).insertList();

        expect(lift_mock).toHaveBeenCalledOnce();
        expect(wrap_mock).toHaveBeenCalledOnce();
    });

    it("WhenSelection is a list then it should wrap in List", () => {
        const state = {} as unknown as EditorState;
        const lift_mock = vi.fn();
        const wrap_mock = vi.fn();
        const dispatch = vi.fn();
        ListNodeInserter(
            state,
            dispatch,
            CheckIsSelectionAListStub.withForbiddenListType(),
            custom_schema.nodes.bullet_list,
            lift_mock,
            wrap_mock,
        ).insertList();

        expect(lift_mock).not.toHaveBeenCalledOnce();
        expect(wrap_mock).toHaveBeenCalledOnce();
    });
});
