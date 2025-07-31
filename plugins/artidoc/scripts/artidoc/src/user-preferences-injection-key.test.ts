/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import { buildUserPreferences } from "@/user-preferences-injection-key";

describe("user-preferences-injection-key", () => {
    it("buildUserPreferences should returns corresponding informations", () => {
        const doc = document.implementation.createHTMLDocument();
        const mount_point = doc.createElement("div");

        doc.body.setAttribute("data-user-locale", "fr_FR");
        doc.body.setAttribute("data-user-timezone", "Europe/Paris");
        mount_point.setAttribute("data-relative-date-display", "absolute_first-relative_tooltip");

        expect(buildUserPreferences(doc, mount_point)).toStrictEqual({
            locale: "fr_FR",
            timezone: "Europe/Paris",
            relative_date_display: "absolute_first-relative_tooltip",
        });
    });
});
