/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import GanttBoard from "./GanttBoard.vue";
import type { Task } from "../../type";
import GanttTask from "./Task/GanttTask.vue";
import TimePeriodMonth from "./TimePeriod/TimePeriodMonth.vue";

window.ResizeObserver =
    window.ResizeObserver ||
    jest.fn().mockImplementation(() => ({
        disconnect: jest.fn(),
        observe: jest.fn(),
        unobserve: jest.fn(),
    }));

describe("GanttBoard", () => {
    const windowResizeObserver = window.ResizeObserver;

    afterEach(() => {
        window.ResizeObserver = windowResizeObserver;
    });

    it("Displays all tasks", () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                tasks: [{ id: 1 }, { id: 2 }, { id: 3 }] as Task[],
                locale: "en_US",
            },
        });

        expect(wrapper.findAllComponents(GanttTask).length).toBe(3);
    });

    it("Displays months according to tasks", async () => {
        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                tasks: [
                    { id: 1, start: new Date(2020, 3, 15) },
                    { id: 2, start: new Date(2020, 3, 20) },
                ] as Task[],
                locale: "en_US",
            },
        });

        wrapper.setData({
            now: new Date(2020, 3, 15),
        });
        await wrapper.vm.$nextTick();

        const time_period = wrapper.findComponent(TimePeriodMonth);
        expect(time_period.exists()).toBe(true);
        expect(
            time_period.props("months").map((month: Date) => month.toDateString())
        ).toStrictEqual(["Wed Apr 01 2020", "Fri May 01 2020"]);
    });

    it("Observes the resize of the time period", () => {
        const observe = jest.fn();
        const mockResizeObserver = jest.fn();
        mockResizeObserver.mockReturnValue({
            observe,
        });
        window.ResizeObserver = mockResizeObserver;

        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                tasks: [
                    { id: 1, start: new Date(2020, 3, 15) },
                    { id: 2, start: new Date(2020, 3, 20) },
                ] as Task[],
                locale: "en_US",
            },
        });

        const time_period = wrapper.findComponent(TimePeriodMonth);
        expect(time_period.exists()).toBe(true);
        expect(observe).toHaveBeenCalledWith(time_period.element);
    });

    it("Fills the empty space of additional months if the user resize the viewport", async () => {
        const observe = jest.fn();
        const mockResizeObserver = jest.fn();
        mockResizeObserver.mockReturnValue({
            observe,
        });
        window.ResizeObserver = mockResizeObserver;

        const wrapper = shallowMount(GanttBoard, {
            propsData: {
                tasks: [
                    { id: 1, start: new Date(2020, 3, 15) },
                    { id: 2, start: new Date(2020, 3, 20) },
                ] as Task[],
                locale: "en_US",
            },
        });

        wrapper.setData({
            now: new Date(2020, 3, 15),
        });
        await wrapper.vm.$nextTick();

        const time_period = wrapper.findComponent(TimePeriodMonth);
        expect(time_period.exists()).toBe(true);
        expect(
            time_period.props("months").map((month: Date) => month.toDateString())
        ).toStrictEqual(["Wed Apr 01 2020", "Fri May 01 2020"]);

        const observerCallback = mockResizeObserver.mock.calls[0][0];
        await observerCallback([
            ({
                contentRect: { width: 450 } as DOMRectReadOnly,
                target: time_period.element,
            } as unknown) as ResizeObserverEntry,
        ]);

        expect(
            time_period.props("months").map((month: Date) => month.toDateString())
        ).toStrictEqual([
            "Wed Apr 01 2020",
            "Fri May 01 2020",
            "Mon Jun 01 2020",
            "Wed Jul 01 2020",
        ]);
    });
});
