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
import { addComment } from "./comments-state.js";

export default CodeMirrorHelperService;

CodeMirrorHelperService.$inject = [
    "$rootScope",
    "$compile",
    "$document",
    "$timeout",
    "FileDiffRestService",
    "TooltipService",
    "gettextCatalog",
];

function CodeMirrorHelperService(
    $rootScope,
    $compile,
    $document,
    $timeout,
    FileDiffRestService,
    TooltipService,
    gettextCatalog
) {
    const self = this;
    Object.assign(self, {
        collapseCommonSectionsSideBySide,
        collapseCommonSectionsUnidiff,
        displayInlineComment,
        showCommentForm,
        displayPlaceholderWidget,
    });

    function displayInlineComment(code_mirror, comment, line_number) {
        const child_scope = $rootScope.$new(true);
        child_scope.comment = comment;
        const inline_comment_element = $compile(
            '<inline-comment comment="comment"></inline-comment>'
        )(child_scope)[0];

        const options = getWidgetPlacementOptions(code_mirror, line_number);

        // Wait for angular to actually render the component so that it has a height
        return $timeout(() => {
            return code_mirror.addLineWidget(line_number, inline_comment_element, options);
        });
    }

    function showCommentForm(
        code_mirror,
        unidiff_offset,
        display_line_number,
        file_path,
        pull_request_id,
        position
    ) {
        const child_scope = $rootScope.$new(true);
        child_scope.submitCallback = (comment_text) => {
            return postComment(unidiff_offset, comment_text, file_path, pull_request_id, position)
                .then((comment) => {
                    addComment(comment);
                    return self.displayInlineComment(code_mirror, comment, display_line_number);
                })
                .then(() => {
                    TooltipService.setupTooltips();
                });
        };
        const new_inline_comment_element = $compile(`
            <new-inline-comment submit-callback="submitCallback"
                                codemirror-widget="codemirror_widget"
            ></new-inline-comment>
        `)(child_scope)[0];

        const options = getWidgetPlacementOptions(code_mirror, display_line_number);
        // Wait for angular to actually render the component so that it has a height
        return $timeout(() => {
            const widget = code_mirror.addLineWidget(
                display_line_number,
                new_inline_comment_element,
                options
            );
            child_scope.codemirror_widget = widget;
            return widget;
        });
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
        const {
            code_mirror,
            handle,
            widget_height,
            display_above_line,
            is_comment_placeholder,
        } = widget_params;

        const options = {
            coverGutter: true,
            above: display_above_line,
        };
        const elem = $document[0].createElement("div");
        elem.classList.add("pull-request-file-diff-placeholder-block");

        if (is_comment_placeholder) {
            elem.classList.add("pull-request-file-diff-comment-placeholder-block");
        }

        elem.style.height = `${widget_height}px`;

        code_mirror.addLineWidget(handle, elem, options);
    }

    function postComment(unidiff_offset, comment_text, file_path, pull_request_id, position) {
        return FileDiffRestService.postInlineComment(
            pull_request_id,
            file_path,
            unidiff_offset,
            comment_text,
            position
        );
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
