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
import { getStore } from "../editors/comments-store.ts";
import { SideBySideLineState } from "../file-lines/SideBySideLineState.ts";
import { SideBySideCodePlaceholderCreationManager } from "../widgets/placeholders/SideBySideCodePlaceholderCreationManager.ts";
import { synchronize } from "../editors/side-by-side-scroll-synchronizer.ts";
import { getCollapsibleSectionsSideBySide } from "../code-collapse/collaspible-code-sections-builder.ts";
import { SideBySideLinesHeightEqualizer } from "../widgets/placeholders/SideBySideLinesHeightEqualizer.ts";
import { PullRequestCurrentUserPresenterBuilder } from "../../comments/PullRequestCurrentUserPresenterBuilder";
import { PullRequestPresenterBuilder } from "../../comments/PullRequestPresenterBuilder";

import {
    PullRequestCommentController,
    PullRequestCommentNewReplySaver,
} from "@tuleap/plugin-pullrequest-comments";
import {
    INLINE_COMMENT_POSITION_RIGHT,
    INLINE_COMMENT_POSITION_LEFT,
} from "@tuleap/plugin-pullrequest-constants";

import "../editors/modes.ts";
import { getCodeMirrorConfigurationToMakePotentiallyDangerousBidirectionalCharactersVisible } from "../editors/diff-bidirectional-unicode-text";
import { SideBySideLineGrouper } from "../file-lines/SideBySideLineGrouper";
import { SideBySideLineMapper } from "../file-lines/SideBySideLineMapper";
import { SideBySideCodeMirrorsContentManager } from "../editors/SideBySideCodeMirrorsContentManager";
import { SideBySidePlaceholderPositioner } from "../widgets/placeholders/SideBySidePlaceholderPositioner";
import { SideBySideCodeMirrorWidgetCreator } from "../widgets/SideBySideCodeMirrorWidgetCreator";
import { SideBySideCodeMirrorWidgetsCreationManager } from "../widgets/SideBySideCodeMirrorWidgetsCreationManager";
import { FileDiffCommentScroller } from "../scroll-to-comment/FileDiffCommentScroller";
import { FileDiffCommentWidgetsMap } from "../scroll-to-comment/FileDiffCommentWidgetsMap";
import { collapseCommonSectionsSideBySide } from "../code-collapse/code-mirror-common-sections-collapse";

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

controller.$inject = ["$element", "$scope", "SharedPropertiesService"];

function controller($element, $scope, SharedPropertiesService) {
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

        collapseCommonSectionsSideBySide(
            document,
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
            PullRequestCommentController(
                getStore(),
                PullRequestCommentNewReplySaver(),
                PullRequestCurrentUserPresenterBuilder.fromUserInfo(
                    SharedPropertiesService.getUserId(),
                    SharedPropertiesService.getUserAvatarUrl(),
                    SharedPropertiesService.getUserLocale(),
                    SharedPropertiesService.getDateTimeFormat(),
                    SharedPropertiesService.getRelativeDateDisplay()
                ),
                PullRequestPresenterBuilder.fromPullRequest(
                    SharedPropertiesService.getPullRequest()
                )
            ),
            getStore(),
            comment_widgets_map,
            SharedPropertiesService.isCommentsMarkdownModeEnabled()
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
                SharedPropertiesService.getProjectId(),
                SharedPropertiesService.getUserId(),
                SharedPropertiesService.getUserAvatarUrl(),
                self.filePath,
                line_number
            );
        });
        right_code_mirror.on("gutterClick", (right_code_mirror, line_number) => {
            widget_creation_manager.displayNewInlineCommentForm(
                INLINE_COMMENT_POSITION_RIGHT,
                self.pullRequestId,
                SharedPropertiesService.getProjectId(),
                SharedPropertiesService.getUserId(),
                SharedPropertiesService.getUserAvatarUrl(),
                self.filePath,
                line_number
            );
        });

        const comment_id = self.commentId ? Number.parseInt(self.commentId, 10) : null;

        FileDiffCommentScroller(
            getStore(),
            file_lines,
            comment_widgets_map
        ).scrollToSideBySideDiffComment(comment_id, left_code_mirror, right_code_mirror);
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
