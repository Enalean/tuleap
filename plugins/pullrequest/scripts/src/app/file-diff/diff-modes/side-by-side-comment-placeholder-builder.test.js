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

import { ADDED_GROUP, DELETED_GROUP, UNMOVED_GROUP } from "./side-by-side-line-grouper.js";
import { NAME as INLINE_COMMENT_NAME } from "../inline-comment-component.js";
import { buildCommentsPlaceholderWidget } from "./side-by-side-comment-placeholder-builder.js";

import * as side_by_side_lines_state from "./side-by-side-lines-state.js";

describe("side-by-side widget builder", () => {
    const left_code_mirror = {};
    const right_code_mirror = {};
    let getGroupLines, getLineHandles, getGroupOfLine, getLineOfHandle;

    beforeEach(() => {
        getGroupLines = jest.spyOn(side_by_side_lines_state, "getGroupLines");
        getLineHandles = jest.spyOn(side_by_side_lines_state, "getLineHandles");
        getGroupOfLine = jest.spyOn(side_by_side_lines_state, "getGroupOfLine");
        getLineOfHandle = jest.spyOn(side_by_side_lines_state, "getLineOfHandle");
    });

    describe("buildCommentsPlaceholderWidget()", () => {
        describe("Given an unmoved line,", () => {
            const unmoved_line = { unidiff_offset: 1, old_offset: 1, new_offset: 1 };
            describe("when it has a comment on the right side", () => {
                const fake_widget = {
                    height: 20,
                    node: { localName: INLINE_COMMENT_NAME },
                };
                const left_handle = {};
                const right_handle = { widgets: [fake_widget] };
                beforeEach(() => {
                    getLineHandles.mockImplementation((value) => {
                        if (value === unmoved_line) {
                            return { left_handle, right_handle };
                        }
                        throw new Error(value);
                    });
                });

                it("then it will return the left code mirror and left handle (where the placeholder will go)", () => {
                    const widget_params = buildCommentsPlaceholderWidget(
                        unmoved_line,
                        left_code_mirror,
                        right_code_mirror
                    );

                    expect(widget_params.code_mirror).toBe(left_code_mirror);
                    expect(widget_params.handle).toBe(left_handle);
                    expect(widget_params.is_comment_placeholder).toBe(true);
                });

                it("then it computes the widget height as the sum of comments on the right side", () => {
                    const widget_params = buildCommentsPlaceholderWidget(
                        unmoved_line,
                        left_code_mirror,
                        right_code_mirror
                    );

                    expect(widget_params.widget_height).toEqual(20);
                });

                it("then it sets the widget below the line", () => {
                    const widget_params = buildCommentsPlaceholderWidget(
                        unmoved_line,
                        left_code_mirror,
                        right_code_mirror
                    );

                    expect(widget_params.display_above_line).toBe(false);
                });
            });

            describe("when it has a comment on the left side", () => {
                const fake_widget = { height: 66, node: { localName: INLINE_COMMENT_NAME } };
                const right_handle = {};
                const left_handle = { widgets: [fake_widget] };

                beforeEach(() => {
                    getLineHandles.mockImplementation((value) => {
                        if (value === unmoved_line) {
                            return { left_handle, right_handle };
                        }
                        throw new Error(value);
                    });
                    getLineOfHandle.mockImplementation((value) => {
                        if (value === left_handle) {
                            return unmoved_line;
                        }
                        throw new Error(value);
                    });
                    getLineOfHandle.mockImplementation((value) => {
                        if (value === unmoved_line) {
                            return { type: UNMOVED_GROUP };
                        }
                    });
                });

                it("then it will return the right code mirror and the right handle (where the placeholder will go)", () => {
                    const widget_params = buildCommentsPlaceholderWidget(
                        unmoved_line,
                        left_code_mirror,
                        right_code_mirror
                    );

                    expect(widget_params.code_mirror).toBe(right_code_mirror);
                    expect(widget_params.handle).toBe(right_handle);
                    expect(widget_params.is_comment_placeholder).toBe(true);
                });

                it("then it will compute the widget height as the sum of comments on the left side", () => {
                    const widget_params = buildCommentsPlaceholderWidget(
                        unmoved_line,
                        left_code_mirror,
                        right_code_mirror
                    );

                    expect(widget_params.widget_height).toEqual(66);
                });

                it("then it will set the widget below the line", () => {
                    const widget_params = buildCommentsPlaceholderWidget(
                        unmoved_line,
                        left_code_mirror,
                        right_code_mirror
                    );

                    expect(widget_params.display_above_line).toBe(false);
                });
            });

            it("When it has no comment, then widget parameters will be null", () => {
                const left_handle = {};
                const right_handle = {};
                getLineHandles.mockImplementation((value) => {
                    if (value === unmoved_line) {
                        return { left_handle, right_handle };
                    }
                });

                const widget_params = buildCommentsPlaceholderWidget(
                    unmoved_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params).toBeNull();
            });
        });

        describe("Given the first line of an added file and the group lines had comments", () => {
            const first_added_line = { unidiff_offset: 1, old_offset: null, new_offset: 1 };
            const second_added_line = { unidiff_offset: 2, old_offset: null, new_offset: 2 };
            const first_widget = {
                height: 48,
                node: { localName: INLINE_COMMENT_NAME },
            };
            const second_widget = {
                height: 95,
                node: { localName: INLINE_COMMENT_NAME },
            };
            const left_handle = {};
            const first_right_handle = { widgets: [first_widget] };
            const second_right_handle = { widgets: [second_widget] };
            const group = {
                unidiff_offsets: [1, 2],
                type: ADDED_GROUP,
            };

            beforeEach(() => {
                getLineOfHandle.mockImplementation((value) => {
                    if (value === left_handle) {
                        return null;
                    }
                    if (value === first_right_handle) {
                        return first_added_line;
                    }
                    throw new Error(value);
                });
                getLineHandles.mockImplementation((value) => {
                    if (value === first_added_line) {
                        return { left_handle, right_handle: first_right_handle };
                    }
                    if (value === second_added_line) {
                        return { left_handle, right_handle: second_right_handle };
                    }
                    throw new Error(value);
                });
                getGroupOfLine.mockImplementation((value) => {
                    if (value === first_added_line) {
                        return group;
                    }
                    throw new Error(value);
                });
                getGroupLines.mockImplementation((value) => {
                    if (group === value) {
                        return [first_added_line, second_added_line];
                    }
                    throw new Error(value);
                });
            });

            it("then it will return the left code mirror and the left handle (where the placeholder will go)", () => {
                const widget_params = buildCommentsPlaceholderWidget(
                    first_added_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params.code_mirror).toBe(left_code_mirror);
                expect(widget_params.handle).toBe(left_handle);
                expect(widget_params.is_comment_placeholder).toBe(true);
            });

            it("then it will compute the widget height as the sum of comments on the lines of the group", () => {
                const widget_params = buildCommentsPlaceholderWidget(
                    first_added_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params.widget_height).toEqual(143);
            });

            it("then it will set the widget below the line", () => {
                const widget_params = buildCommentsPlaceholderWidget(
                    first_added_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params.display_above_line).toBe(false);
            });
        });

        describe("Given the first line of a deleted file and the group lines had comments", () => {
            const first_deleted_line = { unidiff_offset: 55, old_offset: 55, new_offset: null };
            const second_deleted_line = { unidiff_offset: 56, old_offset: 56, new_offset: null };
            const first_widget = {
                height: 62,
                node: { localName: INLINE_COMMENT_NAME },
            };
            const second_widget = {
                height: 42,
                node: { localName: INLINE_COMMENT_NAME },
            };
            const first_left_handle = { widgets: [first_widget] };
            const second_left_handle = { widgets: [second_widget] };
            const right_handle = {};
            const group = {
                unidiff_offsets: [55, 56],
                type: DELETED_GROUP,
            };

            beforeEach(() => {
                getLineHandles.mockImplementation((value) => {
                    if (value === first_deleted_line) {
                        return { left_handle: first_left_handle, right_handle };
                    }
                    if (value === second_deleted_line) {
                        return { left_handle: second_left_handle, right_handle };
                    }
                    throw new Error(value);
                });
                getLineOfHandle.mockImplementation((value) => {
                    if (value === first_left_handle) {
                        return first_deleted_line;
                    }
                    if (value === right_handle) {
                        return null;
                    }
                    throw new Error(value);
                });
                getGroupOfLine.mockImplementation((value) => {
                    if (value === first_deleted_line) {
                        return group;
                    }
                });
                getGroupLines.mockImplementation((value) => {
                    if (value === group) {
                        return [first_deleted_line, second_deleted_line];
                    }
                    throw new Error(value);
                });
            });

            it("then it will return the right code mirror and the right handle (where the placeholder will go)", () => {
                const widget_params = buildCommentsPlaceholderWidget(
                    first_deleted_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params.code_mirror).toBe(right_code_mirror);
                expect(widget_params.handle).toBe(right_handle);
                expect(widget_params.is_comment_placeholder).toBe(true);
            });

            it("then it will compute the widget height as the sum of comments on the lines of the group", () => {
                const widget_params = buildCommentsPlaceholderWidget(
                    first_deleted_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params.widget_height).toEqual(104);
            });

            it("then it will set the widget above the line", () => {
                const widget_params = buildCommentsPlaceholderWidget(
                    first_deleted_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params.display_above_line).toBe(true);
            });
        });

        describe("Given a file with an added and a deleted groups, and there was a comment in the deleted group, when I am treating the first line of the deleted group", () => {
            const first_deleted_line = { unidiff_offset: 5, old_offset: 5, new_offset: null };
            const second_deleted_line = { unidiff_offset: 6, old_offset: 6, new_offset: null };
            const third_added_line = { unidiff_offset: 7, old_offset: null, new_offset: 7 };
            const fourth_added_line = { unidiff_offset: 8, old_offset: null, new_offset: 8 };
            const first_widget = {
                height: 89,
                node: { localName: INLINE_COMMENT_NAME },
            };
            const left_handle = {
                widgets: [first_widget],
            };
            const right_handle = {};
            let deleted_group, added_group;

            beforeEach(() => {
                deleted_group = {
                    type: DELETED_GROUP,
                };
                added_group = {
                    type: ADDED_GROUP,
                };
                getLineHandles.mockImplementation((value) => {
                    if (value === first_deleted_line) {
                        return { left_handle, right_handle };
                    }
                    return { left_handle: {}, right_handle: {} };
                });
                getLineOfHandle.mockImplementation((value) => {
                    if (value === left_handle) {
                        return first_deleted_line;
                    }
                    if (value === right_handle) {
                        return third_added_line;
                    }
                    throw new Error(value);
                });
                getGroupOfLine.mockImplementation((value) => {
                    if (value === first_deleted_line) {
                        return deleted_group;
                    }
                    if (value === third_added_line) {
                        return added_group;
                    }
                    throw new Error(value);
                });
                getGroupLines.mockImplementation((value) => {
                    if (value === deleted_group) {
                        return [first_deleted_line, second_deleted_line];
                    }
                    if (value === added_group) {
                        return [third_added_line, fourth_added_line];
                    }
                    throw new Error(value);
                });
            });

            it("then it will return the right code mirror and the right handle", () => {
                const widget_params = buildCommentsPlaceholderWidget(
                    first_deleted_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params.code_mirror).toBe(right_code_mirror);
                expect(widget_params.handle).toBe(right_handle);
                expect(widget_params.is_comment_placeholder).toBe(true);
            });

            it("then it will compute the widget height as the sum of comments on the lines of the group", () => {
                const widget_params = buildCommentsPlaceholderWidget(
                    first_deleted_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params.widget_height).toEqual(89);
            });

            it("then it will set the widget above the line", () => {
                const widget_params = buildCommentsPlaceholderWidget(
                    first_deleted_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params.display_above_line).toBe(true);
            });

            it("then it will mark both added and deleted groups as handled", () => {
                buildCommentsPlaceholderWidget(
                    first_deleted_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(deleted_group.has_initial_comment_placeholder).toBe(true);
                expect(added_group.has_initial_comment_placeholder).toBe(true);
            });
        });

        describe("Given the first line of a group that has already been handled", () => {
            const first_deleted_line = { unidiff_offset: 5, old_offset: 5, new_offset: null };
            const second_added_line = { unidiff_offset: 6, old_offset: null, new_offset: 5 };
            const left_handle = {};
            const right_handle = {};
            const deleted_group = {
                type: DELETED_GROUP,
                has_initial_comment_placeholder: true,
            };
            const added_group = {
                type: ADDED_GROUP,
                has_initial_comment_placeholder: true,
            };

            beforeEach(() => {
                getLineHandles.mockImplementation((value) => {
                    if (value === second_added_line) {
                        return { left_handle, right_handle };
                    }
                    throw new Error(value);
                });
                getLineOfHandle.mockImplementation((value) => {
                    if (value === left_handle) {
                        return first_deleted_line;
                    }
                    if (value === right_handle) {
                        return second_added_line;
                    }
                    throw new Error(value);
                });
                getGroupOfLine.mockImplementation((value) => {
                    if (value === first_deleted_line) {
                        return deleted_group;
                    }
                    if (value === second_added_line) {
                        return added_group;
                    }
                    throw new Error(value);
                });
            });

            it("then the group will be skipped and it will return null", () => {
                const widget_params = buildCommentsPlaceholderWidget(
                    second_added_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params).toBeNull();
            });
        });
    });
});
