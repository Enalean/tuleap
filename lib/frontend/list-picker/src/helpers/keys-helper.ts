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

export function isEscapeKey(event: KeyboardEvent): boolean {
    return event.key === "Escape" || event.key === "Esc" || event.keyCode === 27;
}

export function isBackspaceKey(event: KeyboardEvent): boolean {
    return event.key === "Backspace" || event.keyCode === 8;
}

export function isEnterKey(event: KeyboardEvent): boolean {
    return event.key === "Enter" || event.keyCode === 13;
}

export function isArrowDown(event: KeyboardEvent): boolean {
    return event.key === "ArrowDown" || event.keyCode === 40;
}

export function isArrowUp(event: KeyboardEvent): boolean {
    return event.key === "ArrowUp" || event.keyCode === 38;
}

export function isTabKey(event: KeyboardEvent): boolean {
    return event.key === "Tab" || event.keyCode === 9;
}

export function isShiftKey(event: KeyboardEvent): boolean {
    return event.key === "Shift" || event.keyCode === 16;
}
