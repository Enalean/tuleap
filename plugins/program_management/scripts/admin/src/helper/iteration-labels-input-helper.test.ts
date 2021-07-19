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
import {
    disableInputLabelIterationWhenNoSelectedIterationTracker,
    enableInputLabelIterationWhenAnIterationTrackerIsSelected,
} from "./iteration-labels-input-helper";

const createDocument = (): Document => document.implementation.createHTMLDocument();

describe("iteration-labels-input-helper", () => {
    describe("enableInputLabelIterationWhenAnIterationTrackerIsSelected", () => {
        it("input element classes has not tlp-form-element-disabled and is not disabled", function () {
            const label = document.createElement("input");
            label.id = "admin-configuration-iteration-label-section";
            label.disabled = true;
            const sub_label = document.createElement("input");
            sub_label.id = "admin-configuration-iteration-sub-label-section";
            sub_label.disabled = false;

            const div_label = document.createElement("div");
            div_label.classList.add("tlp-form-element-disabled");
            div_label.appendChild(label);

            const div_sub_label = document.createElement("div");
            div_sub_label.classList.add("tlp-form-element-disabled");
            div_sub_label.appendChild(sub_label);

            const doc = createDocument();
            doc.body.appendChild(div_label);
            doc.body.appendChild(div_sub_label);

            enableInputLabelIterationWhenAnIterationTrackerIsSelected(doc);

            expect(div_label.classList).not.toContain("tlp-form-element-disabled");
            expect(div_sub_label.classList).not.toContain("tlp-form-element-disabled");
            expect(label.disabled).toBeFalsy();
            expect(sub_label.disabled).toBeFalsy();
        });
    });

    describe("disableInputLabelIterationWhenNoSelectedIterationTracker", () => {
        it("input element classes has tlp-form-element-disabled and becomes disabled", function () {
            const label = document.createElement("input");
            label.id = "admin-configuration-iteration-label-section";
            const sub_label = document.createElement("input");
            sub_label.id = "admin-configuration-iteration-sub-label-section";

            const div_label = document.createElement("div");
            div_label.appendChild(label);

            const div_sub_label = document.createElement("div");
            div_sub_label.appendChild(sub_label);

            const doc = createDocument();
            doc.body.appendChild(div_label);
            doc.body.appendChild(div_sub_label);

            disableInputLabelIterationWhenNoSelectedIterationTracker(doc);

            expect(div_label.classList).toContain("tlp-form-element-disabled");
            expect(div_sub_label.classList).toContain("tlp-form-element-disabled");
            expect(label.disabled).toBeTruthy();
            expect(sub_label.disabled).toBeTruthy();
        });
    });
});
