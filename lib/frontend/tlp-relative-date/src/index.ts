/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { RelativeDateElement } from "./relative-date-element";

export { RelativeDateElement } from "./relative-date-element";
export type { FirstDateShown, OtherDatePlacement } from "./relative-date-element";
export type { RelativeDatesDisplayPreference } from "./relative-date-helper";
export {
    PREFERENCE_ABSOLUTE_FIRST_RELATIVE_SHOWN,
    PREFERENCE_ABSOLUTE_FIRST_RELATIVE_TOOLTIP,
    PREFERENCE_CHOICES,
    PREFERENCE_RELATIVE_FIRST_ABSOLUTE_SHOWN,
    PREFERENCE_RELATIVE_FIRST_ABSOLUTE_TOOLTIP,
    getRelativeDateUserPreferenceOrThrow,
    relativeDatePlacement,
    relativeDatePreference,
} from "./relative-date-helper";

if (!window.customElements.get("tlp-relative-date")) {
    window.customElements.define("tlp-relative-date", RelativeDateElement);
}
