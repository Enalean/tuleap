/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import { initPreviewTrackerLabels } from "./init-preview-labels-helper";
import type { GettextProvider } from "../GettextProvider";
import { DocumentAdapter } from "../dom/DocumentAdapter";

const createDocument = (): Document => document.implementation.createHTMLDocument();

describe("init-preview-labels-helper", () => {
    describe("initPreviewTrackerLabels", () => {
        const gettext_provider: GettextProvider = { gettext: (s) => s };
        it("Do not init preview when program increment label element does not exist", function () {
            const doc = createDocument();

            expect(() =>
                initPreviewTrackerLabels(new DocumentAdapter(doc), gettext_provider),
            ).not.toThrow();
        });
    });
});
