/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import { buildInitialTestsList, buildCategory, buildTest } from "./edit-campaign-model-builder.js";
import { UNCATEGORIZED } from "../../definition/definition-constants.js";

describe("Edit campaign model builder -", () => {
    describe("buildInitialTestsList()", () => {
        it("Given a list of test definitions, then it will return an object with each definition category as a key and each definition of that category as an entry of a 'tests' property. The categories will be sorted alphabetically.", () => {
            const first_definition = {
                id: 32,
                category: UNCATEGORIZED,
            };
            const second_definition = {
                id: 428,
                category: "aristocratic",
            };

            const result = buildInitialTestsList([first_definition, second_definition], []);

            expect(result).toEqual({
                aristocratic: {
                    label: "aristocratic",
                    tests: {
                        428: {
                            definition: second_definition,
                            execution: null,
                            selected: false,
                        },
                    },
                },
                Uncategorized: {
                    label: UNCATEGORIZED,
                    tests: {
                        32: {
                            definition: first_definition,
                            execution: null,
                            selected: false,
                        },
                    },
                },
            });
        });

        it("Given a lists of test definitions and of test executions, then it will assign the definition's tests object and set it selected", () => {
            const first_definition = {
                id: 64,
                category: "misattribution",
            };
            const second_definition = {
                id: 21,
                category: UNCATEGORIZED,
            };
            const third_definition = {
                id: 37,
                category: "undecayableness",
            };
            const first_execution = {
                id: 233,
                definition: first_definition,
            };
            const second_execution = {
                id: 123,
                definition: second_definition,
            };

            const result = buildInitialTestsList(
                [first_definition, second_definition, third_definition],
                [first_execution, second_execution],
            );

            expect(result).toEqual({
                misattribution: {
                    label: "misattribution",
                    tests: {
                        64: {
                            definition: first_definition,
                            execution: first_execution,
                            selected: true,
                        },
                    },
                },
                Uncategorized: {
                    label: UNCATEGORIZED,
                    tests: {
                        21: {
                            definition: second_definition,
                            execution: second_execution,
                            selected: true,
                        },
                    },
                },
                undecayableness: {
                    label: "undecayableness",
                    tests: {
                        37: {
                            definition: third_definition,
                            execution: null,
                            selected: false,
                        },
                    },
                },
            });
        });

        it("Given a test definition with a category and a test execution with a different changeset of the definition with a different category, then it will still return the definition as selected", () => {
            const definition = {
                id: 70,
                category: "unrewarding",
            };

            const execution = {
                id: 149,
                definition: {
                    id: 70,
                    category: "agyria",
                },
            };

            const result = buildInitialTestsList([definition], [execution]);

            expect(result).toEqual({
                unrewarding: {
                    label: "unrewarding",
                    tests: {
                        70: {
                            definition,
                            execution,
                            selected: true,
                        },
                    },
                },
            });
        });
    });

    describe("buildCategory()", () => {
        it("Given a test category, then it will return an object with the category as label and an empty tests property", () => {
            const category = "paucinervate";

            expect(buildCategory(category)).toEqual({
                tests: {},
                label: category,
            });
        });
    });

    describe("buildTest()", () => {
        it("Given a definition, an execution, and a 'selected' boolean, then it will return an object with those properties", () => {
            const definition = { id: 67 };
            const execution = { id: 91 };
            const selected = true;

            expect(buildTest(definition, execution, selected)).toEqual({
                definition,
                execution,
                selected,
            });
        });
    });
});
