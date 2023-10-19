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
    <section class="tlp-pane-section pull-request-threads-section">
        <tuleap-pullrequest-comment-skeleton
            v-if="is_loading_threads"
            v-bind:has_replies="false"
            class="pull-request-description"
            data-test="pull-request-description-comment-skeleton"
        />
        <tuleap-pullrequest-comment-skeleton
            v-if="is_loading_threads"
            v-bind:has_replies="true"
            class="pull-request-overview-thread"
            data-test="pull-request-threads-skeleton"
        />
        <div v-if="!is_loading_threads" data-test="pull-request-threads">
            <tuleap-pullrequest-description-comment
                data-test="pull-request-overview-description"
                class="pull-request-description"
                v-bind:description="description_comment_presenter"
                v-bind:controller="description_comment_controller"
            />
            <tuleap-pullrequest-comment
                data-test="pull-request-thread"
                class="pull-request-overview-thread"
                v-for="(thread, index) in threads.list"
                v-bind:key="`${index}${thread.id}`"
                v-bind:comment="thread"
                v-bind:controller="comments_controller"
                v-bind:is_comment_edition_enabled="is_comment_edition_enabled"
            />
        </div>
        <overview-new-comment-form v-if="!is_loading_threads" />
    </section>
</template>

<script setup lang="ts">
import { ref, reactive, provide, watch } from "vue";
import { useGettext } from "vue3-gettext";
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";
import type { PullRequest, User } from "@tuleap/plugin-pullrequest-rest-api-types";
import { strictInject } from "@tuleap/vue-strict-inject";
import {
    PULL_REQUEST_ID_KEY,
    CURRENT_USER_ID,
    CURRENT_USER_AVATAR_URL,
    USER_DATE_TIME_FORMAT_KEY,
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
    USER_LOCALE_KEY,
    OVERVIEW_APP_BASE_URL_KEY,
    DISPLAY_TULEAP_API_ERROR,
    DISPLAY_NEWLY_CREATED_GLOBAL_COMMENT,
    PROJECT_ID,
    IS_COMMENT_EDITION_ENABLED,
} from "../../constants";
import { fetchPullRequestTimelineItems } from "../../api/tuleap-rest-querier";
import { CommentPresenterBuilder } from "./CommentPresenterBuilder";

import OverviewNewCommentForm from "./OverviewNewCommentForm.vue";

import {
    PullRequestCommentController,
    PullRequestCommentRepliesStore,
    NewReplySaver,
    PullRequestDescriptionCommentSaver,
    PullRequestDescriptionCommentController,
} from "@tuleap/plugin-pullrequest-comments";

import type {
    PullRequestCommentPresenter,
    ControlPullRequestComment,
    CurrentPullRequestUserPresenter,
    PullRequestPresenter,
    SupportedTimelineItem,
    ControlPullRequestDescriptionComment,
    StorePullRequestCommentReplies,
    PullRequestDescriptionCommentPresenter,
} from "@tuleap/plugin-pullrequest-comments";

import { TYPE_EVENT_REVIEWER_CHANGE } from "@tuleap/plugin-pullrequest-constants";
import { DescriptionCommentPresenterBuilder } from "./DescriptionCommentPresenterBuilder";

const { $gettext } = useGettext();

const props = defineProps<{
    pull_request_info: PullRequest | null;
    pull_request_author: User | null;
}>();

const base_url: URL = strictInject(OVERVIEW_APP_BASE_URL_KEY);
const displayTuleapAPIFault = strictInject(DISPLAY_TULEAP_API_ERROR);
const pull_request_id: number = strictInject(PULL_REQUEST_ID_KEY);
const user_id: number = strictInject(CURRENT_USER_ID);
const project_id: number = strictInject(PROJECT_ID);
const avatar_url: string = strictInject(CURRENT_USER_AVATAR_URL);
const date_time_format: string = strictInject(USER_DATE_TIME_FORMAT_KEY);
const user_locale: string = strictInject(USER_LOCALE_KEY);
const relative_date_display: RelativeDatesDisplayPreference = strictInject(
    USER_RELATIVE_DATE_DISPLAY_PREFERENCE_KEY,
);
const is_comment_edition_enabled: boolean = strictInject(IS_COMMENT_EDITION_ENABLED);

const is_loading_threads = ref(true);
const threads = reactive<{ list: PullRequestCommentPresenter[] }>({ list: [] });
const comments_presenters = ref<PullRequestCommentPresenter[]>([]);
const comments_controller = ref<null | ControlPullRequestComment>(null);
const replies_store = ref<null | StorePullRequestCommentReplies>(null);
const current_user_presenter = ref<CurrentPullRequestUserPresenter>({
    user_id,
    avatar_url,
    user_locale,
    preferred_date_format: date_time_format,
    preferred_relative_date_display: relative_date_display,
});
const current_pull_request_presenter = ref<PullRequestPresenter>({
    pull_request_id,
    project_id,
});
const description_comment_presenter = ref<null | PullRequestDescriptionCommentPresenter>(null);
const description_comment_controller = ref<ControlPullRequestDescriptionComment>(
    PullRequestDescriptionCommentController(
        PullRequestDescriptionCommentSaver(),
        current_user_presenter.value,
        displayTuleapAPIFault,
    ),
);

provide(DISPLAY_NEWLY_CREATED_GLOBAL_COMMENT, addNewRootComment);

watch(
    () => props.pull_request_info && props.pull_request_author,
    () => {
        if (!props.pull_request_info || !props.pull_request_author) {
            return;
        }

        description_comment_presenter.value =
            DescriptionCommentPresenterBuilder.fromPullRequestAndItsAuthor(
                props.pull_request_info,
                props.pull_request_author,
                project_id,
            );

        fetchPullRequestTimelineItems(pull_request_id)
            .match(
                (result) => {
                    comments_presenters.value = result
                        .filter(
                            (comment): comment is SupportedTimelineItem =>
                                comment.type !== TYPE_EVENT_REVIEWER_CHANGE,
                        )
                        .map((comment) =>
                            CommentPresenterBuilder.fromPayload(
                                comment,
                                base_url,
                                pull_request_id,
                                $gettext,
                            ),
                        );
                },
                (fault) => {
                    displayTuleapAPIFault(fault);
                },
            )
            .then(() => {
                replies_store.value = PullRequestCommentRepliesStore(comments_presenters.value);
                threads.list = [...replies_store.value.getAllRootComments()];

                comments_controller.value = PullRequestCommentController(
                    replies_store.value,
                    NewReplySaver(),
                    current_user_presenter.value,
                    current_pull_request_presenter.value,
                    displayTuleapAPIFault,
                );

                is_loading_threads.value = false;
            });
    },
);

function addNewRootComment(comment: PullRequestCommentPresenter): void {
    if (!replies_store.value) {
        return;
    }

    replies_store.value.addRootComment(comment);
    threads.list.push(comment);
}
</script>

<style lang="scss">
@use "@tuleap/plugin-pullrequest-comments";

.pull-request-overview-thread > .pull-request-comment-component,
.pull-request-description > .pull-request-description-comment,
.pull-request-comment-skeleton {
    margin: 0 0 var(--tlp-small-spacing);
}

.pull-request-threads-section {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
}
</style>
