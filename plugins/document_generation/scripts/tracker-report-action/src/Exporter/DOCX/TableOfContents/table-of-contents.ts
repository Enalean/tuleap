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

/**
 * This mainly an extracted from https://github.com/dolanmiu/docx/blob/6.0.3/src/file/table-of-contents/table-of-contents.ts
 */

import type { ITableOfContentsOptions } from "docx";
import {
    InternalHyperlink,
    LeaderType,
    PageReference,
    Paragraph,
    Run,
    TabStopPosition,
    TabStopType,
    TextRun,
    XmlAttributeComponent,
    XmlComponent,
} from "docx";
import { TOCFieldInstruction } from "./toc-field-instruction";
import type {
    FormattedArtifact,
    ArtifactFieldValueStepDefinitionContent,
} from "@tuleap/plugin-docgen-docx";
import { getAnchorToArtifactContent } from "@tuleap/plugin-docgen-docx";
import { ComplexFieldCharacter } from "../base-elements";

class AliasAttributes extends XmlAttributeComponent<{ readonly alias: string }> {
    protected override readonly xmlKeys = { alias: "w:val" };
}

class Alias extends XmlComponent {
    constructor(alias: string) {
        super("w:alias");
        this.root.push(new AliasAttributes({ alias }));
    }
}

export class TableOfContentsPrefilled extends XmlComponent {
    public constructor(
        artifacts: ReadonlyArray<FormattedArtifact<ArtifactFieldValueStepDefinitionContent>>,
        properties?: ITableOfContentsOptions,
    ) {
        super("w:sdt");

        this.root.push(this.buildStructuredDocumentTagProperties());

        const content = new (class extends XmlComponent {
            public constructor() {
                super("w:sdtContent");
            }
        })();
        content.addChildElement(this.buildBeginParagraphTOC(properties));
        for (const link of this.buildPrefilledTOC(artifacts)) {
            content.addChildElement(link);
        }
        content.addChildElement(this.buildEndParagraphTOC());

        this.root.push(content);
    }

    private buildStructuredDocumentTagProperties(): XmlComponent {
        return new (class extends XmlComponent {
            public constructor() {
                super("w:sdtPr");
                this.root.push(new Alias("TOC"));
            }
        })();
    }

    private buildBeginParagraphTOC(properties?: ITableOfContentsOptions): Paragraph {
        return new Paragraph({
            children: [
                new Run({
                    children: [
                        new ComplexFieldCharacter("begin", true),
                        new TOCFieldInstruction(properties),
                        new ComplexFieldCharacter("separate"),
                    ],
                }),
            ],
        });
    }

    private buildEndParagraphTOC(): Paragraph {
        return new Paragraph({
            children: [
                new Run({
                    children: [new ComplexFieldCharacter("end")],
                }),
            ],
        });
    }

    private buildPrefilledTOC(
        artifacts: ReadonlyArray<FormattedArtifact<ArtifactFieldValueStepDefinitionContent>>,
    ): ReadonlyArray<Paragraph> {
        const links_to_content = [];

        for (const artifact of artifacts) {
            const artifact_anchor = getAnchorToArtifactContent(artifact);
            const link = new InternalHyperlink({
                children: [new TextRun(`${artifact.title}\t`)],
                anchor: artifact_anchor,
            });
            links_to_content.push(
                new Paragraph({
                    children: [link, new PageReference(artifact_anchor)],
                    tabStops: [
                        {
                            type: TabStopType.RIGHT,
                            position: TabStopPosition.MAX,
                            leader: LeaderType.DOT,
                        },
                    ],
                }),
            );
        }

        return links_to_content;
    }
}
