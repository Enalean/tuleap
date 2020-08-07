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

import { createVueGettextProviderPassthrough } from "../vue-gettext-provider-for-test";
import { createExportReport } from "./report-creator";
import * as general_information_builder from "./general-information-builder";
import { GeneralSection } from "./general-information-builder";
import { TextCell } from "./report-cells";

describe("Create an export report", () => {
    it("generates the report", () => {
        const gettext_provider = createVueGettextProviderPassthrough();
        jest.spyOn(general_information_builder, "buildGeneralSection").mockReturnValue(({
            rows: [[new TextCell("General section")]],
        } as unknown) as GeneralSection);

        const report = createExportReport(gettext_provider, "Project", "Milestone");

        expect(report).toStrictEqual({
            sections: [{ rows: [[new TextCell("General section")]] }],
        });
    });
});
