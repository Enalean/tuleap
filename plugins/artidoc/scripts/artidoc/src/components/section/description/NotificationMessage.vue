<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
    <div
        v-if="is_displayed"
        class="closable-notification"
        v-bind:class="`tlp-alert-${notification.type}`"
    >
        <span class="notification-message" data-test="notification-message">{{
            notification.message
        }}</span>
        <button
            data-test="close-notification-button"
            class="button-close-notification"
            type="button"
            v-on:click="removeNotification()"
            v-bind:aria-label="$gettext('Close')"
        >
            <i class="fa-solid fa-xmark" role="img"></i>
        </button>
    </div>
</template>
<script setup lang="ts">
import { ref } from "vue";
import { useGettext } from "vue3-gettext";
import type {
    Notification,
    NotificationsCollection,
} from "@/sections/notifications/NotificationsCollection";

const { $gettext } = useGettext();

export type NotificationMessageProps = {
    notification: Notification;
    delete_notification: NotificationsCollection["deleteNotification"];
};

const props = defineProps<NotificationMessageProps>();
const is_displayed = ref(true);

const removeNotification = (): void => {
    is_displayed.value = false;
    props.delete_notification(props.notification);
};

setTimeout(removeNotification, 5_000);
</script>
<style lang="scss" scoped>
.closable-notification {
    display: flex;
    width: 25rem;
}

.notification-message {
    flex: 1;
}

.button-close-notification {
    height: 0.875rem;
    margin: 4px 0 0;
    padding: 0;
    border: unset;
    background: unset;
    color: unset;
    font-size: 0.875rem;
    text-align: unset;

    &:hover {
        opacity: 0.5;
        cursor: pointer;
    }
}
</style>
