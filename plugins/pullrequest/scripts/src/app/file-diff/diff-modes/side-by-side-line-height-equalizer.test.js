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

import { equalizeSides } from "./side-by-side-line-height-equalizer.js";

import * as side_by_side_widget_finder from "./side-by-side-widget-finder.js";

describe("line-height-equalizer", () => {
    let getCommentPlaceholderWidget;

    const left_codemirror = "left-codemirror";
    const right_codemirror = "right-codemirror";

    beforeEach(() => {
        getCommentPlaceholderWidget = jest.spyOn(
            side_by_side_widget_finder,
            "getCommentPlaceholderWidget"
        );
    });

    describe("equalizeSides", () => {
        it("Given a line with a new comment, when the opposite line has no comment or placeholder, then it should return some widget creation parameters for the opposite line with height equal to the new_comment widget height.", () => {
            const handles = {
                left_handle: {
                    widgets: [
                        {
                            height: 20,
                            node: {
                                localName: "new-inline-comment",
                                classList: { contains: () => false },
                            },
                        },
                    ],
                },
                right_handle: {},
            };

            const placeholder_to_create = equalizeSides(left_codemirror, right_codemirror, handles);

            expect(placeholder_to_create).toEqual({
                code_mirror: right_codemirror,
                handle: handles.right_handle,
                widget_height: 20,
                display_above_line: false,
                is_comment_placeholder: true,
            });
        });

        it("Given a line with 1 comment, 1 new comment, when the opposite has a placeholder, then it should adjust the opposite placeholder height.", () => {
            const changed = jest.fn();

            const placeholder = {
                height: 20,
                node: {
                    localName: "placeholder",
                    style: { height: "20px" },
                    className: "pull-request-file-diff-comment-placeholder-block",
                },
                changed,
            };

            const handles = {
                left_handle: {
                    widgets: [
                        { height: 25, node: { localName: "inline-comment", className: "" } },
                        { height: 20, node: { localName: "new-inline-comment", className: "" } },
                    ],
                },
                right_handle: {
                    widgets: [placeholder],
                },
            };

            getCommentPlaceholderWidget.mockReturnValueOnce(null).mockReturnValueOnce(placeholder);

            const placeholder_to_create = equalizeSides(left_codemirror, right_codemirror, handles);

            expect(placeholder_to_create).not.toBeDefined();
            expect(changed).toHaveBeenCalled();
            expect(placeholder.node.style.height).toEqual("45px");
        });

        it("Given a line with 2 comments, when the opposite has a placeholder and a new comment is added, then it should reduce the opposite placeholder height.", () => {
            const placeholder = {
                height: 45,
                node: {
                    localName: "placeholder",
                    style: { height: "45px" },
                    className: "pull-request-file-diff-comment-placeholder-block",
                },
                changed: () => {},
            };

            const handles = {
                left_handle: {
                    widgets: [
                        { height: 25, node: { localName: "inline-comment", className: "" } },
                        { height: 20, node: { localName: "inline-comment", className: "" } },
                    ],
                },
                right_handle: {
                    name: "right",
                    widgets: [
                        { height: 20, node: { localName: "new-inline-comment", className: "" } },
                        placeholder,
                    ],
                },
            };

            const opposite_placeholder = null;
            const current_placeholder = placeholder;

            getCommentPlaceholderWidget
                .mockReturnValueOnce(current_placeholder)
                .mockReturnValueOnce(opposite_placeholder);

            const placeholder_to_create = equalizeSides(left_codemirror, right_codemirror, handles);

            expect(placeholder_to_create).not.toBeDefined();
            expect(placeholder.node.style.height).toEqual("25px");
        });

        it("When the two sides have the same number of comments, then the placeholders should be minimized (height 0px).", () => {
            const placeholder = {
                height: 20,
                node: {
                    localName: "placeholder",
                    style: { height: "20px" },
                    className: "pull-request-file-diff-comment-placeholder-block",
                },
                changed: () => {},
            };

            const handles = {
                left_handle: {
                    widgets: [{ height: 20, node: { localName: "inline-comment", className: "" } }],
                },
                right_handle: {
                    widgets: [
                        placeholder,
                        { height: 20, node: { localName: "new-inline-comment", className: "" } },
                    ],
                },
            };
            const opposite_placeholder = null;
            const current_placeholder = placeholder;

            getCommentPlaceholderWidget
                .mockReturnValueOnce(current_placeholder)
                .mockReturnValueOnce(opposite_placeholder)
                .mockReturnValueOnce(current_placeholder)
                .mockReturnValueOnce(opposite_placeholder);

            const placeholder_to_create = equalizeSides(left_codemirror, right_codemirror, handles);

            expect(placeholder_to_create).not.toBeDefined();
            expect(placeholder.node.style.height).toEqual("0px");
        });
    });

    it("Given a line with a code placeholder (added/deleted line), when a new inline comment is added, a comment placeholder will be added and the code placeholder will remain untouched.", () => {
        const changed = jest.fn();

        const code_placeholder = {
            height: 20,
            node: {
                localName: "placeholder",
                style: { height: "20px" },
                className: "pull-request-file-diff-placeholder-block",
            },
            changed,
        };

        const handles = {
            left_handle: {
                widgets: [code_placeholder],
            },
            right_handle: {
                widgets: [{ height: 20, node: { localName: "new-inline-comment", className: "" } }],
            },
        };

        const opposite_placeholder = null;
        const current_placeholder = null;

        getCommentPlaceholderWidget
            .mockReturnValueOnce(current_placeholder)
            .mockReturnValueOnce(opposite_placeholder);

        const placeholder_to_create = equalizeSides(left_codemirror, right_codemirror, handles);

        expect(placeholder_to_create).toEqual({
            code_mirror: "left-codemirror",
            handle: handles.left_handle,
            widget_height: 20,
            display_above_line: false,
            is_comment_placeholder: true,
        });

        expect(changed).not.toHaveBeenCalled();
    });
});
