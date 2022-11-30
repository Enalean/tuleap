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
import { SideBySideCodePlaceholderCreationManager } from "./side-by-side-code-placeholder-creation-manager.ts";
import { synchronize } from "./side-by-side-scroll-synchronizer.ts";
import { getCollapsibleSectionsSideBySide } from "../../code-collapse/collaspible-code-sections-builder.ts";
import { SideBySideLinesHeightEqualizer } from "./side-by-side-line-height-equalizer.ts";

import { INLINE_COMMENT_POSITION_RIGHT, INLINE_COMMENT_POSITION_LEFT } from "../../comments/types";

import "./modes.ts";
import { getCodeMirrorConfigurationToMakePotentiallyDangerousBidirectionalCharactersVisible } from "../diff-bidirectional-unicode-text";
import { SideBySideLineGrouper } from "./side-by-side-line-grouper";
import { SideBySideLineMapper } from "./side-by-side-line-mapper";
import { SideBySideCodeMirrorsContentManager } from "./side-by-side-code-mirrors-content-manager";
import { SideBySidePlaceholderPositioner } from "./side-by-side-placeholder-positioner";
import { SideBySideCodeMirrorWidgetCreator } from "./side-by-side-code-mirror-widget-creator";
import { RelativeDateHelper } from "../../helpers/date-helpers";
import { PullRequestCurrentUserPresenter } from "../../comments/PullRequestCurrentUserPresenter";
import { PullRequestCommentController } from "../../comments/PullRequestCommentController";
import { PullRequestCommentReplyFormFocusHelper } from "../../comments/PullRequestCommentReplyFormFocusHelper";
import { PullRequestCommentNewReplySaver } from "../../comments/PullRequestCommentReplySaver";
import { PullRequestPresenter } from "../../comments/PullRequestPresenter";
import { SideBySideCodeMirrorWidgetsCreationManager } from "./side-by-side-code-mirror-widgets-creation-manager";
import { FileDiffCommentScroller } from "./file-diff-comment-scroller";
import { FileDiffCommentWidgetsMap } from "./file-diff-comment-widgets-map";

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
        commentId: "@",
    },
};

controller.$inject = [
    "$element",
    "$scope",
    "$q",
    "CodeMirrorHelperService",
    "SharedPropertiesService",
];

function controller($element, $scope, $q, CodeMirrorHelperService, SharedPropertiesService) {
    const self = this;

    Object.assign(self, {
        $onInit,
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
        const code_mirrors_content_manager = SideBySideCodeMirrorsContentManager(
            file_lines,
            left_code_mirror,
            right_code_mirror
        );

        const comment_widgets_map = FileDiffCommentWidgetsMap();
        const widget_creator = SideBySideCodeMirrorWidgetCreator(
            document,
            RelativeDateHelper(
                SharedPropertiesService.getDateTimeFormat(),
                SharedPropertiesService.getRelativeDateDisplay(),
                SharedPropertiesService.getUserLocale()
            ),
            PullRequestCommentController(
                PullRequestCommentReplyFormFocusHelper(),
                getStore(),
                PullRequestCommentNewReplySaver()
            ),
            getStore(),
            comment_widgets_map,
            PullRequestPresenter.fromPullRequest(SharedPropertiesService.getPullRequest()),
            PullRequestCurrentUserPresenter.fromUserInfo(
                SharedPropertiesService.getUserId(),
                SharedPropertiesService.getUserAvatarUrl()
            )
        );

        const file_lines_state = SideBySideLineState(
            file_lines,
            SideBySideLineGrouper(file_lines),
            SideBySideLineMapper(file_lines, left_code_mirror, right_code_mirror)
        );

        const code_placeholder_creation_manager = SideBySideCodePlaceholderCreationManager(
            code_mirrors_content_manager,
            file_lines_state,
            widget_creator
        );

        const widget_creation_manager = SideBySideCodeMirrorWidgetsCreationManager(
            file_lines_state,
            SideBySideLinesHeightEqualizer(
                left_code_mirror,
                right_code_mirror,
                SideBySidePlaceholderPositioner(file_lines_state)
            ),
            code_mirrors_content_manager,
            widget_creator,
            widget_creator,
            widget_creator
        );

        file_lines.forEach((line) => {
            displayLine(line, left_code_mirror, right_code_mirror);

            code_placeholder_creation_manager.displayCodePlaceholderIfNeeded(line);
        });

        getStore().getAllRootComments().forEach(widget_creation_manager.displayInlineComment);

        left_code_mirror.on("gutterClick", (left_code_mirror, line_number) => {
            widget_creation_manager.displayNewInlineCommentForm(
                INLINE_COMMENT_POSITION_LEFT,
                self.pullRequestId,
                self.filePath,
                line_number
            );
        });
        right_code_mirror.on("gutterClick", (right_code_mirror, line_number) => {
            widget_creation_manager.displayNewInlineCommentForm(
                INLINE_COMMENT_POSITION_RIGHT,
                self.pullRequestId,
                self.filePath,
                line_number
            );
        });

        FileDiffCommentScroller(
            getStore(),
            file_lines,
            comment_widgets_map
        ).scrollToSideBySideDiffComment(self.commentId, left_code_mirror, right_code_mirror);
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
