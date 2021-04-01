/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { parseNatureLabels } from "./nature-labels-from-mountpoint";
import { createVueGettextProviderPassthrough } from "./vue-gettext-provider-for-test";

describe("parseNatureLabels", () => {
    it("Returns only 'no nature' if no data attribute", async () => {
        const doc = document.implementation.createHTMLDocument();
        const div = doc.createElement("div");

        const labels = await parseNatureLabels(div, createVueGettextProviderPassthrough());

        expect(labels.size).toBe(1);
        expect(labels.get("")).toBe("Linked to");
    });

    it("Adds a custom nature to the collection", async () => {
        const doc = document.implementation.createHTMLDocument();
        const div = doc.createElement("div");
        div.dataset.visibleNatures = '[{"shortname": "duck", "forward_label": "Coin"}]';
        const labels = await parseNatureLabels(div, createVueGettextProviderPassthrough());

        expect(labels.size).toBe(2);
        expect(labels.get("")).toBe("Linked to");
        expect(labels.get("duck")).toBe("Coin");
    });
});
