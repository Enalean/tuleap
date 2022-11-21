/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
import { getStore } from "../comments-store.ts";
import { SideBySideLineState } from "./side-by-side-lines-state.ts";
import { SideBySideCodePlaceholderBuilder } from "./side-by-side-code-placeholder-builder.ts";
import { SideBySideCommentPlaceholderBuilder } from "./side-by-side-comment-placeholder-builder.ts";
import { synchronize } from "./side-by-side-scroll-synchronizer.ts";
import { getCollapsibleSectionsSideBySide } from "../../code-collapse/collaspible-code-sections-builder.ts";
import { SideBySideLinesHeightEqualizer } from "./side-by-side-line-height-equalizer.ts";

import { INLINE_COMMENT_POSITION_RIGHT, INLINE_COMMENT_POSITION_LEFT } from "../../comments/types";

import "./modes.ts";
import { getCodeMirrorConfigurationToMakePotentiallyDangerousBidirectionalCharactersVisible } from "../diff-bidirectional-unicode-text";
import { SideBySideLineGrouper } from "./side-by-side-line-grouper";
import { isAnUnmovedLine } from "./file-line-helper";
import { SideBySideLineMapper } from "./side-by-side-line-mapper";
import { SideBySideCodeMirrorsContentManager } from "./side-by-side-code-mirrors-content-manager";
import { SideBySidePlaceholderPositioner } from "./side-by-side-placeholder-positioner";
import { NewInlineCommentContext } from "../../comments/new-comment-form/NewInlineCommentContext";

export default {
    template: `
        <div class="pull-request-side-by-side-diff" resize></div>
        <div class="pull-request-side-by-side-diff" resize></div>
    `,
    controller,
    bindings: {
        diff: "<",
        filePath: "@",
        pullRequestId: "@",
    },
};

controller.$inject = ["$element", "$scope", "$q", "CodeMirrorHelperService"];

