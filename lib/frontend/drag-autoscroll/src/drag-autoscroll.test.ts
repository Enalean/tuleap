/*
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import { describe, beforeEach, it, expect, vi, type MockInstance } from "vitest";
import {
    getDragAutoscroll,
    calculateScrollSpeed,
    SCROLL_ZONE_SIZE,
    MAX_SCROLL_SPEED,
    MIN_SCROLL_SPEED,
    type DragAutoscroll,
} from "./drag-autoscroll";

describe("drag-autoscroll", () => {
    describe("calculateScrollSpeed()", () => {
        it("should return MAX_SCROLL_SPEED when distance is 0 (at the edge)", () => {
            expect(calculateScrollSpeed(0, SCROLL_ZONE_SIZE)).toBe(MAX_SCROLL_SPEED);
        });

        it("should return MIN_SCROLL_SPEED when distance is at the limit of the zone", () => {
            expect(calculateScrollSpeed(SCROLL_ZONE_SIZE, SCROLL_ZONE_SIZE)).toBe(MIN_SCROLL_SPEED);
        });

        it("should return an intermediate speed when inside the zone", () => {
            const distance = SCROLL_ZONE_SIZE / 2;
            const expected_speed = (MAX_SCROLL_SPEED + MIN_SCROLL_SPEED) / 2;
            expect(calculateScrollSpeed(distance, SCROLL_ZONE_SIZE)).toBe(expected_speed);
        });
    });

    describe("DragAutoscroll Instance", () => {
        let drag_autoscroll: DragAutoscroll;
        let add_event_listener_spy: MockInstance;
        let remove_event_listener_spy: MockInstance;
        let scroll_by_mock: MockInstance;

        const track_pointer_event = "pointermove";

        beforeEach(() => {
            vi.useFakeTimers();

            Object.defineProperty(window, "innerHeight", { writable: true, value: 1000 });

            scroll_by_mock = vi.spyOn(window, "scrollBy").mockImplementation((): void => {});

            add_event_listener_spy = vi.spyOn(document, "addEventListener");
            remove_event_listener_spy = vi.spyOn(document, "removeEventListener");

            drag_autoscroll = getDragAutoscroll(window, track_pointer_event);
        });

        describe("Lifecycle & Events", () => {
            describe("start()", () => {
                it("should add the provided event listener", () => {
                    drag_autoscroll.start();
                    expect(add_event_listener_spy).toHaveBeenCalledWith(
                        track_pointer_event,
                        expect.any(Function),
                    );
                });

                it("should trigger scrolling loop when started in a scroll zone", () => {
                    drag_autoscroll.start();

                    const handle_pointer_move = add_event_listener_spy.mock.calls[0][1] as (event: {
                        clientY: number;
                    }) => void;
                    handle_pointer_move({ clientY: 0 });

                    vi.runOnlyPendingTimers();

                    expect(scroll_by_mock).toHaveBeenCalled();
                });

                it("should not double the scroll speed/calls if started twice", () => {
                    drag_autoscroll.start();
                    const handle_pointer_move = add_event_listener_spy.mock.calls[0][1] as (event: {
                        clientY: number;
                    }) => void;
                    handle_pointer_move({ clientY: 0 });

                    drag_autoscroll.start();

                    scroll_by_mock.mockClear();
                    vi.runOnlyPendingTimers();

                    expect(scroll_by_mock).toHaveBeenCalledTimes(1);
                });
            });

            describe("stop()", () => {
                it("should remove the exact same provided event listener that was added", () => {
                    drag_autoscroll.start();
                    const added_handler = add_event_listener_spy.mock.calls[0][1];

                    drag_autoscroll.stop();

                    expect(remove_event_listener_spy).toHaveBeenCalledWith(
                        track_pointer_event,
                        added_handler,
                    );
                });

                it("should stop scrolling even if time passes", () => {
                    drag_autoscroll.start();
                    vi.runOnlyPendingTimers();
                    scroll_by_mock.mockClear();

                    drag_autoscroll.stop();

                    vi.runOnlyPendingTimers();

                    expect(scroll_by_mock).not.toHaveBeenCalled();
                });
            });
        });

        describe("Scrolling Behavior", () => {
            let handle_pointer_move: (event: { clientY: number }) => void;

            beforeEach(() => {
                drag_autoscroll.start();
                handle_pointer_move = add_event_listener_spy.mock.calls[0][1] as (event: {
                    clientY: number;
                }) => void;
            });

            it("should NOT scroll when pointer is in the middle of the screen", () => {
                handle_pointer_move({ clientY: 500 });
                vi.runOnlyPendingTimers();

                expect(scroll_by_mock).not.toHaveBeenCalled();
            });

            it("should scroll UP (negative delta) when pointer is in the top zone", () => {
                handle_pointer_move({ clientY: 10 });
                vi.runOnlyPendingTimers();

                expect(scroll_by_mock).toHaveBeenCalled();
                const scroll_args = scroll_by_mock.mock.calls[0];
                expect(scroll_args[1]).toBeLessThan(0);
            });

            it("should scroll DOWN (positive delta) when pointer is in the bottom zone", () => {
                handle_pointer_move({ clientY: 990 });
                vi.runOnlyPendingTimers();

                expect(scroll_by_mock).toHaveBeenCalled();
                const scroll_args = scroll_by_mock.mock.calls[0];
                expect(scroll_args[1]).toBeGreaterThan(0);
            });
        });
    });
});
