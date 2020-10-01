/*
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

import { GetText } from "../../../../../src/scripts/tuleap/gettext/gettext-init";
import { sprintf } from "sprintf-js";

export function replaceTrackerIDCallback(clicked_button: HTMLElement): string {
    if (!clicked_button.dataset.trackerId) {
        throw new Error("Missing data-tracker-id attribute on button");
    }

    return clicked_button.dataset.trackerId;
}

export function buildDeletionDescriptionCallback(gettext_provider: GetText) {
    return (clicked_button: HTMLElement): string => {
        if (!clicked_button.dataset.trackerName) {
            throw new Error("Missing data-tracker-name attribute on button");
        }
        return sprintf(
            gettext_provider.gettext(
                "You are about to delete the tracker %s. Please, confirm your action."
            ),
            clicked_button.dataset.trackerName
        );
    };
}
