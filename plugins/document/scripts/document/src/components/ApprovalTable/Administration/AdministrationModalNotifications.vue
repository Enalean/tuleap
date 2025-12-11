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
    <h2 class="tlp-modal-subtitle">{{ $gettext("Notifications") }}</h2>
    <div class="tlp-form-element">
        <label class="tlp-label" for="notification-type">
            {{ $gettext("Notification type") }}
            <i
                class="fa-solid fa-circle-question"
                ref="popover_trigger"
                data-placement="right"
                data-trigger="hover"
            ></i>
        </label>
        <select
            id="notification-type"
            name="notification-type"
            class="tlp-select tlp-select-adjusted"
            v-model="table_notification_value"
            data-test="table-notification-select"
        >
            <option value="disabled">{{ $gettext("Disabled") }}</option>
            <option value="all_at_once">{{ $gettext("All at once") }}</option>
            <option value="sequential">{{ $gettext("Sequential") }}</option>
        </select>
        <notification-helper-popover v-if="popover_trigger" v-bind:trigger="popover_trigger" />
    </div>
    <button
        role="button"
        class="tlp-button-info tlp-button-outline tlp-button-mini send-notification-button"
        v-bind:disabled="is_doing_something"
        v-on:click="onSendNotification"
        data-test="send-notification-button"
    >
        <i
            v-if="is_sending_notification"
            class="tlp-button-icon fa-solid fa-spin fa-circle-notch"
            aria-hidden="true"
        ></i>
        <i v-else class="fa-solid fa-paper-plane tlp-button-icon" aria-hidden="true"></i>
        {{ $gettext("Send a mail reminder to approver(s)") }}
    </button>
    <div class="tlp-form-element">
        <label class="tlp-label tlp-checkbox">
            <input type="checkbox" name="do-reminder-occurence" v-model="table_do_reminder_value" />
            {{ $gettext("Send a mail reminder on occurence") }}
        </label>
    </div>
    <div v-if="table_do_reminder_value" class="reminder-setup">
        <div class="tlp-form-element">
            <label class="tlp-label" for="reminder-occurence">{{ $gettext("Every") }}</label>
            <input
                type="number"
                id="reminder-occurence"
                name="reminder-occurence"
                class="tlp-input"
                size="3"
                v-model="table_reminder_occurence_value"
            />
        </div>
        <div class="tlp-form-element">
            <label class="tlp-label" for="reminder-occurence-unit">
                {{ $gettext("Days/Weeks") }}
            </label>
            <select
                id="reminder-occurence-unit"
                name="reminder-occurence-unit"
                class="tlp-select tlp-select-adjusted"
                v-model="table_reminder_occurence_unit_value"
            >
                <option value="day">{{ $gettext("Days") }}</option>
                <option value="week">{{ $gettext("Weeks") }}</option>
            </select>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { ApprovalTable, Item } from "../../../type";
import { ref } from "vue";
import NotificationHelperPopover from "./NotificationHelperPopover.vue";
import { postApprovalTableReminder } from "../../../api/approval-table-rest-querier";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

const props = defineProps<{
    item: Item;
    table: ApprovalTable;
    is_doing_something: boolean;
}>();

const table_notification_value = defineModel<string>("table_notification_value", {
    required: true,
});
const table_do_reminder_value = defineModel<boolean>("table_do_reminder_value", { required: true });
const table_reminder_occurence_value = defineModel<number>("table_reminder_occurence_value", {
    required: true,
});
const table_reminder_occurence_unit_value = defineModel<string>(
    "table_reminder_occurence_unit_value",
    { required: true },
);
const is_sending_notification = defineModel<boolean>("is_sending_notification");

const emit = defineEmits<{
    (e: "error-message", message: string): void;
    (e: "success-message", message: string): void;
}>();

const popover_trigger = ref<HTMLElement>();

function onSendNotification(): void {
    is_sending_notification.value = true;
    postApprovalTableReminder(props.item.id).match(
        () => {
            is_sending_notification.value = false;
            emit("success-message", $gettext("Reminder sent with success"));
        },
        (fault) => {
            is_sending_notification.value = false;
            emit("error-message", fault.toString());
        },
    );
}
</script>

<style scoped lang="scss">
.send-notification-button {
    margin-bottom: var(--tlp-medium-spacing);
}

.reminder-setup {
    display: flex;
    gap: var(--tlp-medium-spacing);
}
</style>
