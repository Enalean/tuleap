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
import { getStore } from "./comments-store.ts";
import { RelativeDateHelper } from "../helpers/date-helpers";
import { PullRequestCommentController } from "../comments/PullRequestCommentController";
import { PullRequestCommentReplyFormFocusHelper } from "../comments/PullRequestCommentReplyFormFocusHelper";
import { PullRequestPresenter } from "../comments/PullRequestPresenter";
import { PullRequestCommentNewReplySaver } from "../comments/PullRequestCommentReplySaver";
import { PullRequestCurrentUserPresenter } from "../comments/PullRequestCurrentUserPresenter";
import { TAG_NAME as NEW_COMMENT_FORM_TAG_NAME } from "../comments/new-comment-form/NewInlineCommentForm";
import { TAG_NAME as COMMENT_TAG_NAME } from "../comments/PullRequestComment";
import { TAG_NAME as PLACEHOLDER_TAG_NAME } from "./FileDiffPlaceholder";
import { NewInlineCommentSaver } from "../comments/new-comment-form/NewInlineCommentSaver";

export default CodeMirrorHelperService;

CodeMirrorHelperService.$inject = ["gettextCatalog", "SharedPropertiesService"];

function CodeMirrorHelperService(gettextCatalog, SharedPropertiesService) {
    const self = this;
    Object.assign(self, {
        collapseCommonSectionsSideBySide,
        collapseCommonSectionsUnidiff,
        displayInlineComment,
        showCommentForm,
        displayPlaceholderWidget,
    });

    function displayInlineComment(code_mirror, comment, line_number) {
        const inline_comment_element = document.createElement(COMMENT_TAG_NAME);
        inline_comment_element.comment = comment;
        inline_comment_element.setAttribute("class", "inline-comment-element");
        inline_comment_element.relativeDateHelper = RelativeDateHelper(
            SharedPropertiesService.getDateTimeFormat(),
            SharedPropertiesService.getRelativeDateDisplay(),
            SharedPropertiesService.getUserLocale()
        );
        inline_comment_element.controller = PullRequestCommentController(
            PullRequestCommentReplyFormFocusHelper(),
            getStore(),
            PullRequestCommentNewReplySaver()
        );
        inline_comment_element.currentUser = PullRequestCurrentUserPresenter.fromUserInfo(
            SharedPropertiesService.getUserId(),
            SharedPropertiesService.getUserAvatarUrl()
        );
        inline_comment_element.currentPullRequest = PullRequestPresenter.fromPullRequest(
            SharedPropertiesService.getPullRequest()
        );

        const options = getWidgetPlacementOptions(code_mirror, line_number);

        return code_mirror.addLineWidget(line_number, inline_comment_element, options);
    }

    function showCommentForm(
        code_mirror,
        widget_line_number,
        new_inline_comment_context,
        post_rendering_callback = () => {}
    ) {
        const new_comment_element = document.createElement(NEW_COMMENT_FORM_TAG_NAME);

        const widget = code_mirror.addLineWidget(
            widget_line_number,
            new_comment_element,
            getWidgetPlacementOptions(code_mirror, widget_line_number)
        );

        new_comment_element.comment_saver = NewInlineCommentSaver(new_inline_comment_context);
        new_comment_element.post_rendering_callback = post_rendering_callback;
        new_comment_element.post_submit_callback = (comment_presenter) => {
            widget.clear();

            getStore().addRootComment(comment_presenter);
            const comment_widget = self.displayInlineComment(
                code_mirror,
                comment_presenter,
                widget_line_number
            );

            comment_widget.node.post_rendering_callback = post_rendering_callback;
        };

        new_comment_element.on_cancel_callback = () => {
            widget.clear();
            post_rendering_callback();
        };
    }

    function getWidgetPlacementOptions(code_mirror, display_line_number) {
        const options = {
            coverGutter: true,
        };
        const line_handle = code_mirror.getLineHandle(display_line_number);
        const placeholder_index = getPlaceholderWidgetIndex(line_handle);
        if (placeholder_index !== -1) {
            options.insertAt = placeholder_index;
        }
        return options;
    }

    function getPlaceholderWidgetIndex(handle) {
        if (!handle.widgets) {
            return -1;
        }
        return handle.widgets.findIndex((widget) => {
            return widget.node.classList.contains("pull-request-file-diff-placeholder-block");
        });
    }

    function displayPlaceholderWidget(widget_params) {
        const { code_mirror, handle, widget_height, display_above_line, is_comment_placeholder } =
            widget_params;

        const options = {
            coverGutter: true,
            above: display_above_line,
        };

        const placeholder = document.createElement(PLACEHOLDER_TAG_NAME);
        placeholder.height = widget_height;
        placeholder.isReplacingAComment = is_comment_placeholder;

        code_mirror.addLineWidget(handle, placeholder, options);
    }

    function getCollapsedLabelElement(section) {
        const collapsed_label = document.createElement("span");
        collapsed_label.className =
            "pull-request-file-diff-section-collapsed tlp-badge-primary tlp-badge-outline";

        collapsed_label.appendChild(
            document.createTextNode(
                gettextCatalog.getPlural(
                    section.end - section.start + 1,
                    "... Skipped 1 common line",
                    "... Skipped {{ $count }} common lines",
                    {}
                )
            )
        );
        return collapsed_label;
    }

    function collapseCommonSectionsUnidiff(unidiff_codemirror, sections) {
        sections.forEach((section) => appendCollapsedSectionLabel(unidiff_codemirror, section));
    }

    function appendCollapsedSectionLabel(codemirror, section) {
        let last_line_length = 0;

        if (codemirror.getLine(section.end)) {
            last_line_length = codemirror.getLine(section.end).length;
        }

        const collapsed_label = getCollapsedLabelElement(section);

        const marker = codemirror.markText(
            CodeMirror.Pos(section.start, 0),
            CodeMirror.Pos(section.end, last_line_length),
            {
                replacedWith: collapsed_label,
            }
        );

        collapsed_label.addEventListener("click", () => marker.clear());
    }

    function synchronizeExpandCollapsedSectionsSideBySide(left_codemirror, right_codemirror) {
        const left_labels = left_codemirror.getAllMarks();
        const right_labels = right_codemirror.getAllMarks();

        left_labels.forEach((label, index) => {
            label.replacedWith.addEventListener("click", () => right_labels[index].clear());
        });

        right_labels.forEach((label, index) => {
            label.replacedWith.addEventListener("click", () => left_labels[index].clear());
        });
    }

    function collapseCommonSectionsSideBySide(left_codemirror, right_codemirror, sections) {
        sections.forEach((section) => appendCollapsedSectionLabel(left_codemirror, section.left));

        sections.forEach((section) => appendCollapsedSectionLabel(right_codemirror, section.right));

        synchronizeExpandCollapsedSectionsSideBySide(left_codemirror, right_codemirror);
    }
}
