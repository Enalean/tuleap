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
    <!-- Prevent eslint-plugin-vue to mess up with the relativeDateHelper attribute of our custom element -->
    <!-- eslint-disable vue/attribute-hyphenation -->
    <section class="tlp-pane-section">
        <div
            v-if="is_loading_threads"
            class="pull-request-overview-threads-spinner-container"
            data-test="pull-request-threads-spinner"
        >
            <i class="fa-solid fa-spinner fa-spin fa-2xl"></i>
        </div>
        <div v-if="!is_loading_threads" data-test="pull-request-threads">
            <tuleap-pullrequest-comment
                data-test="pull-request-thread"
                class="pull-request-overview-thread"
                v-for="thread in threads"
                v-bind:key="thread.id"
                v-bind:comment="thread"
                v-bind:controller="comments_controller"
                v-bind:relativeDateHelper="relative_date_helper"
            />
        </div>
    </section>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useGettext } from "vue3-gettext";
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";
import { RelativeDatesHelper } from "../../helpers/relative-dates-helper";
import { strictInject } from "../../helpers/strict-inject";
import {
    PULL_REQUEST_ID_KEY,
    CURRENT_USER_ID,
    CURRENT_USER_AVATAR_URL,
    USER_DATE_TIME_FORMAT_KEY,
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
    USER_LOCALE_KEY,
    OVERVIEW_APP_BASE_URL_KEY,
    DISPLAY_TULEAP_API_ERROR,
} from "../../constants";
import { fetchPullRequestTimelineItems } from "../../api/tuleap-rest-querier";
import { CommentPresenterBuilder } from "./CommentPresenterBuilder";

import "@tuleap/plugin-pullrequest-comments";

import type {
    PullRequestCommentPresenter,
    ControlPullRequestComment,
    CurrentPullRequestUserPresenter,
    PullRequestPresenter,
    HelpRelativeDatesDisplay,
    SupportedTimelineItem,
} from "@tuleap/plugin-pullrequest-comments";

import {
    PullRequestCommentController,
    PullRequestCommentReplyFormFocusHelper,
    PullRequestCommentRepliesStore,
    PullRequestCommentNewReplySaver,
} from "@tuleap/plugin-pullrequest-comments";

import { TYPE_EVENT_REVIEWER_CHANGE } from "@tuleap/plugin-pullrequest-constants";

const { $gettext } = useGettext();

const base_url: URL = strictInject(OVERVIEW_APP_BASE_URL_KEY);
const displayTuleapAPIFault = strictInject(DISPLAY_TULEAP_API_ERROR);
const pull_request_id: string = strictInject(PULL_REQUEST_ID_KEY);
const user_id: number = strictInject(CURRENT_USER_ID);
const avatar_url: string = strictInject(CURRENT_USER_AVATAR_URL);
const date_time_format: string = strictInject(USER_DATE_TIME_FORMAT_KEY);
const user_locale: string = strictInject(USER_LOCALE_KEY);
const relative_date_display: RelativeDatesDisplayPreference = strictInject(
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY
);

const is_loading_threads = ref(true);
const threads = ref<PullRequestCommentPresenter[]>([]);
const comments_presenters = ref<PullRequestCommentPresenter[]>([]);
const comments_controller = ref<null | ControlPullRequestComment>(null);
const current_user_presenter = ref<CurrentPullRequestUserPresenter>({ user_id, avatar_url });
const current_pull_request_presenter = ref<PullRequestPresenter>({
    pull_request_id: Number.parseInt(pull_request_id, 10),
});
const relative_date_helper = ref<HelpRelativeDatesDisplay>(
    RelativeDatesHelper(date_time_format, relative_date_display, user_locale)
);

fetchPullRequestTimelineItems(pull_request_id)
    .match(
        (result) => {
            comments_presenters.value = result
                .filter(
                    (comment): comment is SupportedTimelineItem =>
                        comment.type !== TYPE_EVENT_REVIEWER_CHANGE
                )
                .map((comment) =>
                    CommentPresenterBuilder.fromPayload(
                        comment,
                        base_url,
                        pull_request_id,
                        $gettext
                    )
                );
        },
        (fault) => {
            displayTuleapAPIFault(fault);
        }
    )
    .then(() => {
        const replies_store = PullRequestCommentRepliesStore(comments_presenters.value);
        threads.value = replies_store.getAllRootComments();

        comments_controller.value = PullRequestCommentController(
            PullRequestCommentReplyFormFocusHelper(),
            replies_store,
            PullRequestCommentNewReplySaver(),
            current_user_presenter.value,
            current_pull_request_presenter.value
        );

        is_loading_threads.value = false;
    });
</script>

<style lang="scss">
@use "@tuleap/plugin-pullrequest-comments";

.pull-request-overview-threads-spinner-container {
    display: flex;
    justify-content: center;
    padding: var(--tlp-x-large-spacing) 0;
}

.pull-request-overview-thread > .pull-request-comment-component {
    margin: 0 0 var(--tlp-small-spacing);
}
</style>
