/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { describe, it, expect } from "vitest";
import { buildDOMSerializer } from "./DomSerializer";
import { buildCustomSchema } from "../../custom_schema";

const project_id = 120;

describe("DomSerializer", () => {
    it("should not serialize async-cross-reference marks", () => {
        const schema = buildCustomSchema();
        const serializer = buildDOMSerializer(schema);

        const reference = "art #123";

        const part_without_reference = schema.text("This document references ");
        const part_with_reference = schema
            .text(reference)
            .mark([schema.marks.async_cross_reference.create({ text: reference, project_id })]);

        const paragraph = schema.nodes.paragraph.create({}, [
            part_without_reference,
            part_with_reference,
        ]);

        const doc = schema.nodes.doc.create({}, [paragraph]);

        const dom = serializer.serializeDOM(doc.content);
        expect(dom.querySelector("p")?.innerHTML).toBe("This document references art #123");
    });
});
