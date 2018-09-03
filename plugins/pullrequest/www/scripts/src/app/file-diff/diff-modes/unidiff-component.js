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

export default {
    template: `<div class="pull-request-unidiff" resize></div>`,
    controller,
    bindings: {
        diff: "<",
        filePath: "@",
        pullRequestId: "@"
    }
};

controller.$inject = [
    "$element",
    "$scope",
    "FileDiffRestService",
    "TooltipService",
    "CodeMirrorHelperService",
    "CodeCollapseService"
];

function controller(
    $element,
    $scope,
    FileDiffRestService,
    TooltipService,
    CodeMirrorHelperService,
    CodeCollapseService
) {
    const self = this;
    Object.assign(self, {
        $onInit: init
    });

    function init() {
        const codemirror_area = $element[0].querySelector(".pull-request-unidiff");
        const unidiff_options = {
            readOnly: true,
            lineWrapping: true,
            gutters: ["gutter-oldlines", "gutter-newlines"],
            mode: self.diff.mime_type
        };

        const unidiff_codemirror = CodeMirror(codemirror_area, unidiff_options);
        $scope.$broadcast("code_mirror_initialized");
        displayUnidiff(unidiff_codemirror, self.diff.lines);

        const collapsible_sections = CodeCollapseService.getCollapsibleCodeSections(
            self.diff.lines,
            self.diff.inline_comments
        );

        CodeMirrorHelperService.collapseCommonSectionsUnidiff(
            unidiff_codemirror,
            collapsible_sections
        );

        self.diff.inline_comments.forEach(comment => {
            CodeMirrorHelperService.displayInlineComment(
                unidiff_codemirror,
                comment,
                comment.unidiff_offset - 1
            );
        });

        const gutterClick = (codemirror, line_number) => {
            CodeMirrorHelperService.showCommentForm(
                codemirror,
                line_number,
                self.filePath,
                self.pullRequestId
            );
        };
        unidiff_codemirror.on("gutterClick", gutterClick);

        TooltipService.setupTooltips();
    }

    function displayUnidiff(unidiff_codemirror, file_lines) {
        let content = file_lines.map(({ content }) => content);
        content = content.join("\n");

        unidiff_codemirror.setValue(content);

        file_lines.forEach((line, line_number) => {
            if (line.old_offset) {
                unidiff_codemirror.setGutterMarker(
                    line_number,
                    "gutter-oldlines",
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
                    "gutter-newlines",
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
