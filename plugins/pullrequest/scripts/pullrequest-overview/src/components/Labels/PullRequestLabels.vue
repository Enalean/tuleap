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
    <div class="tlp-property">
        <label class="tlp-label">
            {{ $gettext("Labels") }}
        </label>
        <div class="pull-request-labels">
            <span
                v-for="label in pull_request_labels"
                v-bind:key="label.id"
                v-bind:class="getBadgeClasses(label)"
                data-test="pull-request-label"
            >
                <i class="fa-solid fa-tag tlp-badge-icon" aria-hidden="true"></i>{{ label.label }}
            </span>
            <span
                v-if="has_no_labels"
                class="pull-request-no-labels-empty-state-text"
                data-test="no-labels-yet-text"
            >
                {{ $gettext("No labels have been assigned yet") }}
            </span>
            <button
                v-if="!is_loading_labels && can_user_manage_labels"
                data-test="manage-labels-button"
                class="tlp-button-primary tlp-button-outline pull-request-manage-labels-button"
                v-bind:aria-label="$gettext(`Manage pull-request's labels`)"
                v-on:click="openModal()"
            >
                <i
                    class="tlp-button-icon fa-solid fa-pencil pull-request-manage-labels-button-icon"
                    aria-hidden="true"
                ></i>
            </button>
        </div>
        <property-skeleton v-if="is_loading_labels" />
        <pull-request-manage-labels-modal
            v-if="is_modal_shown && can_user_manage_labels"
            v-bind:current_labels="pull_request_labels"
            v-bind:project_labels="project_labels"
            v-bind:post_edition_callback="post_edition_callback"
            v-bind:on_cancel_callback="on_cancel_callback"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { ProjectLabel, PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import { fetchProjectLabels, fetchPullRequestLabels } from "../../api/tuleap-rest-querier";
import { DISPLAY_TULEAP_API_ERROR, PULL_REQUEST_ID_KEY } from "../../constants";
import PropertySkeleton from "../ReadOnlyInfo/PropertySkeleton.vue";
import { isPullRequestBroken } from "../Actions/merge-status-helper";
import PullRequestManageLabelsModal from "./PullRequestManageLabelsModal.vue";

const { $gettext } = useGettext();

const props = defineProps<{
    pull_request: PullRequest | null;
}>();

const pull_request_id = strictInject(PULL_REQUEST_ID_KEY);
const displayTuleapAPIFault = strictInject(DISPLAY_TULEAP_API_ERROR);

const pull_request_labels = ref<ReadonlyArray<ProjectLabel>>([]);
const project_labels = ref<ReadonlyArray<ProjectLabel>>([]);

const are_pull_request_labels_loading = ref(true);
const are_project_labels_loading = ref(true);
const is_modal_shown = ref(false);

const is_loading_labels = computed(
    () => are_project_labels_loading.value || are_pull_request_labels_loading.value,
);
const has_no_labels = computed(
    () => pull_request_labels.value.length === 0 && !are_pull_request_labels_loading.value,
);
const can_user_manage_labels = computed(
    () =>
        props.pull_request &&
        props.pull_request.user_can_update_labels &&
        !isPullRequestBroken(props.pull_request),
);

const getBadgeClasses = (label: ProjectLabel): string[] => {
    const classes = [`tlp-badge-${label.color}`];

    if (label.is_outline) {
        classes.push("tlp-badge-outline");
    }

    return classes;
};

const openModal = (): void => {
    is_modal_shown.value = true;
};

const fetchAllLabels = (pull_request: PullRequest) => {
    fetchProjectLabels(pull_request.repository.project.id)
        .match((result) => {
            project_labels.value = result;
        }, displayTuleapAPIFault)
        .finally(() => {
            are_project_labels_loading.value = false;
        });

    fetchPullRequestLabels(pull_request_id)
        .match((result) => {
            pull_request_labels.value = result;
        }, displayTuleapAPIFault)
        .finally(() => {
            are_pull_request_labels_loading.value = false;
        });
};

const post_edition_callback = (): void => {
    if (props.pull_request) {
        fetchAllLabels(props.pull_request);
    }

    is_modal_shown.value = false;
};

const on_cancel_callback = (): void => {
    is_modal_shown.value = false;
};

watch(
    () => props.pull_request,
    () => {
        if (!props.pull_request) {
            return;
        }

        fetchAllLabels(props.pull_request);
    },
);
</script>

<style lang="scss">
@use "../../../themes/common";

.pull-request-labels {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
    align-items: center;
}

.pull-request-no-labels-empty-state-text {
    color: var(--tlp-dark-color);
    font-style: italic;

    + .pull-request-manage-labels-button {
        margin: 0 0 0 var(--tlp-small-spacing);
    }
}

.pull-request-manage-labels-button {
    @include common.edit-button-icon-only;

    .pull-request-manage-labels-button-icon {
        margin: 0;
        font-size: 0.65rem;
    }
}
</style>
