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

export default CodeMirrorHelperService;

CodeMirrorHelperService.$inject = [
    "$rootScope",
    "$compile",
    "FileDiffRestService",
    "TooltipService"
];

function CodeMirrorHelperService($rootScope, $compile, FileDiffRestService, TooltipService) {
    const self = this;
    Object.assign(self, {
        displayInlineComment,
        showCommentForm
    });

    function displayInlineComment(codemirror, comment) {
        const child_scope = $rootScope.$new(true);
        child_scope.comment = comment;
        const inline_comment_element = $compile(
            '<inline-comment comment="comment"></inline-comment>'
        )(child_scope)[0];
        codemirror.addLineWidget(comment.unidiff_offset - 1, inline_comment_element, {
            coverGutter: true
        });
    }

    function showCommentForm(codemirror, line_number, file_path, pull_request) {
        const child_scope = $rootScope.$new(true);
        child_scope.submitCallback = comment_text => {
            return postComment(line_number, comment_text, file_path, pull_request).then(comment => {
                self.displayInlineComment(codemirror, comment);
                TooltipService.setupTooltips();
            });
        };
        const new_inline_comment_element = $compile(`
            <new-inline-comment submit-callback="submitCallback"
                                codemirror-widget="codemirror_widget"
            ></new-inline-comment>
        `)(child_scope)[0];
        child_scope.codemirror_widget = codemirror.addLineWidget(
            line_number,
            new_inline_comment_element,
            {
                coverGutter: true
            }
        );
    }

    function postComment(line_number, comment_text, file_path, pull_request) {
        const unidiff_offset = Number(line_number) + 1;
        return FileDiffRestService.postInlineComment(
            pull_request,
            file_path,
            unidiff_offset,
            comment_text
        );
    }
}
