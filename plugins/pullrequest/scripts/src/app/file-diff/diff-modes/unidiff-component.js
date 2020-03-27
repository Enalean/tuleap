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
import { getComments } from "../comments-state.js";
import { getCollapsibleCodeSections } from "../../code-collapse/code-collapse-service.js";

import "./modes.js";
import { POSITION_LEFT, POSITION_RIGHT } from "../inline-comment-positions.js";

export default {
    template: `<div class="pull-request-unidiff" resize></div>`,
    controller,
    bindings: {
        diff: "<",
        filePath: "@",
        pullRequestId: "@",
    },
};

controller.$inject = [
    "$element",
    "$scope",
    "FileDiffRestService",
    "TooltipService",
    "CodeMirrorHelperService",
];

function controller(
    $element,
    $scope,
    FileDiffRestService,
    TooltipService,
    CodeMirrorHelperService
) {
    const self = this;

    const GUTTER_NEWLINES = "gutter-newlines";
    const GUTTER_OLDLINES = "gutter-oldlines";

    Object.assign(self, {
        $onInit: init,
    });

    function init() {
        const codemirror_area = $element[0].querySelector(".pull-request-unidiff");
        const unidiff_options = {
            readOnly: true,
            lineWrapping: true,
            gutters: [GUTTER_OLDLINES, GUTTER_NEWLINES],
            mode: self.diff.mime_type,
        };

        const unidiff_codemirror = CodeMirror(codemirror_area, unidiff_options);
        $scope.$broadcast("code_mirror_initialized");
        displayUnidiff(unidiff_codemirror, self.diff.lines);

        const collapsible_sections = getCollapsibleCodeSections(self.diff.lines, getComments());

        CodeMirrorHelperService.collapseCommonSectionsUnidiff(
            unidiff_codemirror,
            collapsible_sections
        );

        getComments().forEach((comment) => {
            CodeMirrorHelperService.displayInlineComment(
                unidiff_codemirror,
                comment,
                comment.unidiff_offset - 1
            );
        });

        unidiff_codemirror.on("gutterClick", addNewComment);

        TooltipService.setupTooltips();
    }

    function getCommentPosition(line_number, gutter) {
        const line = self.diff.lines[line_number];

        return gutter === GUTTER_OLDLINES && line.new_offset === null
            ? POSITION_LEFT
            : POSITION_RIGHT;
    }

    function addNewComment(code_mirror, line_number, gutter) {
        const comment_position = getCommentPosition(line_number, gutter);

        CodeMirrorHelperService.showCommentForm(
            code_mirror,
            Number(line_number) + 1,
            line_number,
            self.filePath,
            self.pullRequestId,
            comment_position
        );
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
