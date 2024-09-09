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
import NotificationMessage, {
    type NotificationMessageProps,
} from "@/components/section/description/NotificationMessage.vue";

const default_props: NotificationMessageProps = {
    notification: {
        message: "message",
        type: "info",
    },
    delete_notification: vi.fn(),
};

function getWrapper(props: NotificationMessageProps): VueWrapper {
    return shallowMount(NotificationMessage, {
        props,
    });
}

describe("NotificationMessage", () => {
    it("should display an error message", () => {
        const wrapper = getWrapper({
            ...default_props,
            notification: {
                ...default_props.notification,
                type: "danger",
            },
        });
        const progressElement = wrapper.find('span[class="tlp-alert-danger"]');

        expect(progressElement.exists()).toBe(true);
        expect(progressElement.text()).toBe("message");
    });
    it("should display an info message", () => {
        const wrapper = getWrapper({
            ...default_props,
            notification: {
                ...default_props.notification,
                type: "info",
            },
        });
        const progressElement = wrapper.find('span[class="tlp-alert-info"]');

        expect(progressElement.exists()).toBe(true);
        expect(progressElement.text()).toBe("message");
    });
    it("should display a success message", () => {
        const wrapper = getWrapper({
            ...default_props,
            notification: {
                ...default_props.notification,
                type: "success",
            },
        });
        const progressElement = wrapper.find('span[class="tlp-alert-success"]');

        expect(progressElement.exists()).toBe(true);
        expect(progressElement.text()).toBe("message");
    });
    it("should display a warning message", () => {
        const wrapper = getWrapper({
            ...default_props,
            notification: {
                ...default_props.notification,
                type: "warning",
            },
        });
        const progressElement = wrapper.find('span[class="tlp-alert-warning"]');

        expect(progressElement.exists()).toBe(true);
        expect(progressElement.text()).toBe("message");
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
});
