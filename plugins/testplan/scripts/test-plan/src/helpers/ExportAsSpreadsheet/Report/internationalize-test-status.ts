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

import type { TestStats } from "../../BacklogItems/compute-test-stats";
import type { VueGettextProvider } from "../../vue-gettext-provider";

export function getInternationalizedTestStatus(
    gettext_provider: VueGettextProvider,
    test_status: keyof TestStats | null,
): string {
    switch (test_status) {
        case null:
            return "";
        case "failed":
            return gettext_provider.$gettext("Failed");
        case "blocked":
            return gettext_provider.$gettext("Blocked");
        case "notrun":
            return gettext_provider.$gettext("Not run");
        case "passed":
            return gettext_provider.$gettext("Passed");
        default:
            return ((val: never): never => val)(test_status);
    }
}
