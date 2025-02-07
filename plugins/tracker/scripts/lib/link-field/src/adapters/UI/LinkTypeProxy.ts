/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { LinkType } from "../../domain/links/LinkType";
import { FORWARD_DIRECTION, REVERSE_DIRECTION } from "../../domain/links/LinkType";

export const LinkTypeProxy = {
    fromChangeEvent: (event: Event): LinkType | null => {
        if (!(event.target instanceof HTMLSelectElement)) {
            return null;
        }
        const [shortname, direction] = event.target.value.split(" ");
        if (direction !== FORWARD_DIRECTION && direction !== REVERSE_DIRECTION) {
            return null;
        }
        return {
            direction,
            shortname,
            label: event.target.selectedOptions[0].label,
        };
    },
};