function controller($element, $scope, $q, CodeMirrorHelperService) {
    const self = this;

    Object.assign(self, {
        $onInit,
        code_mirrors_content_manager: {},
        file_lines_state: {},
        comment_placeholder_builder: {},
        code_placeholder_builder: {},
        placeholder_positioner: {},
        lines_equalizer: {},
    });

    function $onInit() {
        const [left_element, right_element] = $element[0].querySelectorAll(
            ".pull-request-side-by-side-diff"
        );
        const options =
            getCodeMirrorConfigurationToMakePotentiallyDangerousBidirectionalCharactersVisible({
                readOnly: true,
                lineWrapping: true,
                gutters: ["gutter-lines"],
                mode: self.diff.mime_type,
                scrollbarStyle: "overlay",
                viewportMargin: 20,
            });

        const left_code_mirror = CodeMirror(left_element, options);
        const right_code_mirror = CodeMirror(right_element, options);
        $scope.$broadcast("code_mirror_initialized");

        const file_lines = self.diff.lines;
        displaySideBySideDiff(file_lines, left_code_mirror, right_code_mirror);

        synchronize(left_code_mirror, right_code_mirror);

        const collapsible_sections = getCollapsibleSectionsSideBySide(
            file_lines,
            getStore().getAllRootComments()
        );
        CodeMirrorHelperService.collapseCommonSectionsSideBySide(
            left_code_mirror,
            right_code_mirror,
            collapsible_sections
        );
    }

    function displaySideBySideDiff(file_lines, left_code_mirror, right_code_mirror) {
        self.code_mirrors_content_manager = SideBySideCodeMirrorsContentManager(
            file_lines,
            left_code_mirror,
            right_code_mirror
        );

        self.file_lines_state = SideBySideLineState(
            file_lines,
            SideBySideLineGrouper(file_lines),
            SideBySideLineMapper(file_lines, left_code_mirror, right_code_mirror)
        );

        self.placeholder_positioner = SideBySidePlaceholderPositioner(self.file_lines_state);
        self.comment_placeholder_builder = SideBySideCommentPlaceholderBuilder(
            left_code_mirror,
            right_code_mirror,
            self.file_lines_state,
            self.placeholder_positioner
        );

        self.code_placeholder_builder = SideBySideCodePlaceholderBuilder(
            left_code_mirror,
            right_code_mirror,
            self.file_lines_state
        );

        self.lines_equalizer = SideBySideLinesHeightEqualizer(
            left_code_mirror,
            right_code_mirror,
            self.placeholder_positioner
        );

        const code_placeholders = file_lines.map((line) => {
            displayLine(line, left_code_mirror, right_code_mirror);
            return addCodePlaceholder(line);
        });

        code_placeholders.forEach((widget_params) => {
            if (!widget_params) {
                return;
            }
            CodeMirrorHelperService.displayPlaceholderWidget(widget_params);
        });

        file_lines.forEach(addCommentsPlaceholder);

        getStore()
            .getAllRootComments()
            .forEach((comment) => {
                displayInlineComment(comment, left_code_mirror, right_code_mirror);
            });

        handleCodeMirrorEvents(left_code_mirror, right_code_mirror);
    }

    function displayInlineComment(comment, left_code_mirror, right_code_mirror) {
        const comment_line = self.file_lines_state.getCommentLine(comment);

        if (comment_line.new_offset === null) {
            CodeMirrorHelperService.displayInlineComment(
                left_code_mirror,
                comment,
                comment_line.old_offset - 1
            ).node.post_rendering_callback = () => {
                recomputeCommentPlaceholderHeight(
                    self.code_mirrors_content_manager.getLineInLeftCodeMirror(
                        comment_line.old_offset - 1
                    )
                );
            };

            return addCommentsPlaceholder(comment_line);
        }

        if (comment_line.old_offset === null) {
            CodeMirrorHelperService.displayInlineComment(
                right_code_mirror,
                comment,
                comment_line.new_offset - 1
            ).node.post_rendering_callback = () => {
                recomputeCommentPlaceholderHeight(
                    self.code_mirrors_content_manager.getLineInRightCodeMirror(
                        comment_line.new_offset - 1
                    )
                );
            };

            return addCommentsPlaceholder(comment_line);
        }

        const target_code_mirror =
            comment.position === INLINE_COMMENT_POSITION_LEFT
                ? left_code_mirror
                : right_code_mirror;
        const line_number =
            comment.position === INLINE_COMMENT_POSITION_LEFT
                ? comment_line.old_offset - 1
                : comment_line.new_offset - 1;

        CodeMirrorHelperService.displayInlineComment(
            target_code_mirror,
            comment,
            line_number
        ).node.post_rendering_callback = () => {
            recomputeCommentPlaceholderHeight(
                comment.position === INLINE_COMMENT_POSITION_LEFT
                    ? self.code_mirrors_content_manager.getLineInLeftCodeMirror(line_number)
                    : self.code_mirrors_content_manager.getLineInRightCodeMirror(line_number)
            );
        };

        return addCommentsPlaceholder(comment_line);
    }

    function recomputeCommentPlaceholderHeight(line) {
        const line_handles = self.file_lines_state.getLineHandles(line);
        if (!line_handles) {
            return;
        }

        const placeholder_to_create = self.lines_equalizer.equalizeSides(line_handles);
        if (placeholder_to_create) {
            CodeMirrorHelperService.displayPlaceholderWidget(placeholder_to_create);
        }
    }

    function handleCodeMirrorEvents(left_code_mirror, right_code_mirror) {
        const getLineNumberFromEvent = (gutter_click_event) =>
            // We need this trick because CodeMirror can sometimes provide the
            // wrong line number. Hence, we need to parse the content of the gutter
            // which holds the line number.
            Number.parseInt(gutter_click_event.target.textContent, 10) - 1;

        left_code_mirror.on("gutterClick", (left_code_mirror, line_number, gutter, event) => {
            addCommentOnLeftCodeMirror(left_code_mirror, getLineNumberFromEvent(event));
        });
        right_code_mirror.on("gutterClick", (right_code_mirror, line_number, gutter, event) => {
            addCommentOnRightCodeMirror(right_code_mirror, getLineNumberFromEvent(event));
        });
    }

    function addCommentOnLeftCodeMirror(left_code_mirror, line_number) {
        const line = self.code_mirrors_content_manager.getLineInLeftCodeMirror(line_number);
        if (!line) {
            return;
        }

        CodeMirrorHelperService.showCommentForm(
            left_code_mirror,
            line_number,
            NewInlineCommentContext.fromContext(
                self.pullRequestId,
                self.filePath,
                line.unidiff_offset,
                INLINE_COMMENT_POSITION_LEFT
            ),
            () => recomputeCommentPlaceholderHeight(line)
        );
    }

    function addCommentOnRightCodeMirror(right_code_mirror, line_number) {
        const line = self.code_mirrors_content_manager.getLineInRightCodeMirror(line_number);
        if (!line) {
            return;
        }

        CodeMirrorHelperService.showCommentForm(
            right_code_mirror,
            line_number,
            NewInlineCommentContext.fromContext(
                self.pullRequestId,
                self.filePath,
                line.unidiff_offset,
                INLINE_COMMENT_POSITION_RIGHT
            ),
            () => recomputeCommentPlaceholderHeight(line)
        );
    }

    function addCodePlaceholder(line) {
        if (isAnUnmovedLine(line) || !self.file_lines_state.isFirstLineOfGroup(line)) {
            return null;
        }
        return self.code_placeholder_builder.buildCodePlaceholderWidget(line);
    }

    function addCommentsPlaceholder(line) {
        if (!isAnUnmovedLine(line) && !self.file_lines_state.isFirstLineOfGroup(line)) {
            return;
        }
        const widget_params = self.comment_placeholder_builder.buildCommentsPlaceholderWidget(line);
        if (!widget_params) {
            return;
        }

        CodeMirrorHelperService.displayPlaceholderWidget(widget_params);
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
