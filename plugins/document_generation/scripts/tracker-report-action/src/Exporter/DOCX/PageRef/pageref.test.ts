/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import { PageRef } from "./pageref";
import type { IContext } from "docx";

describe("PageRef", () => {
    it("builds a page reference", () => {
        const page_ref = new PageRef("refA");
        const tree = page_ref.prepForXml({} as IContext);

        expect(tree).toStrictEqual({
            "w:r": [
                {
                    "w:fldChar": {
                        _attr: {
                            "w:dirty": true,
                            "w:fldCharType": "begin",
                        },
                    },
                },
                {
                    "w:instrText": [
                        {
                            _attr: {
                                "xml:space": "preserve",
                            },
                        },
                        "PAGEREF refA",
                    ],
                },
                {
                    "w:fldChar": {
                        _attr: {
                            "w:fldCharType": "end",
                        },
                    },
                },
            ],
        });
    });
});
