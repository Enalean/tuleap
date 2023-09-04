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

import { describe, it, expect } from "vitest";
import { TableOfContentsPrefilled } from "./table-of-contents";
import type { IContext } from "docx";
import type {
    FormattedArtifact,
    ArtifactFieldValueStepDefinitionContent,
} from "@tuleap/plugin-docgen-docx";
import { EMPTY_TOC, TOC_WITH_CONTENT } from "./table-of-contents-test-samples";

describe("Table of contents", () => {
    it("builds an empty TOC", () => {
        const toc = new TableOfContentsPrefilled([]);
        const tree = toc.prepForXml({} as IContext);

        expect(tree).toStrictEqual(EMPTY_TOC);
    });

    it("builds a TOC prefilled with artifacts information", () => {
        const toc = new TableOfContentsPrefilled([
            buildFakeFormattedArtifact(123, "Some title #123"),
            buildFakeFormattedArtifact(987, "Some other title #987"),
        ]);
        const tree = toc.prepForXml({} as IContext);

        expect(tree).toStrictEqual(TOC_WITH_CONTENT);
    });
});

function buildFakeFormattedArtifact(
    id: number,
    title: string,
): FormattedArtifact<ArtifactFieldValueStepDefinitionContent> {
    return { id, title } as FormattedArtifact<ArtifactFieldValueStepDefinitionContent>;
}
