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

import "./file-diff.tpl.html";
import { isUnifiedMode, isSideBySideMode } from "./diff-mode-state.js";
import { initComments } from "./comments-state.js";
import { doesChangedCodeContainsPotentiallyDangerousBidirectionalUnicodeText } from "./diff-bidirectional-unicode-text";
import { PullRequestCommentPresenterBuilder } from "../comments/pullrequest-comment-presenter-builder";

export default {
    templateUrl: "file-diff.tpl.html",
    controller,
};

controller.$inject = ["$state", "SharedPropertiesService", "FileDiffRestService"];

function controller($state, SharedPropertiesService, FileDiffRestService) {
    const self = this;
    Object.assign(self, {
        is_loading: true,
        is_binary_file: false,
        special_format: "",
        has_potentially_dangerous_bidirectional_unicode_text: false,
        diff: null,
        file_path: $state.params.file_path,
        pull_request_id: SharedPropertiesService.getPullRequest().id,
        shouldShowUnifiedDiff,
        shouldShowSideBySideDiff,
        $onInit: init,
    });

    function init() {
        const pull_request_comment_presenter_builder = PullRequestCommentPresenterBuilder(
            $state,
            self.relative_date_helper
        );

        FileDiffRestService.getUnidiff(self.pull_request_id, self.file_path)
            .then((diff) => {
                self.diff = diff;
                self.is_binary_file = diff.charset === "binary";
                self.special_format = diff.special_format;
                self.has_potentially_dangerous_bidirectional_unicode_text =
                    doesChangedCodeContainsPotentiallyDangerousBidirectionalUnicodeText(diff);

                const comments = diff.inline_comments.map(
                    pull_request_comment_presenter_builder.fromFileDiffComment
                );

                initComments(comments);
            })
            .finally(() => {
                self.is_loading = false;
            });
    }

    function shouldShowUnifiedDiff() {
        return (
            !self.is_loading &&
            !self.is_binary_file &&
            self.special_format === "" &&
            isUnifiedMode()
        );
    }

    function shouldShowSideBySideDiff() {
        return (
            !self.is_loading &&
            !self.is_binary_file &&
            self.special_format === "" &&
            isSideBySideMode()
        );
    }
}
