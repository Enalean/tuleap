/*
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

import { synchronizeTeamOfProgram } from "../api/synchronize-team";
import type { DocumentAdapter } from "../dom/DocumentAdapter";

export function initSynchronizeTeamButtons(doc: DocumentAdapter, location: Location): void {
    const buttons_to_synchronize = doc.getElementsByClassName(
        "program-management-admin-action-synchronize-button",
    );

    [].forEach.call(buttons_to_synchronize, function (button_to_synchronize: HTMLElement) {
        if (!button_to_synchronize || !(button_to_synchronize instanceof HTMLButtonElement)) {
            throw new Error("Button to synchronize team does not exist");
        }

        button_to_synchronize.addEventListener("click", async (event) => {
            event.preventDefault();

            await triggerTeamSynchronization(button_to_synchronize, location);
        });
    });
}

export async function triggerTeamSynchronization(
    button_to_synchronize: HTMLButtonElement,
    location: Location,
): Promise<void> {
    if (!button_to_synchronize.dataset.projectLabel || !button_to_synchronize.dataset.teamId) {
        return;
    }

    await synchronizeTeamOfProgram(
        button_to_synchronize.dataset.projectLabel,
        parseInt(button_to_synchronize.dataset.teamId, 10),
    );

    location.reload();
}
