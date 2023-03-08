<!--
  - Copyright (c) Enalean, 2023 - present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <tuleap-pullrequest-new-comment-form
        data-test="pull-request-new-global-comment-component"
        v-bind:comment_saver="comment_saver"
        v-bind:post_submit_callback="post_submit_callback"
        v-bind:error_callback="displayTuleapAPIFault"
        v-bind:config="{ is_cancel_allowed: false, is_autofocus_enabled: false }"
        v-bind:author_presenter="{ avatar_url }"
    />
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { NewCommentSaver } from "@tuleap/plugin-pullrequest-comments";
import { TYPE_GLOBAL_COMMENT } from "@tuleap/plugin-pullrequest-constants";
import type { PullRequestComment } from "@tuleap/plugin-pullrequest-rest-api-types";
import { strictInject } from "../../helpers/strict-inject";

import {
    CURRENT_USER_AVATAR_URL,
    CURRENT_USER_ID,
    DISPLAY_NEWLY_CREATED_GLOBAL_COMMENT,
    DISPLAY_TULEAP_API_ERROR,
    OVERVIEW_APP_BASE_URL_KEY,
    PULL_REQUEST_ID_KEY,
} from "../../constants";
import { CommentPresenterBuilder } from "./CommentPresenterBuilder";

const { $gettext } = useGettext();

const user_id: number = strictInject(CURRENT_USER_ID);
const pull_request_id: string = strictInject(PULL_REQUEST_ID_KEY);
const base_url: URL = strictInject(OVERVIEW_APP_BASE_URL_KEY);
const avatar_url: string = strictInject(CURRENT_USER_AVATAR_URL);
const displayNewlyCreatedGlobalComment = strictInject(DISPLAY_NEWLY_CREATED_GLOBAL_COMMENT);
const displayTuleapAPIFault = strictInject(DISPLAY_TULEAP_API_ERROR);

const comment_saver = NewCommentSaver({
    user_id,
    type: TYPE_GLOBAL_COMMENT,
    pull_request_id: Number.parseInt(pull_request_id, 10),
});

const post_submit_callback = (new_comment: PullRequestComment): void => {
    if (new_comment.type !== TYPE_GLOBAL_COMMENT) {
        return;
    }

    displayNewlyCreatedGlobalComment(
        CommentPresenterBuilder.fromPayload(new_comment, base_url, pull_request_id, $gettext)
    );
};
</script>
