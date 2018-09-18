/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

import {
    rewire$getGroupLines,
    rewire$getLineHandles,
    rewire$getGroupOfLine,
    rewire$getLineOfHandle,
    restore as restoreLinesState
} from "./side-by-side-lines-state.js";

describe("side-by-side widget builder", () => {
    const left_code_mirror = {};
    const right_code_mirror = {};
    let getGroupLines, getLineHandles, getGroupOfLine, getLineOfHandle;

    beforeEach(() => {
        getGroupLines = jasmine.createSpy("getGroupLines");
        rewire$getGroupLines(getGroupLines);
        getLineHandles = jasmine.createSpy("getLineHandles");
        rewire$getLineHandles(getLineHandles);
        getGroupOfLine = jasmine.createSpy("getGroupOfLine");
        rewire$getGroupOfLine(getGroupOfLine);
        getLineOfHandle = jasmine.createSpy("getLineOfHandle");
        rewire$getLineOfHandle(getLineOfHandle);
    });

    afterEach(() => {
        restoreLinesState();
    });

    describe("buildCommentsPlaceholderWidget()", () => {
        describe("Given an unmoved line,", () => {
            const unmoved_line = { unidiff_offset: 1, old_offset: 1, new_offset: 1 };
            describe("when it has a comment on the right side", () => {
                const fake_widget = {
                    height: 20,
                    node: { localName: INLINE_COMMENT_NAME }
                };
                const left_handle = {};
                const right_handle = { widgets: [fake_widget] };
                beforeEach(() => {
                    getLineHandles
                        .withArgs(unmoved_line)
                        .and.returnValue({ left_handle, right_handle });
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
                    getLineHandles
                        .withArgs(unmoved_line)
                        .and.returnValue({ left_handle, right_handle });
                    getLineOfHandle.withArgs(left_handle).and.returnValue(unmoved_line);
                    getGroupOfLine.withArgs(unmoved_line).and.returnValue({ type: UNMOVED_GROUP });
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
                getLineHandles
                    .withArgs(unmoved_line)
                    .and.returnValue({ left_handle, right_handle });

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
                node: { localName: INLINE_COMMENT_NAME }
            };
            const second_widget = {
                height: 95,
                node: { localName: INLINE_COMMENT_NAME }
            };
            const left_handle = {};
            const first_right_handle = { widgets: [first_widget] };
            const second_right_handle = { widgets: [second_widget] };
            const group = {
                unidiff_offsets: [1, 2],
                type: ADDED_GROUP
            };

            beforeEach(() => {
                getLineOfHandle.withArgs(left_handle).and.returnValue(null);
                getLineOfHandle.withArgs(first_right_handle).and.returnValue(first_added_line);
                getLineHandles
                    .withArgs(first_added_line)
                    .and.returnValue({ left_handle, right_handle: first_right_handle });
                getLineHandles
                    .withArgs(second_added_line)
                    .and.returnValue({ left_handle, right_handle: second_right_handle });

                getGroupOfLine.withArgs(first_added_line).and.returnValue(group);
                getGroupLines
                    .withArgs(group)
                    .and.returnValue([first_added_line, second_added_line]);
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
                node: { localName: INLINE_COMMENT_NAME }
            };
            const second_widget = {
                height: 42,
                node: { localName: INLINE_COMMENT_NAME }
            };
            const first_left_handle = { widgets: [first_widget] };
            const second_left_handle = { widgets: [second_widget] };
            const right_handle = {};
            const group = {
                unidiff_offsets: [55, 56],
                type: DELETED_GROUP
            };

            beforeEach(() => {
                getLineHandles
                    .withArgs(first_deleted_line)
                    .and.returnValue({ left_handle: first_left_handle, right_handle });
                getLineHandles
                    .withArgs(second_deleted_line)
                    .and.returnValue({ left_handle: second_left_handle, right_handle });
                getLineOfHandle.withArgs(first_left_handle).and.returnValue(first_deleted_line);
                getLineOfHandle.withArgs(right_handle).and.returnValue(null);
                getGroupOfLine.withArgs(first_deleted_line).and.returnValue(group);
                getGroupLines
                    .withArgs(group)
                    .and.returnValue([first_deleted_line, second_deleted_line]);
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
                node: { localName: INLINE_COMMENT_NAME }
            };
            const left_handle = {
                widgets: [first_widget]
            };
            const right_handle = {};
            let deleted_group, added_group;

            beforeEach(() => {
                deleted_group = {
                    type: DELETED_GROUP
                };
                added_group = {
                    type: ADDED_GROUP
                };
                getLineHandles.and.returnValue({ left_handle: {}, right_handle: {} });
                getLineHandles
                    .withArgs(first_deleted_line)
                    .and.returnValue({ left_handle, right_handle });
                getLineOfHandle.withArgs(left_handle).and.returnValue(first_deleted_line);
                getLineOfHandle.withArgs(right_handle).and.returnValue(third_added_line);
                getGroupOfLine.withArgs(first_deleted_line).and.returnValue(deleted_group);
                getGroupOfLine.withArgs(third_added_line).and.returnValue(added_group);
                getGroupLines
                    .withArgs(deleted_group)
                    .and.returnValue([first_deleted_line, second_deleted_line]);
                getGroupLines
                    .withArgs(added_group)
                    .and.returnValue([third_added_line, fourth_added_line]);
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
                has_initial_comment_placeholder: true
            };
            const added_group = {
                type: ADDED_GROUP,
                has_initial_comment_placeholder: true
            };

            beforeEach(() => {
                getLineHandles
                    .withArgs(second_added_line)
                    .and.returnValue({ left_handle, right_handle });
                getLineOfHandle.withArgs(left_handle).and.returnValue(first_deleted_line);
                getLineOfHandle.withArgs(right_handle).and.returnValue(second_added_line);
                getGroupOfLine.withArgs(first_deleted_line).and.returnValue(deleted_group);
                getGroupOfLine.withArgs(second_added_line).and.returnValue(added_group);
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
