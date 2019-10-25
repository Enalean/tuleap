/*
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

import { createPopover, modal as createModal } from "tlp";

document.addEventListener("DOMContentLoaded", () => {
    const trigger = document.getElementById(
        "agiledashboard-administration-cannot-create-planning-popover-trigger"
    );

    if (trigger) {
        createPopover(
            trigger,
            document.getElementById("agiledashboard-administration-cannot-create-planning-popover")
        );
    }

    const button = document.getElementById("agiledashboard-administration-remove-planning-button");
    if (button) {
        const modal = createModal(document.getElementById(button.dataset.targetModalId));

        button.addEventListener("click", () => {
            modal.show();
        });
    }
});
