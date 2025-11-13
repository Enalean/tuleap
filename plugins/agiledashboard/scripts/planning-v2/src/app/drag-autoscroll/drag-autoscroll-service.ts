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

/* positive integer, must be greater than MAX_SCROLL_SPEED */
export const SCROLL_ZONE_SIZE = 100;
/* positive integer, must be greater than MIN_SCROLL_SPEED */
export const MAX_SCROLL_SPEED = 20;
/* positive integer */
export const MIN_SCROLL_SPEED = 5;

export interface IDragAutoscrollService {
    start(): void;
    stop(): void;
}

export function calculateScrollSpeed(distance_from_edge: number, zone_size: number): number {
    const clamped_distance = Math.max(0, Math.min(distance_from_edge, zone_size));
    const intensity = 1 - clamped_distance / zone_size;
    return MIN_SCROLL_SPEED + intensity * (MAX_SCROLL_SPEED - MIN_SCROLL_SPEED);
}

export function DragAutoscrollService(global_window: Window = window): IDragAutoscrollService {
    let animation_frame_id: number | null = null;
    let mouse_position_y: number | null = null;

    function handlePointerMove(event: PointerEvent): void {
        mouse_position_y = event.clientY;
    }

    function start(): void {
        global_window.document.addEventListener("pointermove", handlePointerMove);
        startScrollAnimation();
    }

    function stop(): void {
        global_window.document.removeEventListener("pointermove", handlePointerMove);
        stopScrollAnimation();
        mouse_position_y = null;
    }

    function startScrollAnimation(): void {
        if (animation_frame_id !== null) {
            return;
        }

        const animate = (): void => {
            performScroll();
            animation_frame_id = global_window.requestAnimationFrame(animate);
        };

        animation_frame_id = global_window.requestAnimationFrame(animate);
    }

    function performScroll(): void {
        if (mouse_position_y === null) {
            return;
        }

        const viewport_height = global_window.innerHeight;
        const effective_zone_size = Math.min(SCROLL_ZONE_SIZE, viewport_height / 3);
        const distance_from_top = mouse_position_y;
        const distance_from_bottom = viewport_height - mouse_position_y;

        let scroll_delta = 0;

        if (distance_from_top < effective_zone_size) {
            scroll_delta = -calculateScrollSpeed(distance_from_top, effective_zone_size);
        } else if (distance_from_bottom < effective_zone_size) {
            scroll_delta = calculateScrollSpeed(distance_from_bottom, effective_zone_size);
        }

        if (scroll_delta !== 0) {
            global_window.scrollBy(0, scroll_delta);
        }
    }

    function stopScrollAnimation(): void {
        if (animation_frame_id !== null) {
            global_window.cancelAnimationFrame(animation_frame_id);
            animation_frame_id = null;
        }
    }

    return {
        start,
        stop,
    };
}

export const drag_autoscroll_service = DragAutoscrollService();
