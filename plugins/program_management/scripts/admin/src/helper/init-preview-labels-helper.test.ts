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
import * as previewActualizer from "../milestones/preview-actualizer";
import { RetrieveElementStub } from "../dom/RetrieveElementStub";

const createDocument = (): Document => document.implementation.createHTMLDocument();

jest.mock("../milestones/preview-actualizer");
describe("init-preview-labels-helper", () => {
    describe("initPreviewTrackerLabels", () => {
        const gettext_provider: GettextProvider = { gettext: (s) => s };
        it("Do not init preview when program increment label element does not exist", function () {
            const doc = createDocument();
            const init_preview = jest.spyOn(previewActualizer, "initPreview");

            initPreviewTrackerLabels(doc, gettext_provider, RetrieveElementStub.withElements());
            expect(init_preview).not.toHaveBeenCalled();
        });
        it("Init preview is called when program increment label element exists", function () {
            const doc = createDocument();
            const init_preview = jest.spyOn(previewActualizer, "initPreview");

            const program_increment_label = document.createElement("input");
            program_increment_label.id = "admin-configuration-program-increment-label-section";
            const program_increment_sub_label = document.createElement("input");

            doc.body.appendChild(program_increment_label);

            initPreviewTrackerLabels(
                doc,
                gettext_provider,
                RetrieveElementStub.withElements(
                    program_increment_label,
                    program_increment_sub_label
                )
            );
            expect(init_preview).toHaveBeenCalled();
        });
    });
});
