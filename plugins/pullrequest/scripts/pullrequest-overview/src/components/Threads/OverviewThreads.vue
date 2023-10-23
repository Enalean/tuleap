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
            <template v-for="(item, index) in timeline.list">
                <tuleap-pullrequest-timeline-event-comment
                    v-if="isAnEvent(item)"
                    data-test="pull-request-overview-action-event"
                    v-bind:key="index"
                    v-bind:event="item"
                    v-bind:current_user="current_user_presenter"
                />
                <tuleap-pullrequest-comment
                    v-if="isAComment(item)"
                    data-test="pull-request-thread"
                    class="pull-request-overview-thread"
                    v-bind:key="`${index}${item.id}`"
                    v-bind:comment="item"
                    v-bind:controller="comments_controller"
                    v-bind:is_comment_edition_enabled="is_comment_edition_enabled"
                />
            </template>
        </div>
        <overview-new-comment-form v-if="!is_loading_threads" />
    </section>
</template>

<script setup lang="ts">
import { ref, reactive, provide, watch } from "vue";
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";
import type {
    ActionOnPullRequestEvent,
    PullRequest,
    PullRequestComment,
    User,
} from "@tuleap/plugin-pullrequest-rest-api-types";
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
    ControlPullRequestDescriptionComment,
    StorePullRequestCommentReplies,
    PullRequestDescriptionCommentPresenter,
} from "@tuleap/plugin-pullrequest-comments";
import {
    PULL_REQUEST_ACTIONS_LIST,
    TYPE_EVENT_REVIEWER_CHANGE,
    TYPE_GLOBAL_COMMENT,
    TYPE_INLINE_COMMENT,
} from "@tuleap/plugin-pullrequest-constants";
import { DescriptionCommentPresenterBuilder } from "./DescriptionCommentPresenterBuilder";

type DisplayableItem = PullRequestCommentPresenter | ActionOnPullRequestEvent;
type SupportedTimelineItem = PullRequestComment | ActionOnPullRequestEvent;

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
const timeline = reactive<{ list: DisplayableItem[] }>({ list: [] });
const comments_controller = ref<null | ControlPullRequestComment>(null);
const replies_store = ref<StorePullRequestCommentReplies>();
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

const isAnEvent = (
    item: DisplayableItem | SupportedTimelineItem,
): item is ActionOnPullRequestEvent =>
    "event_type" in item && PULL_REQUEST_ACTIONS_LIST.includes(item.event_type);

const isAComment = (item: DisplayableItem | SupportedTimelineItem): item is PullRequestComment =>
    "type" in item && (item.type === TYPE_GLOBAL_COMMENT || item.type === TYPE_INLINE_COMMENT);

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

        fetchPullRequestTimelineItems(pull_request_id).match(
            (timeline_items) => {
                const supported_timeline_items = timeline_items.filter(
                    (timeline_item): timeline_item is SupportedTimelineItem =>
                        timeline_item.type !== TYPE_EVENT_REVIEWER_CHANGE,
                );

                const action_events = supported_timeline_items.filter(isAnEvent);
                const comments = supported_timeline_items.filter(isAComment);

                replies_store.value = PullRequestCommentRepliesStore(
                    comments.map((timeline_item) => {
                        return CommentPresenterBuilder.fromPayload(
                            timeline_item,
                            base_url,
                            pull_request_id,
                        );
                    }),
                );

                const display = [...replies_store.value.getAllRootComments(), ...action_events];

                timeline.list = display.sort(
                    (a: { post_date: string }, b: { post_date: string }) =>
                        Date.parse(a.post_date) - Date.parse(b.post_date),
                );

                comments_controller.value = PullRequestCommentController(
                    replies_store.value,
                    NewReplySaver(),
                    current_user_presenter.value,
                    current_pull_request_presenter.value,
                    displayTuleapAPIFault,
                );

                is_loading_threads.value = false;
            },
            (fault) => {
                displayTuleapAPIFault(fault);
            },
        );
    },
);

function addNewRootComment(comment: PullRequestCommentPresenter): void {
    if (!replies_store.value) {
        return;
    }

    replies_store.value.addRootComment(comment);
    timeline.list.push(comment);
}
</script>

<style lang="scss">
@use "@tuleap/plugin-pullrequest-comments";

.pull-request-overview-thread > .pull-request-comment-component,
.pull-request-description > .pull-request-description-comment,
.pull-request-comment-skeleton,
.pull-request-timeline-event {
    margin: 0 0 var(--tlp-small-spacing);
}

.pull-request-threads-section {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
}
</style>
