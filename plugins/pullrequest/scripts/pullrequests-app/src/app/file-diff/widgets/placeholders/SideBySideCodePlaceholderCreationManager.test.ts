/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import type { Editor } from "codemirror";
import type { FileLine } from "../../types";
import type { StubCreatePlaceholderWidget } from "../../../../../tests/stubs/CreatePlaceholderWidgetStub";

import { FileLineStub } from "../../../../../tests/stubs/FileLineStub";
import { GroupOfLinesStub } from "../../../../../tests/stubs/GroupOfLinesStub";
import { FileLineHandleStub } from "../../../../../tests/stubs/FileLineHandleStub";
import { FileLinesStateStub } from "../../../../../tests/stubs/FileLinesStateStub";

import { SideBySideCodePlaceholderCreationManager } from "./SideBySideCodePlaceholderCreationManager";
import { SideBySideCodeMirrorsContentManager } from "../../editors/SideBySideCodeMirrorsContentManager";
import { CreatePlaceholderWidgetStub } from "../../../../../tests/stubs/CreatePlaceholderWidgetStub";

describe("side-by-side-code-placeholder-creation-manager", () => {
    const left_code_mirror: Editor = {
        setValue: () => {
            // Do nothing
        },
    } as unknown as Editor;
    const right_code_mirror: Editor = {
        setValue: () => {
            // Do nothing
        },
    } as unknown as Editor;
    const code_mirrors_content_manager = SideBySideCodeMirrorsContentManager(
        [],
        left_code_mirror,
        right_code_mirror,
    );
    let create_placeholder_stub: StubCreatePlaceholderWidget;

    beforeEach(() => {
        create_placeholder_stub = CreatePlaceholderWidgetStub();
    });

    describe("displayCodePlaceholderIfNeeded()", () => {
        describe("Deleted group -", () => {
            it("Given the first line of a deleted group, then it will return the right code mirror (where the line widget will go), the right line handle and the sum of the group's line handles' heights", () => {
                const second_line_deleted = FileLineStub.buildRemovedLine(2, 2);
                const third_line_deleted = FileLineStub.buildRemovedLine(3, 3);
                const second_right_handle = FileLineHandleStub.buildLineHandleWithNoWidgets();

                const code_placeholder_builder = SideBySideCodePlaceholderCreationManager(
                    code_mirrors_content_manager,
                    FileLinesStateStub(
                        [second_line_deleted, third_line_deleted],
                        [
                            GroupOfLinesStub.buildGroupOfRemovedLines([
                                second_line_deleted,
                                third_line_deleted,
                            ]),
                        ],
                        new Map([
                            [
                                second_line_deleted as FileLine,
                                {
                                    left_handle:
                                        FileLineHandleStub.buildLineHandleWithNoWidgets(40),
                                    right_handle: second_right_handle,
                                },
                            ],
                            [
                                third_line_deleted as FileLine,
                                {
                                    left_handle:
                                        FileLineHandleStub.buildLineHandleWithNoWidgets(20),
                                    right_handle: FileLineHandleStub.buildLineHandleWithNoWidgets(),
                                },
                            ],
                        ]),
                    ).getState(),
                    create_placeholder_stub.build(),
                );

                code_placeholder_builder.displayCodePlaceholderIfNeeded(second_line_deleted);

                expect(create_placeholder_stub.getNbCalls()).toBe(1);
                expect(create_placeholder_stub.getLastCreationParametersReceived()).toStrictEqual({
                    code_mirror: right_code_mirror,
                    handle: second_right_handle,
                    widget_height: 60,
                    display_above_line: true,
                    is_comment_placeholder: false,
                });
            });

            it("Given the deleted group starts at the beginning of the file, then the height of the first line will be subtracted from the height of the widget because there is always a first line, even when it's empty", () => {
                const first_line_deleted = FileLineStub.buildRemovedLine(1, 1);
                const second_line_deleted = FileLineStub.buildRemovedLine(2, 2);
                const first_right_handle = FileLineHandleStub.buildLineHandleWithNoWidgets(20);

                const code_placeholder_builder = SideBySideCodePlaceholderCreationManager(
                    code_mirrors_content_manager,
                    FileLinesStateStub(
                        [first_line_deleted, second_line_deleted],
                        [
                            GroupOfLinesStub.buildGroupOfRemovedLines([
                                first_line_deleted,
                                second_line_deleted,
                            ]),
                        ],
                        new Map([
                            [
                                first_line_deleted as FileLine,
                                {
                                    left_handle:
                                        FileLineHandleStub.buildLineHandleWithNoWidgets(20),
                                    right_handle:
                                        FileLineHandleStub.buildLineHandleWithNoWidgets(20),
                                },
                            ],
                            [
                                second_line_deleted as FileLine,
                                {
                                    left_handle:
                                        FileLineHandleStub.buildLineHandleWithNoWidgets(57),
                                    right_handle: FileLineHandleStub.buildLineHandleWithNoWidgets(),
                                },
                            ],
                        ]),
                    ).getState(),
                    create_placeholder_stub.build(),
                );

                code_placeholder_builder.displayCodePlaceholderIfNeeded(first_line_deleted);

                expect(create_placeholder_stub.getNbCalls()).toBe(1);
                expect(create_placeholder_stub.getLastCreationParametersReceived()).toStrictEqual({
                    code_mirror: right_code_mirror,
                    handle: first_right_handle,
                    widget_height: 57,
                    display_above_line: true,
                    is_comment_placeholder: false,
                });
            });
        });

        describe("Added group -", () => {
            it("Given the first line of an added group, then it will return the left code mirror (where the line widget will go), the left line handle and the sum of the group's line handles' heights", () => {
                const second_line_added = FileLineStub.buildAddedLine(2, 2);
                const third_line_added = FileLineStub.buildAddedLine(3, 3);
                const second_left_handle = FileLineHandleStub.buildLineHandleWithNoWidgets();

                const code_placeholder_builder = SideBySideCodePlaceholderCreationManager(
                    code_mirrors_content_manager,
                    FileLinesStateStub(
                        [second_line_added, third_line_added],
                        [
                            GroupOfLinesStub.buildGroupOfAddedLines([
                                second_line_added,
                                third_line_added,
                            ]),
                        ],
                        new Map([
                            [
                                second_line_added as FileLine,
                                {
                                    left_handle: second_left_handle,
                                    right_handle:
                                        FileLineHandleStub.buildLineHandleWithNoWidgets(20),
                                },
                            ],
                            [
                                third_line_added as FileLine,
                                {
                                    left_handle: FileLineHandleStub.buildLineHandleWithNoWidgets(),
                                    right_handle:
                                        FileLineHandleStub.buildLineHandleWithNoWidgets(40),
                                },
                            ],
                        ]),
                    ).getState(),
                    create_placeholder_stub.build(),
                );

                code_placeholder_builder.displayCodePlaceholderIfNeeded(second_line_added);

                expect(create_placeholder_stub.getNbCalls()).toBe(1);
                expect(create_placeholder_stub.getLastCreationParametersReceived()).toStrictEqual({
                    code_mirror: left_code_mirror,
                    handle: second_left_handle,
                    widget_height: 60,
                    display_above_line: false,
                    is_comment_placeholder: false,
                });
            });

            it("Given the added group starts at the beginning of the file, then the height of the first line will be subtracted from the height of the widget because there is always a first line, even when it's empty", () => {
                const first_line_added = FileLineStub.buildAddedLine(1, 1);
                const second_line_added = FileLineStub.buildAddedLine(2, 2);
                const first_left_handle = FileLineHandleStub.buildLineHandleWithNoWidgets(20);

                const code_placeholder_builder = SideBySideCodePlaceholderCreationManager(
                    code_mirrors_content_manager,
                    FileLinesStateStub(
                        [first_line_added, second_line_added],
                        [
                            GroupOfLinesStub.buildGroupOfAddedLines([
                                first_line_added,
                                second_line_added,
                            ]),
                        ],
                        new Map([
                            [
                                first_line_added as FileLine,
                                {
                                    left_handle: first_left_handle,
                                    right_handle:
                                        FileLineHandleStub.buildLineHandleWithNoWidgets(57),
                                },
                            ],
                            [
                                second_line_added as FileLine,
                                {
                                    left_handle: FileLineHandleStub.buildLineHandleWithNoWidgets(),
                                    right_handle:
                                        FileLineHandleStub.buildLineHandleWithNoWidgets(20),
                                },
                            ],
                        ]),
                    ).getState(),
                    create_placeholder_stub.build(),
                );

                code_placeholder_builder.displayCodePlaceholderIfNeeded(first_line_added);

                expect(create_placeholder_stub.getNbCalls()).toBe(1);
                expect(create_placeholder_stub.getLastCreationParametersReceived()).toStrictEqual({
                    code_mirror: left_code_mirror,
                    handle: first_left_handle,
                    widget_height: 57,
                    display_above_line: true,
                    is_comment_placeholder: false,
                });
            });
        });
    });
});
