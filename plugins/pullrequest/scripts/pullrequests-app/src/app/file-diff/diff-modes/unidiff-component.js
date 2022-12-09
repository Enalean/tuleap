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
import { getStore } from "../comments-store.ts";
import { getCollapsibleCodeSections } from "../../code-collapse/collaspible-code-sections-builder.ts";

import "./modes.ts";
import { INLINE_COMMENT_POSITION_RIGHT, INLINE_COMMENT_POSITION_LEFT } from "../../comments/types";
import { getCodeMirrorConfigurationToMakePotentiallyDangerousBidirectionalCharactersVisible } from "../diff-bidirectional-unicode-text";
import { SideBySideCodeMirrorWidgetCreator } from "./side-by-side-code-mirror-widget-creator";
import { RelativeDateHelper } from "../../helpers/date-helpers";
import { PullRequestPresenter } from "../../comments/PullRequestPresenter";
import { PullRequestCurrentUserPresenter } from "../../comments/PullRequestCurrentUserPresenter";
import { NewInlineCommentContext } from "../../comments/new-comment-form/NewInlineCommentContext";
import { PullRequestCommentController } from "../../comments/PullRequestCommentController";
import { PullRequestCommentReplyFormFocusHelper } from "../../comments/PullRequestCommentReplyFormFocusHelper";
import { PullRequestCommentNewReplySaver } from "../../comments/PullRequestCommentReplySaver";
import { FileDiffCommentScroller } from "./file-diff-comment-scroller";
import { FileDiffCommentWidgetsMap } from "./file-diff-comment-widgets-map";

export default {
    template: `<div class="pull-request-unidiff" resize></div>`,
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
    "FileDiffRestService",
    "CodeMirrorHelperService",
    "SharedPropertiesService",
];

function controller(
    $element,
    $scope,
    FileDiffRestService,
    CodeMirrorHelperService,
    SharedPropertiesService
) {
    const self = this;

    const GUTTER_NEWLINES = "gutter-newlines";
    const GUTTER_OLDLINES = "gutter-oldlines";

    const comment_widgets_map = FileDiffCommentWidgetsMap();

    Object.assign(self, {
        $onInit,
        widget_creator: SideBySideCodeMirrorWidgetCreator(
            document,
            RelativeDateHelper(
                SharedPropertiesService.getDateTimeFormat(),
                SharedPropertiesService.getRelativeDateDisplay(),
                SharedPropertiesService.getUserLocale()
            ),
            PullRequestCommentController(
                PullRequestCommentReplyFormFocusHelper(),
                getStore(),
                PullRequestCommentNewReplySaver(),
                PullRequestCurrentUserPresenter.fromUserInfo(
                    SharedPropertiesService.getUserId(),
                    SharedPropertiesService.getUserAvatarUrl()
                ),
                PullRequestPresenter.fromPullRequest(SharedPropertiesService.getPullRequest())
            ),
            getStore(),
            comment_widgets_map
        ),
    });

    function $onInit() {
        const codemirror_area = $element[0].querySelector(".pull-request-unidiff");
        const unidiff_options =
            getCodeMirrorConfigurationToMakePotentiallyDangerousBidirectionalCharactersVisible({
                readOnly: true,
                lineWrapping: true,
                gutters: [GUTTER_OLDLINES, GUTTER_NEWLINES],
                mode: self.diff.mime_type,
            });

        const unidiff_codemirror = CodeMirror(codemirror_area, unidiff_options);
        $scope.$broadcast("code_mirror_initialized");
        displayUnidiff(unidiff_codemirror, self.diff.lines);

        const collapsible_sections = getCollapsibleCodeSections(
            self.diff.lines,
            getStore().getAllRootComments()
        );

        CodeMirrorHelperService.collapseCommonSectionsUnidiff(
            unidiff_codemirror,
            collapsible_sections
        );

        getStore()
            .getAllRootComments()
            .forEach((comment) => {
                self.widget_creator.displayInlineCommentWidget({
                    code_mirror: unidiff_codemirror,
                    comment,
                    line_number: comment.unidiff_offset - 1,
                    post_rendering_callback: () => {
                        // Do nothing
                    },
                });
            });

        unidiff_codemirror.on("gutterClick", addNewComment);

        FileDiffCommentScroller(
            getStore(),
            self.diff.lines,
            comment_widgets_map
        ).scrollToUnifiedDiffComment(self.commentId, unidiff_codemirror);
    }

    function getCommentPosition(line_number, gutter) {
        const line = self.diff.lines[line_number];

        return gutter === GUTTER_OLDLINES && line.new_offset === null
            ? INLINE_COMMENT_POSITION_LEFT
            : INLINE_COMMENT_POSITION_RIGHT;
    }

    function addNewComment(code_mirror, line_number, gutter_class, event) {
        if (event.target.classList.contains(gutter_class)) {
            return;
        }

        const comment_position = getCommentPosition(line_number, gutter_class);

        self.widget_creator.displayNewInlineCommentFormWidget({
            code_mirror,
            line_number,
            context: NewInlineCommentContext.fromContext(
                self.pullRequestId,
                self.filePath,
                Number(line_number) + 1,
                comment_position
            ),
            post_rendering_callback: () => {
                // Nothing to do
            },
        });
    }

    function displayUnidiff(unidiff_codemirror, file_lines) {
        let content = file_lines.map(({ content }) => content);
        content = content.join("\n");

        unidiff_codemirror.setValue(content);

        file_lines.forEach((line, line_number) => {
            if (line.old_offset) {
                unidiff_codemirror.setGutterMarker(
                    line_number,
                    GUTTER_OLDLINES,
                    document.createTextNode(line.old_offset)
                );
            } else {
                unidiff_codemirror.addLineClass(
                    line_number,
                    "background",
                    "pull-request-file-diff-added-lines"
                );
            }
            if (line.new_offset) {
                unidiff_codemirror.setGutterMarker(
                    line_number,
                    GUTTER_NEWLINES,
                    document.createTextNode(line.new_offset)
                );
            } else {
                unidiff_codemirror.addLineClass(
                    line_number,
                    "background",
                    "pull-request-file-diff-deleted-lines"
                );
            }
        });
    }
}
