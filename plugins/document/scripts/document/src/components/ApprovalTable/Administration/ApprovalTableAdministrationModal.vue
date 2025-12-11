<!--
  - Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
        id="admin-modal"
        role="dialog"
        aria-labelledby="admin-modal-label"
        class="tlp-modal tlp-modal-medium-sized"
        ref="modal_div"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="admin-modal-label">
                {{ $gettext("Approval table administration") }}
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

        <div class="tlp-modal-feedback">
            <div v-if="error_message !== ''" class="tlp-alert-danger" data-test="admin-modal-error">
                <p class="tlp-alert-title">{{ $gettext("Error while updating table") }}</p>
                {{ error_message }}
            </div>
            <div v-if="success_message !== ''" class="tlp-alert-success">
                {{ success_message }}
            </div>
        </div>

        <div v-if="isTableLinkedToLastItemVersion(item, table)" class="tlp-modal-body">
            <administration-modal-global-settings
                v-bind:table="table"
                v-model:table_status_value="table_status_value"
                v-model:table_comment_value="table_comment_value"
                v-model:table_owner_value="table_owner_value"
            />
            <administration-modal-notifications
                v-bind:item="item"
                v-bind:table="table"
                v-bind:is_doing_something="is_doing_something"
                v-model:table_notification_value="table_notification_value"
                v-model:is_sending_notification="is_sending_notification"
                v-model:table_do_reminder_value="table_do_reminder_value"
                v-model:table_reminder_occurence_value="table_reminder_occurence_value"
                v-model:table_reminder_occurence_unit_value="table_reminder_occurence_unit_value"
                v-on:error-message="(message) => (error_message = message)"
                v-on:success-message="(message) => (success_message = message)"
            />
            <administration-modal-reviewers
                v-bind:item="item"
                v-bind:table="table"
                v-bind:is_doing_something="is_doing_something"
                v-model:table_reviewers_value="table_reviewers_value"
                v-model:table_reviewers_to_add_value="table_reviewers_to_add_value"
                v-model:table_reviewers_group_to_add_value="table_reviewers_group_to_add_value"
                v-model:is_sending_reminder="is_sending_reminder"
                v-on:error-message="(message) => (error_message = message)"
                v-on:success-message="(message) => (success_message = message)"
            />
        </div>
        <div v-else class="tlp-modal-body">
            <administration-modal-missing-table
                v-bind:item="item"
                v-bind:table="table"
                v-model:table_action_value="table_action_value"
            />
        </div>

        <div class="tlp-modal-footer">
            <button
                type="button"
                data-dismiss="modal"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                v-if="delete_confirmation"
                type="button"
                class="tlp-button-danger tlp-modal-action"
                v-bind:disabled="!delete_confirmation_enabled"
                v-on:click="onDeleteConfirmation"
                data-test="delete-confirmation-table-button"
            >
                <i class="fa-solid fa-trash tlp-button-icon" aria-hidden="true"></i>
                {{ $gettext("Confirm deletion?") }}
            </button>
            <button
                v-else
                type="button"
                class="tlp-button-danger tlp-button-outline tlp-modal-action"
                v-bind:disabled="is_doing_something"
                v-on:click="onDelete"
                data-test="delete-table-button"
            >
                <i
                    v-if="is_deleting"
                    class="tlp-button-icon fa-solid fa-spin fa-circle-notch"
                    aria-hidden="true"
                ></i>
                <i v-else class="fa-solid fa-trash tlp-button-icon" aria-hidden="true"></i>
                {{ $gettext("Delete") }}
            </button>
            <button
                type="button"
                class="tlp-button-primary tlp-modal-action"
                v-bind:disabled="is_doing_something || table_owner_value === null"
                v-on:click="
                    isTableLinkedToLastItemVersion(item, table)
                        ? onUpdate()
                        : onUpdateTableVersion()
                "
                data-test="update-table-button"
            >
                <i
                    v-if="is_updating"
                    class="tlp-button-icon fa-solid fa-spin fa-circle-notch"
                    aria-hidden="true"
                ></i>
                <i v-else class="fa-solid fa-floppy-disk tlp-button-icon" aria-hidden="true"></i>
                {{ $gettext("Update") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal, EVENT_TLP_MODAL_HIDDEN } from "@tuleap/tlp-modal";
import type {
    ApprovableDocument,
    ApprovalTable,
    ApprovalTableReviewer,
    Item,
    UserGroup,
} from "../../../type";
import type { User } from "@tuleap/core-rest-api-types";
import {
    deleteApprovalTable,
    patchApprovalTable,
    updateApprovalTable,
} from "../../../api/approval-table-rest-querier";
import AdministrationModalGlobalSettings from "./AdministrationModalGlobalSettings.vue";
import AdministrationModalNotifications from "./AdministrationModalNotifications.vue";
import AdministrationModalReviewers from "./AdministrationModalReviewers.vue";
import { isTableLinkedToLastItemVersion } from "../../../helpers/approval-table-helper";
import AdministrationModalMissingTable from "./AdministrationModalMissingTable.vue";

const props = defineProps<{
    trigger: HTMLButtonElement;
    table: ApprovalTable;
    item: Item & ApprovableDocument;
}>();

const emit = defineEmits<{
    (e: "refresh-data"): void;
}>();

const modal_div = ref<HTMLDivElement>();
const modal = ref<Modal | null>(null);
const error_message = ref<string>("");
const success_message = ref<string>("");
const is_deleting = ref<boolean>(false);
const delete_confirmation = ref<boolean>(false);
const delete_confirmation_enabled = ref<boolean>(false);
const is_updating = ref<boolean>(false);
const is_sending_notification = ref<boolean>(false);
const is_sending_reminder = ref<boolean>(false);
const table_owner_value = ref<User | null>(props.table.table_owner);
const table_status_value = ref<string>(props.table.state);
const table_comment_value = ref<string>(props.table.description);
const table_notification_value = ref<string>(props.table.notification_type);
const table_do_reminder_value = ref<boolean>(props.table.reminder_occurence !== 0);
const table_reminder_occurence_value = ref<number>(props.table.reminder_occurence);
const table_reminder_occurence_unit_value = ref<string>("day");
const table_reviewers_value = ref<Array<ApprovalTableReviewer>>([...props.table.reviewers]);
const table_reviewers_to_add_value = ref<Array<User>>([]);
const table_reviewers_group_to_add_value = ref<Array<UserGroup>>([]);
const table_action_value = ref<string>("copy");

const is_doing_something = computed(
    () =>
        is_deleting.value ||
        is_updating.value ||
        is_sending_notification.value ||
        is_sending_reminder.value,
);

onMounted(() => {
    if (modal_div.value === undefined) {
        throw Error("Failed to create administration modal");
    }

    modal.value = createModal(modal_div.value, {
        destroy_on_hide: false,
        keyboard: true,
        dismiss_on_backdrop_click: true,
    });
    props.trigger.addEventListener("click", () => modal.value?.show());
    modal.value?.addEventListener(EVENT_TLP_MODAL_HIDDEN, () => {
        delete_confirmation.value = false;
        delete_confirmation_enabled.value = false;
    });
});

onUnmounted(() => {
    modal.value?.destroy();
});

function onDelete(): void {
    delete_confirmation.value = true;
    setTimeout(() => {
        delete_confirmation_enabled.value = true;
    }, 1000);
}

function onDeleteConfirmation(): void {
    delete_confirmation.value = false;
    delete_confirmation_enabled.value = false;
    is_deleting.value = true;
    deleteApprovalTable(props.item.id).match(
        () => {
            emit("refresh-data");
            is_deleting.value = false;
            modal.value?.hide();
        },
        (fault) => {
            is_deleting.value = false;
            error_message.value = fault.toString();
        },
    );
}

function onUpdate(): void {
    if (table_owner_value.value === null) {
        throw Error("This should not happen");
    }
    is_updating.value = true;
    updateApprovalTable(
        props.item.id,
        table_owner_value.value.id,
        table_status_value.value,
        table_comment_value.value,
        table_notification_value.value,
        table_reviewers_value.value.map((reviewer) => reviewer.user.id),
        table_reviewers_to_add_value.value.map((user) => user.id),
        table_reviewers_group_to_add_value.value.map((user_group) => {
            if (user_group.id.includes("_")) {
                // We assume that user group id is something like 102_3, we just need 3
                return Number.parseInt(user_group.id.split("_")[1], 10);
            }
            return Number.parseInt(user_group.id, 10);
        }),
        table_do_reminder_value.value
            ? table_reminder_occurence_value.value *
                  (table_reminder_occurence_unit_value.value === "day" ? 1 : 7)
            : 0,
    ).match(
        () => {
            emit("refresh-data");
            is_updating.value = false;
            modal.value?.hide();
        },
        (fault) => {
            is_updating.value = false;
            error_message.value = fault.toString();
        },
    );
}

function onUpdateTableVersion(): void {
    is_updating.value = true;
    patchApprovalTable(props.item.id, table_action_value.value).match(
        () => {
            emit("refresh-data");
            is_updating.value = false;
            modal.value?.hide();
        },
        (fault) => {
            is_updating.value = false;
            error_message.value = fault.toString();
        },
    );
}
</script>
