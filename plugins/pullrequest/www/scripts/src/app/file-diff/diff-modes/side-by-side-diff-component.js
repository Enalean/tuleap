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

import CodeMirror from "codemirror";
import "codemirror/addon/scroll/simplescrollbars.js";
import { buildLineGroups, DELETED_GROUP, ADDED_GROUP } from "./side-by-side-data-builder.js";
import { synchronize } from "./side-by-side-scroll-synchronizer.js";

export default {
    template: `
        <div class="pull-request-side-by-side-diff" resize></div>
        <div class="pull-request-side-by-side-diff" resize></div>
    `,
    controller,
    bindings: {
        diff: "<",
        filePath: "@",
        pullRequestId: "@"
    }
};

controller.$inject = ["$element", "$scope", "$q", "CodeMirrorHelperService", "TooltipService"];

function controller($element, $scope, $q, CodeMirrorHelperService, TooltipService) {
    const self = this;
    self.$onInit = init;

    function init() {
        const [left_element, right_element] = $element[0].querySelectorAll(
            ".pull-request-side-by-side-diff"
        );
        const options = {
            readOnly: true,
            lineWrapping: true,
            gutters: ["gutter-lines"],
            mode: self.diff.mime_type,
            scrollbarStyle: "overlay"
        };

        const left_code_mirror = CodeMirror(left_element, options);
        const right_code_mirror = CodeMirror(right_element, options);
        $scope.$broadcast("code_mirror_initialized");

        const file_lines = self.diff.lines;
        displaySideBySideDiff(left_code_mirror, right_code_mirror, file_lines);

        synchronize(left_code_mirror, right_code_mirror);
    }

    function displaySideBySideDiff(left_code_mirror, right_code_mirror, file_lines) {
        const left_lines = file_lines.filter(line => line.old_offset !== null);
        const right_lines = file_lines.filter(line => line.new_offset !== null);

        const { lines_to_groups_map, first_line_to_group_map } = buildLineGroups(file_lines);

        const left_content = left_lines.map(({ content }) => content).join("\n");
        const right_content = right_lines.map(({ content }) => content).join("\n");

        left_code_mirror.setValue(left_content);
        right_code_mirror.setValue(right_content);

        const promises = self.diff.inline_comments.map(comment => {
            const comment_line = file_lines[comment.unidiff_offset - 1];
            return displayInlineCommentAndComputeItsHeight(
                comment,
                comment_line,
                lines_to_groups_map,
                left_code_mirror,
                right_code_mirror
            );
        });

        $q.all(promises).then(() => {
            TooltipService.setupTooltips();

            file_lines.forEach((line, line_number) => {
                const previous_line = file_lines[line_number - 1];
                displayLine(line, left_code_mirror, right_code_mirror);
                displayOppositePlaceholder(
                    line,
                    previous_line,
                    first_line_to_group_map,
                    left_code_mirror,
                    right_code_mirror
                );
            });
        });
    }

    function displayInlineCommentAndComputeItsHeight(
        comment,
        comment_line,
        lines_to_groups_map,
        left_code_mirror,
        right_code_mirror
    ) {
        if (comment_line.new_offset === null) {
            return CodeMirrorHelperService.displayInlineComment(
                left_code_mirror,
                comment,
                comment_line.old_offset - 1
            ).then(widget => {
                lines_to_groups_map.get(comment.unidiff_offset).height += widget.height;
            });
        }

        if (comment_line.old_offset === null) {
            return CodeMirrorHelperService.displayInlineComment(
                right_code_mirror,
                comment,
                comment_line.new_offset - 1
            ).then(widget => {
                lines_to_groups_map.get(comment.unidiff_offset).height += widget.height;
            });
        }

        // Arbitrarily show the comment on right side. As of today, We can't tell the backend
        // to store it on the left side.
        return CodeMirrorHelperService.displayInlineComment(
            right_code_mirror,
            comment,
            comment_line.new_offset - 1
        ).then(widget => {
            comment_line.height = initializeOrIncrementHeight(comment_line, widget);
        });
    }

    function initializeOrIncrementHeight(line, widget) {
        return typeof line.height === "undefined" ? widget.height : line.height + widget.height;
    }

    function displayOppositePlaceholder(
        line,
        previous_line,
        first_line_to_group_map,
        left_code_mirror,
        right_code_mirror
    ) {
        if (lineIsUnmoved(line) && line.height > 0) {
            const placeholder_line_number = line.old_offset - 1;
            CodeMirrorHelperService.displayPlaceholderWidget(
                left_code_mirror,
                placeholder_line_number,
                line.height
            );
            return;
        }

        if (!first_line_to_group_map.has(line.unidiff_offset)) {
            return;
        }

        const group = first_line_to_group_map.get(line.unidiff_offset);
        if (group.type === DELETED_GROUP) {
            const placeholder_line_number = previous_line ? previous_line.new_offset - 1 : 0;
            CodeMirrorHelperService.displayPlaceholderWidget(
                right_code_mirror,
                placeholder_line_number,
                group.height
            );
            return;
        }

        if (group.type === ADDED_GROUP) {
            const placeholder_line_number = previous_line ? previous_line.old_offset - 1 : 0;
            CodeMirrorHelperService.displayPlaceholderWidget(
                left_code_mirror,
                placeholder_line_number,
                group.height
            );
        }
    }

    function lineIsUnmoved(line) {
        return line.new_offset !== null && line.old_offset !== null;
    }

    function displayLine(line, left_code_mirror, right_code_mirror) {
        if (line.old_offset !== null) {
            left_code_mirror.setGutterMarker(
                line.old_offset - 1,
                "gutter-lines",
                document.createTextNode(line.old_offset)
            );

            if (line.new_offset === null) {
                left_code_mirror.addLineClass(
                    line.old_offset - 1,
                    "background",
                    "pull-request-file-diff-deleted-lines"
                );
            }
        }

        if (line.new_offset !== null) {
            right_code_mirror.setGutterMarker(
                line.new_offset - 1,
                "gutter-lines",
                document.createTextNode(line.new_offset)
            );

            if (line.old_offset === null) {
                right_code_mirror.addLineClass(
                    line.new_offset - 1,
                    "background",
                    "pull-request-file-diff-added-lines"
                );
            }
        }
    }
}
