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
import { getComments } from "../comments-state.js";
import {
    initDataAndCodeMirrors,
    isFirstLineOfGroup,
    getCommentLine,
    getLineHandles,
    getRightLine,
    getLeftLine
} from "./side-by-side-lines-state.js";
import {
    getWidgetCreationParams,
    getUnmovedLineWidgetCreationParams
} from "./side-by-side-widget-builder.js";
import { synchronize } from "./side-by-side-scroll-synchronizer.js";
import { getCollapsibleSectionsSideBySide } from "../../code-collapse/code-collapse-service.js";

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
        displaySideBySideDiff(file_lines, left_code_mirror, right_code_mirror);

        synchronize(left_code_mirror, right_code_mirror);

        const collapsible_sections = getCollapsibleSectionsSideBySide(file_lines, getComments());
        CodeMirrorHelperService.collapseCommonSectionsSideBySide(
            left_code_mirror,
            right_code_mirror,
            collapsible_sections
        );
    }

    function displaySideBySideDiff(file_lines, left_code_mirror, right_code_mirror) {
        initDataAndCodeMirrors(file_lines, left_code_mirror, right_code_mirror);

        const promises = getComments().map(comment => {
            return displayInlineComment(comment, left_code_mirror, right_code_mirror);
        });

        $q.all(promises).then(() => {
            TooltipService.setupTooltips();

            file_lines.forEach((line, line_number) => {
                displayLine(line, left_code_mirror, right_code_mirror);
                displayOppositePlaceholder(line, line_number, left_code_mirror, right_code_mirror);
            });

            handleCodeMirrorEvents(left_code_mirror, right_code_mirror);
        });
    }

    function displayInlineComment(comment, left_code_mirror, right_code_mirror) {
        const comment_line = getCommentLine(comment);
        if (comment_line.new_offset === null) {
            return CodeMirrorHelperService.displayInlineComment(
                left_code_mirror,
                comment,
                comment_line.old_offset - 1
            );
        }

        if (comment_line.old_offset === null) {
            return CodeMirrorHelperService.displayInlineComment(
                right_code_mirror,
                comment,
                comment_line.new_offset - 1
            );
        }

        // Arbitrarily show the comment on right side. As of today, We can't tell the backend
        // to store it on the left side.
        return CodeMirrorHelperService.displayInlineComment(
            right_code_mirror,
            comment,
            comment_line.new_offset - 1
        );
    }

    function handleCodeMirrorEvents(left_code_mirror, right_code_mirror) {
        left_code_mirror.on("lineWidgetAdded", (code_mirror, line_widget, line_number) => {
            const placeholder = getOppositePlaceholderWidgetForLeft(line_number);
            addHeightToOppositePlaceholder(placeholder, line_widget.height);
        });
        right_code_mirror.on("lineWidgetAdded", (code_mirror, line_widget, line_number) => {
            const line = getRightLine(line_number);
            const { left_handle } = getLineHandles(line);
            if (!left_handle.widgets) {
                const widget_params = getUnmovedLineWidgetCreationParams(line);
                widget_params.code_mirror = left_code_mirror;
                CodeMirrorHelperService.displayPlaceholderWidget(widget_params);
                return;
            }
            const placeholder = left_handle.widgets[0];
            addHeightToOppositePlaceholder(placeholder, line_widget.height);
        });
        left_code_mirror.on("lineWidgetCleared", (code_mirror, line_widget, line_number) => {
            const placeholder = getOppositePlaceholderWidgetForLeft(line_number);
            subtractHeightToOppositePlaceholder(placeholder, line_widget.height);
        });
        right_code_mirror.on("lineWidgetCleared", (code_mirror, line_widget, line_number) => {
            const placeholder = getOppositePlaceholderWidgetForRight(line_number);
            if (placeholder) {
                subtractHeightToOppositePlaceholder(placeholder, line_widget.height);
            }
        });

        left_code_mirror.on("gutterClick", addCommentOnLeftCodeMirror);
        right_code_mirror.on("gutterClick", addCommentOnRightCodeMirror);
    }

    function getOppositePlaceholderWidgetForLeft(line_number) {
        const line = getLeftLine(line_number);
        const { right_handle } = getLineHandles(line);
        // Since we cannot add comments on unmoved lines on the left,
        // the opposite line should always have a placeholder
        return right_handle.widgets[0];
    }

    function getOppositePlaceholderWidgetForRight(line_number) {
        const line = getRightLine(line_number);
        const { left_handle } = getLineHandles(line);
        if (!left_handle.widgets) {
            return;
        }

        return left_handle.widgets[0];
    }

    function addHeightToOppositePlaceholder(placeholder, widget_height) {
        adjustOppositePlaceholderHeight(placeholder, placeholder.height + widget_height);
    }

    function subtractHeightToOppositePlaceholder(placeholder, widget_height) {
        adjustOppositePlaceholderHeight(placeholder, placeholder.height - widget_height);
    }

    function adjustOppositePlaceholderHeight(placeholder, widget_height) {
        placeholder.node.style = `height: ${widget_height}px`;
        placeholder.changed();
    }

    function addCommentOnLeftCodeMirror(left_code_mirror, line_number) {
        const line = getLeftLine(line_number);
        if (!line || lineIsUnmoved(line)) {
            // As of today, We can't tell the backend to store comments on the
            // left side. So, we don't allow it.
            return;
        }

        CodeMirrorHelperService.showCommentForm(
            left_code_mirror,
            line.unidiff_offset,
            line_number,
            self.filePath,
            self.pullRequestId
        );
    }

    function addCommentOnRightCodeMirror(right_code_mirror, line_number) {
        const line = getRightLine(line_number);
        if (!line) {
            return;
        }

        CodeMirrorHelperService.showCommentForm(
            right_code_mirror,
            line.unidiff_offset,
            line_number,
            self.filePath,
            self.pullRequestId
        );
    }

    function displayOppositePlaceholder(line, line_number, left_code_mirror, right_code_mirror) {
        if (!lineIsUnmoved(line) && !isFirstLineOfGroup(line)) {
            return;
        }
        const widget_params = getWidgetCreationParams(line, left_code_mirror, right_code_mirror);
        if (!widget_params) {
            return;
        }

        CodeMirrorHelperService.displayPlaceholderWidget(widget_params);
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
                left_code_mirror.addLineClass(
                    line.old_offset - 1,
                    "gutter",
                    "pull-request-side-by-side-diff-can-comment"
                );
            }
        }

        if (line.new_offset !== null) {
            right_code_mirror.setGutterMarker(
                line.new_offset - 1,
                "gutter-lines",
                document.createTextNode(line.new_offset)
            );
            right_code_mirror.addLineClass(
                line.new_offset - 1,
                "gutter",
                "pull-request-side-by-side-diff-can-comment"
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
