/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

export function isEscapeKey(event: Event): event is KeyboardEvent {
    const keyboard_event = getKeyboardEvent(event);

    return keyboard_event.key === "Escape" || keyboard_event.key === "Esc";
}

export function isEnterKey(event: Event): event is KeyboardEvent {
    const keyboard_event = getKeyboardEvent(event);

    return keyboard_event.key === "Enter";
}

export function isArrowDown(event: Event): event is KeyboardEvent {
    const keyboard_event = getKeyboardEvent(event);

    return keyboard_event.key === "ArrowDown";
}

export function isArrowUp(event: Event): event is KeyboardEvent {
    const keyboard_event = getKeyboardEvent(event);

    return keyboard_event.key === "ArrowUp";
}

export function isTabKey(event: Event): event is KeyboardEvent {
    const keyboard_event = getKeyboardEvent(event);

    return keyboard_event.key === "Tab";
}

export function isShiftKey(event: Event): event is KeyboardEvent {
    const keyboard_event = getKeyboardEvent(event);

    return keyboard_event.key === "Shift";
}

function getKeyboardEvent(event: Event): KeyboardEvent {
    if (!(event instanceof KeyboardEvent)) {
        throw new Error("Event is not a keyboard event");
    }

    return event;
}
