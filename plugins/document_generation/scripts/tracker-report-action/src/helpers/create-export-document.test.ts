/*
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

import { createExportDocument } from "./create-export-document";

describe("Create ArtifactValues Collection", () => {
    it("Transforms json content into a collection", () => {
        const report_artifacts = [
            {
                values: [
                    {
                        field_id: 1,
                        type: "aid",
                        label: "Artifact Number",
                        value: 1001,
                    },
                    {
                        field_id: 1,
                        type: "whatever",
                        label: "What Ever",
                        value: 9999,
                    },
                ],
            },
            {
                values: [
                    {
                        field_id: 1,
                        type: "aid",
                        label: "Artifact Number",
                        value: 1002,
                    },
                ],
            },
        ];

        const report = createExportDocument(report_artifacts);
        const collection = report.fields;

        expect(collection.length).toEqual(2);
        expect(collection[0].field_name).toEqual("Artifact Number");
        expect(collection[0].field_value).toEqual(1001);
        expect(collection[1].field_name).toEqual("Artifact Number");
        expect(collection[1].field_value).toEqual(1002);
    });
});
