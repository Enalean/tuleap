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

import { createVueGettextProviderPassthrough } from "../../vue-gettext-provider-for-test";
import { createExportReport } from "./report-creator";
import * as general_information_builder from "./Section/general-information-builder";
import * as requirements_builder from "./Section/requirements-builder";
import * as test_results_builder from "./Section/test-results-builder";
import * as justifications_builder from "./Section/justifications-builder";
import type { GeneralSection } from "./Section/general-information-builder";
import { TextCell } from "@tuleap/plugin-docgen-xlsx";
import type { RequirementsSection } from "./Section/requirements-builder";
import type { TestResultsSection } from "./Section/test-results-builder";
import type { JustificationsSection } from "./Section/justifications-builder";

describe("Create an export report", () => {
    it("generates the report", async () => {
        const gettext_provider = createVueGettextProviderPassthrough();
        jest.spyOn(general_information_builder, "buildGeneralSection").mockReturnValue({
            rows: [[new TextCell("General section")]],
        } as unknown as GeneralSection);
        jest.spyOn(requirements_builder, "buildRequirementsSection").mockResolvedValue({
            rows: [[new TextCell("Requirements section")]],
        } as unknown as RequirementsSection);
        jest.spyOn(test_results_builder, "buildTestResultsSection").mockReturnValue({
            rows: [[new TextCell("Test results section")]],
        } as unknown as TestResultsSection);
        jest.spyOn(justifications_builder, "buildJustificationsSection").mockResolvedValue({
            rows: [[new TextCell("Justifications section")]],
        } as unknown as JustificationsSection);

        const report = await createExportReport(
            gettext_provider,
            "Project",
            "Milestone",
            "Real Name",
            new Date(2020),
            [],
            [],
        );

        expect(report).toStrictEqual({
            sections: [
                { rows: [[new TextCell("General section")]] },
                { rows: [[new TextCell("Requirements section")]] },
                { rows: [[new TextCell("Test results section")]] },
                { rows: [[new TextCell("Justifications section")]] },
            ],
        });
    });
});
