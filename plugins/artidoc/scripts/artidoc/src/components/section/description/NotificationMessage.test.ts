/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { describe, expect, it, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import NotificationMessage, {
    type NotificationMessageProps,
} from "@/components/section/description/NotificationMessage.vue";
import type { NotificationType } from "@/sections/notifications/NotificationsCollection";

const default_props: NotificationMessageProps = {
    notification: {
        message: "message",
        type: "info",
    },
    delete_notification: vi.fn(),
};

function getWrapper(props: NotificationMessageProps): VueWrapper {
    return shallowMount(NotificationMessage, {
        global: {
            plugins: [createGettext({ silent: true })],
        },
        props,
    });
}

describe("NotificationMessage", () => {
    it.each<[string, NotificationType]>([
        ["error", "danger"],
        ["info", "info"],
        ["success", "success"],
        ["warning", "warning"],
    ])("should display a %s message", (expected_type_of_message, notification_type) => {
        const wrapper = getWrapper({
            ...default_props,
            notification: {
                ...default_props.notification,
                type: notification_type,
            },
        });

        const notification_element = wrapper.find(`.closable-notification`);
        expect(notification_element.exists()).toBe(true);
        expect(notification_element.classes(`tlp-alert-${notification_type}`)).toBe(true);

        const notification_message = notification_element.find("[data-test=notification-message]");
        expect(notification_message.text()).toBe("message");
    });

    it("should delete the notification after 5 seconds", () => {
        vi.useFakeTimers();

        const mocked_delete_notification = vi.fn();
        getWrapper({
            ...default_props,
            delete_notification: mocked_delete_notification,
        });

        vi.advanceTimersByTime(5_000);

        expect(mocked_delete_notification).toHaveBeenCalledOnce();

        vi.useRealTimers();
    });

    it("When the cross button is clicked, then it should remove the notification", () => {
        const mocked_delete_notification = vi.fn();
        const wrapper = getWrapper({
            ...default_props,
            delete_notification: mocked_delete_notification,
        });

        wrapper.find("[data-test=close-notification-button]").trigger("click");

        expect(mocked_delete_notification).toHaveBeenCalledOnce();
    });
});
