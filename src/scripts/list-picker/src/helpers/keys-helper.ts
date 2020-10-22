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

    return (
        keyboard_event.key === "Escape" ||
        keyboard_event.key === "Esc" ||
        keyboard_event.keyCode === 27
    );
}

export function isBackspaceKey(event: Event): event is KeyboardEvent {
    const keyboard_event = getKeyboardEvent(event);

    return keyboard_event.key === "Backspace" || keyboard_event.keyCode === 8;
}

export function isEnterKey(event: Event): event is KeyboardEvent {
    const keyboard_event = getKeyboardEvent(event);

    return keyboard_event.key === "Enter" || keyboard_event.keyCode === 13;
}

export function isArrowDown(event: Event): event is KeyboardEvent {
    const keyboard_event = getKeyboardEvent(event);

    return keyboard_event.key === "ArrowDown" || keyboard_event.keyCode === 40;
}

export function isArrowUp(event: Event): event is KeyboardEvent {
    const keyboard_event = getKeyboardEvent(event);

    return keyboard_event.key === "ArrowUp" || keyboard_event.keyCode === 38;
}

function getKeyboardEvent(event: Event): KeyboardEvent {
    if (!(event instanceof KeyboardEvent)) {
        throw new Error("Event is not a keyboard event");
    }

    return event;
}
