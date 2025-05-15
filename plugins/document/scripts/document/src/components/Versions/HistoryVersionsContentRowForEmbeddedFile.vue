<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
  -
  -->

<template>
    <tr>
        <td class="tlp-table-cell-numeric">
            <a v-bind:href="version.open_href">{{ version.number }}</a>
        </td>
        <td>
            <document-relative-date v-bind:date="version.date" />
        </td>
        <td>
            <user-badge v-bind:user="version.author" />
        </td>
        <td>{{ version.name }}</td>
        <td>{{ version.changelog }}</td>
        <td>
            <a
                v-if="version.approval_href"
                v-bind:href="version.approval_href"
                data-test="approval-link"
                >{{ $gettext("Show") }}</a
            >
        </td>
        <td class="tlp-table-cell-actions">
            <button
                type="button"
                class="tlp-table-cell-actions-button tlp-button-small tlp-button-danger tlp-button-outline"
                v-if="item.user_can_delete"
                ref="delete_button"
                v-bind:disabled="!has_more_than_one_version"
                v-bind:title="
                    has_more_than_one_version
                        ? ''
                        : $gettext('The last version of an item cannot be deleted')
                "
                data-test="delete-button"
            >
                <i class="fa-solid fa-trash-can tlp-button-icon" aria-hidden="true"></i>
                {{ $gettext("Delete") }}
            </button>
            <div
                v-if="has_more_than_one_version"
                ref="confirm_deletion"
                class="tlp-modal tlp-modal-danger"
                role="dialog"
                v-bind:aria-labelledby="'confirmation-modal-title-' + version.id"
            >
                <div class="tlp-modal-header">
                    <h1
                        class="tlp-modal-title"
                        v-bind:id="'confirmation-modal-title-' + version.id"
                    >
                        {{ $gettext("Hold on a second!") }}
                    </h1>
                    <button
                        class="tlp-modal-close"
                        type="button"
                        data-dismiss="modal"
                        v-bind:aria-label="$gettext('Close')"
                    >
                        <i class="fa-solid fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="tlp-modal-feedback" v-if="got_error_while_trying_to_delete">
                    <div class="tlp-alert-danger">
                        {{ $gettext("An error occurred while deleting the version") }}
                    </div>
                </div>
                <div class="tlp-modal-body">
                    <p>
                        {{
                            $gettext(
                                "You are about to delete a version permanently. Please confirm your action.",
                            )
                        }}
                    </p>
                </div>
                <div class="tlp-modal-footer">
                    <button
                        type="button"
                        class="tlp-button-danger tlp-button-outline tlp-modal-action"
                        data-dismiss="modal"
                    >
                        {{ $gettext("Cancel") }}
                    </button>
                    <button
                        type="button"
                        class="tlp-button-danger tlp-modal-action"
                        v-on:click="onConfirmDeletion"
                        data-test="confirm-button"
                        v-bind:disabled="is_deleting || got_error_while_trying_to_delete"
                    >
                        <i
                            class="tlp-button-icon"
                            aria-hidden="true"
                            v-bind:class="{
                                'fa-solid fa-trash-can': !is_deleting,
                                'fa-solid fa-circle-notch fa-spin': is_deleting,
                            }"
                        ></i>
                        {{ $gettext("Delete") }}
                    </button>
                </div>
            </div>
        </td>
    </tr>
</template>

<script setup lang="ts">
import UserBadge from "../User/UserBadge.vue";
import { inject, onMounted, onUnmounted, ref } from "vue";
import type { Embedded, EmbeddedFileVersion } from "../../type";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal, EVENT_TLP_MODAL_HIDDEN } from "@tuleap/tlp-modal";
import { deleteEmbeddedFileVersion } from "../../api/version-rest-querier";
import DocumentRelativeDate from "../Date/DocumentRelativeDate.vue";
import { useGettext } from "vue3-gettext";
import { FEEDBACK } from "../../injection-keys";
import { noop_feedack_handler } from "../../helpers/noop-feedback-handler";

const props = defineProps<{
    item: Embedded;
    version: EmbeddedFileVersion;
    has_more_than_one_version: boolean;
    loadVersions: () => void;
}>();

const confirm_deletion = ref<HTMLElement | null>(null);
const delete_button = ref<HTMLButtonElement | null>(null);
const is_deleting = ref(false);
const got_error_while_trying_to_delete = ref(false);

let modal: Modal | null = null;

function showConfirmationModal(): void {
    if (modal) {
        modal.show();
    }
}

const { success } = inject(FEEDBACK, noop_feedack_handler);
const gettext_provider = useGettext();

function onConfirmDeletion(): void {
    is_deleting.value = true;
    deleteEmbeddedFileVersion(props.version.id).match(
        () => {
            success(
                gettext_provider.interpolate(
                    gettext_provider.$gettext("Version %{ number } has been successfully deleted"),
                    { number: props.version.number },
                ),
            );
            props.loadVersions();
        },
        () => {
            got_error_while_trying_to_delete.value = true;
            is_deleting.value = false;
        },
    );
}

function resetErrorMessageStatus(): void {
    got_error_while_trying_to_delete.value = false;
}

onMounted(() => {
    if (!confirm_deletion.value) {
        return;
    }

    if (!delete_button.value) {
        return;
    }

    modal = createModal(confirm_deletion.value);
    delete_button.value.addEventListener("click", showConfirmationModal);
    modal.addEventListener(EVENT_TLP_MODAL_HIDDEN, resetErrorMessageStatus);
});

onUnmounted(() => {
    if (delete_button.value && modal) {
        delete_button.value.removeEventListener("click", showConfirmationModal);
    }

    if (modal) {
        modal.removeEventListener(EVENT_TLP_MODAL_HIDDEN, resetErrorMessageStatus);
        modal.destroy();
    }
});
</script>
