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

import { ADDED_GROUP, DELETED_GROUP } from "./side-by-side-line-grouper.js";
import {
    getWidgetCreationParams,
    getUnmovedLineWidgetCreationParams
} from "./side-by-side-widget-builder.js";
import {
    rewire$getGroupLines,
    rewire$getLineHandles,
    rewire$getGroupOfLine,
    rewire$hasNextLine,
    rewire$getNextLine,
    restore as restoreLinesState
} from "./side-by-side-lines-state.js";

describe("side-by-side widget builder", () => {
    const left_code_mirror = {};
    const right_code_mirror = {};
    let getGroupLines, getLineHandles, getGroupOfLine, hasNextLine, getNextLine;

    beforeEach(() => {
        getGroupLines = jasmine.createSpy("getGroupLines");
        rewire$getGroupLines(getGroupLines);
        getLineHandles = jasmine.createSpy("getLineHandles");
        rewire$getLineHandles(getLineHandles);
        getGroupOfLine = jasmine.createSpy("getGroupOfLine");
        rewire$getGroupOfLine(getGroupOfLine);
        hasNextLine = jasmine.createSpy("hasNextLine");
        rewire$hasNextLine(hasNextLine);
        getNextLine = jasmine.createSpy("getNextLine");
        rewire$getNextLine(getNextLine);
    });

    afterEach(() => {
        restoreLinesState();
    });

    describe("getWidgetCreationParams()", () => {
        describe("Unmoved lines -", () => {
            it("Given an unmoved line with a comment (line widget), left and right code mirrors, then it will return the left code mirror (where the line widget will go), the left line handle where to attach it and the height difference between the left and right handles", () => {
                const unmoved_line = { unidiff_offset: 1, old_offset: 1, new_offset: 1 };
                const left_handle = { height: 20 };
                const fake_widget = {};
                const right_handle = { height: 57, widgets: [fake_widget] };
                getLineHandles
                    .withArgs(unmoved_line)
                    .and.returnValue({ left_handle, right_handle });

                const widget_params = getWidgetCreationParams(
                    unmoved_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params).toEqual({
                    code_mirror: left_code_mirror,
                    handle: left_handle,
                    widget_height: 37,
                    display_above_line: false
                });
            });

            it("Given an unmoved line without comment, then it will return null", () => {
                const unmoved_line = { unidiff_offset: 1, old_offset: 1, new_offset: 1 };
                const left_handle = { height: 40 };
                const right_handle = { height: 40 };
                getLineHandles
                    .withArgs(unmoved_line)
                    .and.returnValue({ left_handle, right_handle });

                const widget_params = getWidgetCreationParams(
                    unmoved_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params).toBeNull();
            });
        });

        describe("Deleted group -", () => {
            it("Given the first line of a deleted group, then it will return the right code mirror (where the line widget will go), the right line handle and the sum of the group's line handles' heights", () => {
                const first_deleted_line = { unidiff_offset: 2, old_offset: 2, new_offset: null };
                const second_deleted_line = { unidiff_offset: 3, old_offset: 3, new_offset: null };
                const first_right_handle = {};
                const first_left_handle = { height: 40 };
                const second_left_handle = { height: 20 };
                getLineHandles.withArgs(first_deleted_line).and.returnValue({
                    left_handle: first_left_handle,
                    right_handle: first_right_handle
                });
                getLineHandles
                    .withArgs(second_deleted_line)
                    .and.returnValue({ left_handle: second_left_handle });
                const group = {
                    unidiff_offsets: [2, 3],
                    type: DELETED_GROUP
                };
                getGroupOfLine.withArgs(first_deleted_line).and.returnValue(group);
                getGroupLines
                    .withArgs(group)
                    .and.returnValue([first_deleted_line, second_deleted_line]);

                const widget_params = getWidgetCreationParams(
                    first_deleted_line,
                    group,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params).toEqual({
                    code_mirror: right_code_mirror,
                    handle: first_right_handle,
                    widget_height: 60,
                    display_above_line: false
                });
            });

            it("Given the deleted group starts at the beginning of the file, then the height of the first line will be subtracted from the height of the widget because there is always a first line, even when it's empty", () => {
                const first_deleted_line = { unidiff_offset: 1, old_offset: 1, new_offset: null };
                const second_deleted_line = { unidiff_offset: 2, old_offset: 2, new_offset: null };
                const first_right_handle = { height: 20 };
                const first_left_handle = { height: 20 };
                const second_left_handle = { height: 57 };
                getLineHandles.withArgs(first_deleted_line).and.returnValue({
                    left_handle: first_left_handle,
                    right_handle: first_right_handle
                });
                getLineHandles
                    .withArgs(second_deleted_line)
                    .and.returnValue({ left_handle: second_left_handle });
                const group = {
                    unidiff_offsets: [1, 2],
                    type: DELETED_GROUP
                };
                getGroupOfLine.and.returnValue(group);
                getGroupLines
                    .withArgs(group)
                    .and.returnValue([first_deleted_line, second_deleted_line]);

                const widget_params = getWidgetCreationParams(
                    first_deleted_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params).toEqual({
                    code_mirror: right_code_mirror,
                    handle: first_right_handle,
                    widget_height: 57,
                    display_above_line: false
                });
            });
        });

        describe("Added group -", () => {
            it("Given the first line of an added group, then it will return the left code mirror (where the line widget will go), the left line handle and the sum of the group's line handles' heights", () => {
                const first_added_line = { unidiff_offset: 2, old_offset: null, new_offset: 2 };
                const second_added_line = { unidiff_offset: 3, old_offset: null, new_offset: 3 };
                const first_left_handle = {};
                const first_right_handle = { height: 20 };
                const second_right_handle = { height: 40 };
                getLineHandles.withArgs(first_added_line).and.returnValue({
                    left_handle: first_left_handle,
                    right_handle: first_right_handle
                });
                getLineHandles
                    .withArgs(second_added_line)
                    .and.returnValue({ right_handle: second_right_handle });
                const group = {
                    unidiff_offsets: [2, 3],
                    type: ADDED_GROUP
                };
                getGroupOfLine.withArgs(first_added_line).and.returnValue(group);
                getGroupLines
                    .withArgs(group)
                    .and.returnValue([first_added_line, second_added_line]);

                const widget_params = getWidgetCreationParams(
                    first_added_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params).toEqual({
                    code_mirror: left_code_mirror,
                    handle: first_left_handle,
                    widget_height: 60,
                    display_above_line: false
                });
            });

            it("Given the added group starts at the beginning of the file, then the height of the first line will be subtracted from the height of the widget because there is always a first line, even when it's empty", () => {
                const first_added_line = { unidiff_offset: 1, old_offset: null, new_offset: 1 };
                const second_added_line = { unidiff_offset: 2, old_offset: null, new_offset: 2 };
                const first_left_handle = { height: 20 };
                const first_right_handle = { height: 57 };
                const second_right_handle = { height: 20 };
                getLineHandles.withArgs(first_added_line).and.returnValue({
                    left_handle: first_left_handle,
                    right_handle: first_right_handle
                });
                getLineHandles
                    .withArgs(second_added_line)
                    .and.returnValue({ right_handle: second_right_handle });
                const group = {
                    unidiff_offsets: [2, 3],
                    type: ADDED_GROUP
                };
                getGroupOfLine.and.returnValue(group);
                getGroupLines
                    .withArgs(group)
                    .and.returnValue([first_added_line, second_added_line]);

                const widget_params = getWidgetCreationParams(
                    first_added_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params).toEqual({
                    code_mirror: left_code_mirror,
                    handle: first_left_handle,
                    widget_height: 57,
                    display_above_line: false
                });
            });
        });

        describe("First line modified -", () => {
            it("Given the first line is modified (it will appear as deleted and then added), then the opposite placeholder for the added group must be added above the line to keep the scrolling height", () => {
                const first_deleted_line = { unidiff_offset: 1, old_offset: 1, new_offset: null };
                const second_added_line = { unidiff_offset: 2, old_offset: null, new_offset: 1 };
                hasNextLine.withArgs(first_deleted_line).and.returnValue(true);
                getNextLine.withArgs(first_deleted_line).and.returnValue(second_added_line);
                const left_handle = { height: 40 };
                const right_handle = { height: 40 };
                getLineHandles.withArgs(first_deleted_line).and.returnValue({
                    left_handle,
                    right_handle
                });
                getLineHandles.withArgs(second_added_line).and.returnValue({
                    left_handle,
                    right_handle
                });
                const deleted_group = {
                    unidiff_offsets: [1],
                    type: DELETED_GROUP
                };
                const added_group = {
                    unidiff_offsets: [2],
                    type: ADDED_GROUP
                };
                getGroupOfLine.withArgs(first_deleted_line).and.returnValue(deleted_group);
                getGroupOfLine.withArgs(second_added_line).and.returnValue(added_group);
                getGroupLines.withArgs(deleted_group).and.returnValue([first_deleted_line]);
                getGroupLines.withArgs(added_group).and.returnValue([second_added_line]);

                const widget_params = getWidgetCreationParams(
                    first_deleted_line,
                    left_code_mirror,
                    right_code_mirror
                );

                expect(widget_params).toEqual({
                    code_mirror: right_code_mirror,
                    handle: right_handle,
                    widget_height: 40,
                    display_above_line: true
                });
            });
        });
    });

    describe("getUnmovedLineWidgetCreationParams()", () => {
        it("Given an unmoved line and given it had already a placeholder on the right side, then it will return the left handle to attach another placeholder and the height of the line minus that of the placeholder to keep the scroll in sync", () => {
            const unmoved_line = { unidiff_offset: 1, old_offset: 1, new_offset: 1 };
            const left_handle = { height: 20 };
            const classList = jasmine.createSpyObj("classList", ["contains"]);
            classList.contains
                .withArgs("pull-request-file-diff-placeholder-block")
                .and.returnValue(true);
            const placeholder_widget = {
                node: {
                    classList
                },
                height: 48
            };
            const right_handle = { height: 108, widgets: [placeholder_widget] };
            getLineHandles.and.returnValue({ left_handle, right_handle });

            const widget_params = getUnmovedLineWidgetCreationParams(unmoved_line);

            expect(widget_params).toEqual({
                handle: left_handle,
                widget_height: 40,
                display_above_line: false
            });
        });
    });
});
