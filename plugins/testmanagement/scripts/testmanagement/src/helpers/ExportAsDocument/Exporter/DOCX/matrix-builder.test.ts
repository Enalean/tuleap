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

import { buildTraceabilityMatrix } from "./matrix-builder";
import type { IContext } from "docx";
import { createGettextProviderPassthrough } from "../../../create-gettext-provider-passthrough-for-tests";

describe("matrix-builder", () => {
    describe("buildTraceabilityMatrix", () => {
        it("should display requirement in a table", () => {
            const gettext_provider = createGettextProviderPassthrough();

            const section = buildTraceabilityMatrix(
                {
                    name: "Tuleap 13.4",
                    backlog: [],
                    traceability_matrix: [
                        {
                            requirement: {
                                id: 1,
                                title: "Lorem",
                                tracker_id: 111,
                            },
                            tests: new Map([
                                [
                                    11,
                                    {
                                        id: 11,
                                        title: "Test A",
                                        campaign: "Tuleap 13.4",
                                        status: "passed",
                                        executed_by: "John Doe",
                                        executed_on: "Today",
                                        executed_on_date: new Date(),
                                    },
                                ],
                            ]),
                        },
                        {
                            requirement: {
                                id: 2,
                                title: "Ipsum",
                                tracker_id: 111,
                            },
                            tests: new Map([
                                [
                                    22,
                                    {
                                        id: 22,
                                        title: "Test B",
                                        campaign: "Tuleap 13.4",
                                        status: "passed",
                                        executed_by: "John Doe",
                                        executed_on: "Today",
                                        executed_on_date: new Date(),
                                    },
                                ],
                            ]),
                        },
                    ],
                    tests: [],
                },
                gettext_provider,
            );

            const tree = JSON.stringify(section[1].prepForXml({} as IContext));
            expect(tree).toContain("Lorem");
            expect(tree).toContain("Ipsum");
        });

        it("should not display a table if there isn't any requirements", () => {
            const gettext_provider = createGettextProviderPassthrough();

            const section = buildTraceabilityMatrix(
                {
                    name: "Tuleap 13.4",
                    backlog: [],
                    traceability_matrix: [],
                    tests: [],
                },
                gettext_provider,
            );

            const tree = section[1].prepForXml({} as IContext);
            expect(JSON.stringify(tree)).toContain(
                "There isn't any requirements to put in the traceability matrix.",
            );
        });
    });
});
