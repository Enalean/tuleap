/*
 *  Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { type Ref, ref } from "vue";

export type NotificationType = "success" | "info" | "warning" | "danger";
export type Notification = {
    message: string;
    type: NotificationType;
};
export type NotificationsCollection = {
    messages: Ref<Notification[]>;
    addNotification(notification: Notification): void;
    deleteNotification(notification: Notification): void;
};

export function buildNotificationsCollection(): NotificationsCollection {
    const messages: Ref<Notification[]> = ref([]);

    function deleteNotification(notification: Notification): void {
        const index = messages.value.indexOf(notification);
        if (index < 0) {
            return;
        }
        messages.value.splice(index, 1);
    }
    function addNotification(notification: Notification): void {
        messages.value.push(notification);
    }
    return { messages, addNotification, deleteNotification };
}
