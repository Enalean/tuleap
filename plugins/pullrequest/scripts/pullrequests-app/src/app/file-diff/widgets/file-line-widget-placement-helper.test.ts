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

import type { Editor, LineHandle } from "codemirror";
import type { CommentWidgetCreationParams } from "../types-codemirror-overriden";
import { getWidgetPlacementOptions } from "./file-line-widget-placement-helper";
import { FileLineHandleStub } from "../../../../tests/stubs/FileLineHandleStub";
import { FileDiffWidgetStub } from "../../../../tests/stubs/FileDiffWidgetStub";

type EditorWithLineHandles = Editor & {
    getLineHandle: jest.SpyInstance;
};

const buildEditorWithLineHandle = (line_handle: LineHandle | null): EditorWithLineHandles =>
    ({
        getLineHandle: jest.fn().mockReturnValue(line_handle),
    }) as unknown as EditorWithLineHandles;

describe("file-line-widget-placement-helper", () => {
    describe("getWidgetPlacementOptions()", () => {
        it("should return an empty LineWidgetOption when there is no handle for the given line number", () => {
            const options = getWidgetPlacementOptions({
                code_mirror: buildEditorWithLineHandle(null),
                line_number: 15,
            } as unknown as CommentWidgetCreationParams);

            expect(options).toStrictEqual({});
        });

        it(`When the line handle associated to the given line number has no widgets
            Then it should return a LineWidgetOption with no insertAt property`, () => {
            const options = getWidgetPlacementOptions({
                code_mirror: buildEditorWithLineHandle(
                    FileLineHandleStub.buildLineHandleWithNoWidgets(),
                ),
                line_number: 15,
            } as unknown as CommentWidgetCreationParams);

            expect(options).toStrictEqual({
                coverGutter: true,
            });
        });

        it(`When the line handle associated to the given line number has comment widgets
            Then it should return a LineWidgetOption with no insertAt property
            So the widget will be placed below all the existing widgets in the line handle`, () => {
            const options = getWidgetPlacementOptions({
                code_mirror: buildEditorWithLineHandle(
                    FileLineHandleStub.buildLineHandleWithWidgets([
                        FileDiffWidgetStub.buildInlineCommentWidget(),
                        FileDiffWidgetStub.buildInlineCommentWidget(),
                    ]),
                ),
                line_number: 15,
            } as unknown as CommentWidgetCreationParams);

            expect(options).toStrictEqual({
                coverGutter: true,
            });
        });

        it(`When the line handle associated to the given line number has a code comment placeholder widget
            Then it should return a LineWidgetOption with no insertAt corresponding to the placeholder index
            So the new widget can be inserted right above the placeholder`, () => {
            const options = getWidgetPlacementOptions({
                code_mirror: buildEditorWithLineHandle(
                    FileLineHandleStub.buildLineHandleWithWidgets([
                        FileDiffWidgetStub.buildInlineCommentWidget(),
                        FileDiffWidgetStub.buildCodeCommentPlaceholder(),
                    ]),
                ),
                line_number: 15,
            } as unknown as CommentWidgetCreationParams);

            expect(options).toStrictEqual({
                coverGutter: true,
                insertAt: 1,
            });
        });
    });
});
