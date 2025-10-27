/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import DOMPurify from "dompurify";
import { get } from "@tuleap/tlp-fetch";

export function initiatePermissionsChange(): void {
    const select = document.getElementById("package_id");
    const container = document.getElementById("permissions_list");
    if (container === null || !(select instanceof HTMLSelectElement)) {
        return;
    }

    select.addEventListener("change", () => {
        get(
            "frsajax.php?group_id=" +
                encodeURIComponent(select.dataset.projectId || 0) +
                "&action=permissions_frs_package&package_id=" +
                encodeURIComponent(select.value),
        )
            .then((response: Response) => response.text())
            .then((html) => {
                container.innerText = "";
                container.insertAdjacentHTML("afterbegin", DOMPurify.sanitize(html));
            });
    });
}
