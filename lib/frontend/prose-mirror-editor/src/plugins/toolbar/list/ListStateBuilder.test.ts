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
import { ListStateBuilder } from "./ListStateBuilder";
import { ListState } from "./ListState";
import { CheckIsSelectionAListWithTypeStub } from "./stubs/IsSelectionAListWithTypeChecker";
import { custom_schema } from "../../../custom_schema";

describe("ListStateBuilder", () => {
    it("When list type is authorised, then it should return the activate state", () => {
        const state = ListStateBuilder(
            {} as EditorState,
            CheckIsSelectionAListWithTypeStub.withSelectionWithListType(),
        ).build(custom_schema.nodes.ordered_list, custom_schema.nodes.bullet_list);

        expect(state).toStrictEqual(ListState.activated());
    });

    it("When node is not a list, then it should return the enable state", () => {
        const state = ListStateBuilder(
            {} as EditorState,
            CheckIsSelectionAListWithTypeStub.withForbiddenListType(),
        ).build(custom_schema.nodes.paragraph, custom_schema.nodes.bullet_list);

        expect(state).toStrictEqual(ListState.enabled());
    });
});
