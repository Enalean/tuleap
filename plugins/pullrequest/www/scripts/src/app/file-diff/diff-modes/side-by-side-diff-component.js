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

controller.$inject = ["$element", "$scope"];

function controller($element, $scope) {
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

        displaySideBySideDiff(left_code_mirror, right_code_mirror, self.diff.lines);
    }

    function displaySideBySideDiff(left_code_mirror, right_code_mirror, file_lines) {
        const left_lines = file_lines.filter(line => line.old_offset !== null);
        const right_lines = file_lines.filter(line => line.new_offset !== null);

        const left_content = left_lines.map(({ content }) => content).join("\n");
        const right_content = right_lines.map(({ content }) => content).join("\n");

        left_code_mirror.setValue(left_content);
        right_code_mirror.setValue(right_content);

        file_lines.forEach((line, line_number) => {
            displayLine(line, line_number, left_code_mirror, right_code_mirror);
        });
    }

    function displayLine(line, line_number, left_code_mirror, right_code_mirror) {
        if (line.old_offset !== null) {
            left_code_mirror.setGutterMarker(
                line.old_offset - 1,
                "gutter-lines",
                document.createTextNode(line.old_offset)
            );

            if (line.new_offset === null) {
                left_code_mirror.addLineClass(
                    line_number,
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
                    line_number,
                    "background",
                    "pull-request-file-diff-added-lines"
                );
            }
        }
    }
}
