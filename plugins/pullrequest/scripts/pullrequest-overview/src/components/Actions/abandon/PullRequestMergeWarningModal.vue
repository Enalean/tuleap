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
    <div
        ref="modal_element"
        class="tlp-modal tlp-modal-warning"
        role="dialog"
        aria-labelledby="pull-request-merge-with-warning-modal"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="pull-request-merge-with-warning-modal">
                {{ $gettext("Warning") }}
            </h1>
            <button
                data-dismiss="modal"
                class="tlp-modal-close"
                type="button"
                v-bind:aria-label="$gettext('Close')"
            >
                <i class="fa-solid fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <h2 class="tlp-modal-subtitle">{{ getModalTitle() }}</h2>
            <div
                class="tlp-alert-warning"
                v-if="may_need_rebase"
                data-test="warning-not-fast-forward-merge"
            >
                <p class="tlp-alert-title">{{ $gettext("Non fast-forward merge") }}</p>
                {{
                    $gettext(
                        "Pull request destination has diverged. Merge will not resolve in a fast-forward. You can proceed with the merge, or cancel and update your pull request",
                    )
                }}
            </div>
            <div
                class="tlp-alert-warning"
                v-if="is_missing_ci_validation"
                data-test="warning-missing-ci-validation"
            >
                {{ $gettext("The last CI status is") }}
                <span class="pull-request-warning-last-ci-status"> {{ getI18nCIStatus() }}. </span>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                data-dismiss="modal"
                type="button"
                class="tlp-button-warning tlp-button-outline tlp-modal-action"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                v-on:click="merge()"
                data-dismiss="modal"
                type="button"
                class="tlp-button-warning tlp-modal-action"
                data-test="merge-anyway-button"
            >
                {{ $gettext("Merge anyway") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { useGettext } from "vue3-gettext";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal, EVENT_TLP_MODAL_HIDDEN } from "@tuleap/tlp-modal";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import { ARE_MERGE_COMMITS_ALLOWED_IN_REPOSITORY } from "../../../constants";
import { isCIHappy, isFastForwardMerge } from "../merge-status-helper";
import {
    BUILD_STATUS_FAILED,
    BUILD_STATUS_PENDING,
    BUILD_STATUS_UNKNOWN,
} from "@tuleap/plugin-pullrequest-constants";

const { $gettext, $ngettext } = useGettext();

const are_merge_commits_allowed_in_repository = strictInject(
    ARE_MERGE_COMMITS_ALLOWED_IN_REPOSITORY,
);

const props = defineProps<{
    pull_request: PullRequest;
    merge_callback: () => void;
    cancel_callback: () => void;
}>();

const modal_element = ref<Element | undefined>();
const modal_instance = ref<Modal | null>(null);

const cancel = (): void => {
    props.cancel_callback();
};

const merge = (): void => {
    props.merge_callback();
};

onMounted(() => {
    if (!modal_element.value) {
        return;
    }

    modal_instance.value = createModal(modal_element.value, {
        destroy_on_hide: false,
        keyboard: false,
    });

    modal_instance.value.show();
    modal_instance.value.addEventListener(EVENT_TLP_MODAL_HIDDEN, cancel);
});

onBeforeUnmount(() => {
    if (modal_instance.value) {
        modal_instance.value.removeEventListener(EVENT_TLP_MODAL_HIDDEN, cancel);
    }
});

const may_need_rebase = computed(
    () => !isFastForwardMerge(props.pull_request) && are_merge_commits_allowed_in_repository,
);
const is_missing_ci_validation = computed(() => !isCIHappy(props.pull_request));

const getModalTitle = (): string => {
    let nb_warnings = 0;

    if (may_need_rebase.value === true) {
        nb_warnings++;
    }

    if (is_missing_ci_validation.value === true) {
        nb_warnings++;
    }

    return $ngettext(
        "Please be aware of the following warning before you decide to merge:",
        "Please be aware of these %{ nb_warnings } warnings before you decide to merge:",
        nb_warnings,
        { nb_warnings: String(nb_warnings) },
    );
};

const getI18nCIStatus = (): string => {
    switch (props.pull_request.last_build_status) {
        case BUILD_STATUS_UNKNOWN:
            return $gettext("unknown");
        case BUILD_STATUS_PENDING:
            return $gettext("pending");
        case BUILD_STATUS_FAILED:
            return $gettext("fail");
        default:
            return "";
    }
};
</script>
<style lang="scss">
.pull-request-warning-last-ci-status {
    font-weight: 600;
}
</style>
