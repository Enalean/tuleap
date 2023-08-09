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
    <div class="pull-request-edit-title" v-if="can_user_edit_title">
        <button
            class="tlp-button-primary tlp-button-outline"
            type="button"
            v-on:click="openModal"
            data-test="pull-request-open-title-modal-button"
        >
            <i class="tlp-button-icon fa-solid fa-pencil"></i>
            {{ $gettext("Edit title") }}
        </button>
        <div
            ref="modal_element"
            class="tlp-modal"
            role="dialog"
            aria-labelledby="pull-request-edit-modal-title"
        >
            <div class="tlp-modal-header">
                <h1 id="pull-request-edit-modal-title" class="tlp-modal-title">
                    <span>{{ $gettext("Edit title") }}</span>
                </h1>
                <button
                    class="tlp-modal-close"
                    type="button"
                    data-dismiss="modal"
                    v-bind:aria-label="$gettext('Close')"
                >
                    <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
                </button>
            </div>
            <div class="tlp-modal-body">
                <div class="tlp-form-element">
                    <label class="tlp-label" for="pullrequest-title">
                        {{ $gettext("Title") }}
                        <i class="fa fa-asterisk"></i>
                    </label>
                    <input
                        id="pullrequest-title"
                        class="tlp-input"
                        type="text"
                        v-model="title"
                        data-test="pull-request-edit-title-input"
                        v-bind:placeholder="$gettext('Title')"
                        required
                    />
                </div>
            </div>
            <div class="tlp-modal-footer">
                <button
                    type="button"
                    class="tlp-button-primary tlp-button-outline tlp-modal-action"
                    data-dismiss="modal"
                >
                    <span>{{ $gettext("Cancel") }}</span>
                </button>
                <button
                    type="submit"
                    class="tlp-button-primary tlp-modal-action"
                    v-bind:disabled="is_saving"
                    v-on:click="saveTitle()"
                    data-test="pull-request-save-changes-button"
                >
                    <span>{{ $gettext("Save changes") }}</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch, onBeforeUnmount, computed } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal, EVENT_TLP_MODAL_HIDDEN } from "@tuleap/tlp-modal";
import { useGettext } from "vue3-gettext";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import { patchTitle } from "../../api/tuleap-rest-querier";
import { strictInject } from "@tuleap/vue-strict-inject";
import { DISPLAY_TULEAP_API_ERROR, POST_PULL_REQUEST_UPDATE_CALLBACK } from "../../constants";

const { $gettext } = useGettext();
const props = defineProps<{
    pull_request_info: PullRequest | null;
}>();
const modal_element = ref<Element | undefined>();
const modal_instance = ref<Modal | null>(null);
const is_saving = ref(false);
const title = ref("");
const displayTuleapAPIFault = strictInject(DISPLAY_TULEAP_API_ERROR);
const postPullRequestUpdateCallback = strictInject(POST_PULL_REQUEST_UPDATE_CALLBACK);

const can_user_edit_title = computed(
    (): boolean =>
        props.pull_request_info !== null &&
        props.pull_request_info.user_can_update_title_and_description
);

watch(
    () => [props.pull_request_info, modal_element.value],
    () => {
        if (props.pull_request_info === null || !can_user_edit_title.value) {
            return;
        }

        title.value = props.pull_request_info.raw_title;

        if (modal_element.value) {
            modal_instance.value = createModal(modal_element.value, {
                destroy_on_hide: false,
                keyboard: false,
            });

            modal_instance.value.addEventListener(EVENT_TLP_MODAL_HIDDEN, close);
        }
    }
);

onBeforeUnmount(() => {
    if (modal_instance.value) {
        modal_instance.value.removeEventListener(EVENT_TLP_MODAL_HIDDEN, close);
    }
});

async function saveTitle(): Promise<void> {
    if (!props.pull_request_info || !can_user_edit_title.value) {
        return;
    }
    is_saving.value = true;

    await patchTitle(props.pull_request_info.id, title.value).match(
        (updated_pull_request: PullRequest) => {
            is_saving.value = false;
            postPullRequestUpdateCallback(updated_pull_request);
            if (modal_instance.value) {
                modal_instance.value.hide();
            }
        },
        (fault) => {
            is_saving.value = false;
            displayTuleapAPIFault(fault);
        }
    );
}

function openModal(): void {
    if (modal_instance.value) {
        modal_instance.value.show();
    }
}

function close(): void {
    if (props.pull_request_info) {
        title.value = props.pull_request_info.raw_title;
    }
}
</script>

<style lang="scss">
.pull-request-edit-title {
    margin: 0 0 0 var(--tlp-small-spacing);
}
</style>
