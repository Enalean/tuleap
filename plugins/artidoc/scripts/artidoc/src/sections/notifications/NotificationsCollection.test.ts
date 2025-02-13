/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { describe, it, expect, beforeEach } from "vitest";
import type {
    Notification,
    NotificationsCollection,
} from "@/sections/notifications/NotificationsCollection";
import { buildNotificationsCollection } from "@/sections/notifications/NotificationsCollection";

const notification = {
    type: "warning",
    message: "Please don't use stores in your application",
} as Notification;

describe("NotificationsCollection", () => {
    let collection: NotificationsCollection;

    beforeEach(() => {
        collection = buildNotificationsCollection();
    });

    it("addNotification() should add the provided notification to the collection", () => {
        collection.addNotification(notification);

        expect(collection.messages.value).toHaveLength(1);
        expect(collection.messages.value[0]).toStrictEqual(notification);
    });

    it("deleteNotification() should remove the notification from the collection", () => {
        collection.addNotification(notification);
        collection.deleteNotification(notification);

        expect(collection.messages.value).toHaveLength(0);
    });
});
