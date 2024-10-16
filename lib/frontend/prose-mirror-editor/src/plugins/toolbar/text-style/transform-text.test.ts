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

import { describe, it, expect, beforeEach, vi } from "vitest";
import type { EditorState, Transaction } from "prosemirror-state";
import type { Attrs, NodeType } from "prosemirror-model";
import type { EditorNode } from "../../../types/internal-types";
import { custom_schema } from "../../../custom_schema";
import { getFormattedTextCommand, getHeadingCommand, getPlainTextCommand } from "./transform-text";

describe("transform-text", () => {
    let transaction_containing_new_block_type: Transaction,
        setBlockType: () => Transaction,
        dispatch: () => void;

    beforeEach(() => {
        transaction_containing_new_block_type = {} as Transaction;
        setBlockType = vi.fn().mockReturnValue(transaction_containing_new_block_type);
        dispatch = vi.fn();
    });

    const buildCurrentBlock = (type: NodeType, attributes: Attrs = {}): EditorNode =>
        ({
            type,
            attrs: attributes,
        }) as EditorNode;

    const buildState = (current_block: EditorNode): EditorState =>
        ({
            tr: {
                setBlockType,
            } as unknown as Transaction,
            selection: {
                $from: {
                    pos: 0,
                    parent: current_block,
                },
                $to: { pos: 20 },
            },
        }) as EditorState;

    describe("getHeadingCommand()", () => {
        it("Given that no dispatch function was provided to the Command, then it should return true", () => {
            const state = {} as EditorState;
            const command = getHeadingCommand(2);

            expect(command(state)).toBe(true);
        });

        it("Given that the current block type is a heading, but it has already the right level, then it should dispatch a transaction setting the block type to paragraph and return true", () => {
            const current_level = 2;
            const current_block = buildCurrentBlock(custom_schema.nodes.heading, {
                level: current_level,
            });
            const state = buildState(current_block);

            const command_result = getHeadingCommand(current_level)(state, dispatch);

            expect(setBlockType).toHaveBeenCalledWith(
                state.selection.$from.pos,
                state.selection.$to.pos,
                custom_schema.nodes.paragraph,
            );
            expect(dispatch).toHaveBeenCalledWith(transaction_containing_new_block_type);
            expect(command_result).toBe(true);
        });

        it(`Given that the current block type is not a heading
            Then it should dispatch a transaction setting the block type to heading with the requested level and return true`, () => {
            const current_block = buildCurrentBlock(custom_schema.nodes.paragraph);
            const state = buildState(current_block);

            const requested_heading_level = 5;
            const command_result = getHeadingCommand(requested_heading_level)(state, dispatch);

            expect(setBlockType).toHaveBeenCalledWith(
                state.selection.$from.pos,
                state.selection.$to.pos,
                custom_schema.nodes.heading,
                { level: requested_heading_level },
            );
            expect(dispatch).toHaveBeenCalledWith(transaction_containing_new_block_type);
            expect(command_result).toBe(true);
        });

        it(`Given that the current block type is a heading, and its level is different than the requested level,
            Then it should dispatch a transaction setting the block type to heading with the requested level and return true`, () => {
            const requested_heading_level = 1;
            const current_block = buildCurrentBlock(custom_schema.nodes.heading, {
                level: requested_heading_level + 1,
            });
            const state = buildState(current_block);

            const command_result = getHeadingCommand(requested_heading_level)(state, dispatch);

            expect(setBlockType).toHaveBeenCalledWith(
                state.selection.$from.pos,
                state.selection.$to.pos,
                custom_schema.nodes.heading,
                { level: requested_heading_level },
            );
            expect(dispatch).toHaveBeenCalledWith(transaction_containing_new_block_type);
            expect(command_result).toBe(true);
        });
    });

    describe("getPlainTextCommand()", () => {
        it("Given that no dispatch function was provided to the Command, then it should return true", () => {
            const state = {} as EditorState;
            const command = getPlainTextCommand();

            expect(command(state)).toBe(true);
        });

        it("should dispatch a transaction setting the block type to paragraph and return true", () => {
            const current_block = buildCurrentBlock(custom_schema.nodes.heading, { level: 1 });
            const state = buildState(current_block);

            const command_result = getPlainTextCommand()(state, dispatch);

            expect(setBlockType).toHaveBeenCalledWith(
                state.selection.$from.pos,
                state.selection.$to.pos,
                custom_schema.nodes.paragraph,
            );
            expect(dispatch).toHaveBeenCalledWith(transaction_containing_new_block_type);
            expect(command_result).toBe(true);
        });
    });

    describe("getFormattedTextCommand()", () => {
        it("Given that no dispatch function was provided to the Command, then it should return true", () => {
            const state = {} as EditorState;
            const command = getFormattedTextCommand();

            expect(command(state)).toBe(true);
        });

        it("should dispatch a transaction setting the block type to code_block and return true", () => {
            const current_block = buildCurrentBlock(custom_schema.nodes.heading, { level: 1 });
            const state = buildState(current_block);

            const command_result = getFormattedTextCommand()(state, dispatch);

            expect(setBlockType).toHaveBeenCalledWith(
                state.selection.$from.pos,
                state.selection.$to.pos,
                custom_schema.nodes.code_block,
            );
            expect(dispatch).toHaveBeenCalledWith(transaction_containing_new_block_type);
            expect(command_result).toBe(true);
        });
    });
});
