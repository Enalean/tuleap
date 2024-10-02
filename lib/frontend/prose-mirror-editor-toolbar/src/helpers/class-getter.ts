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
 *
 */

export interface ButtonClasses {
    [key: string]: boolean;
}

export function getClass(is_activated: boolean): ButtonClasses {
    return {
        "tlp-button-primary button-active": is_activated,
        "tlp-button-secondary": !is_activated,
        "prose-mirror-button": true,
        "tlp-button-primary": true,
        "tlp-button-outline": true,
    };
}
