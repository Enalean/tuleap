/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
import type { StreamParser, StringStream, TagStyle } from "@codemirror/language";
import {
    bracketMatching,
    HighlightStyle,
    LanguageSupport,
    StreamLanguage,
    syntaxHighlighting,
} from "@codemirror/language";
import type { Completion } from "@codemirror/autocomplete";
import syntax from "@tuleap/tql-syntax";
import { Tag } from "@lezer/highlight";

export interface TQLParserDefinitionItem {
    readonly pattern: RegExp;
    readonly token: string;
}

export type TQLParserDefinition = ReadonlyArray<TQLParserDefinitionItem>;

const TQL_parser_definition: TQLParserDefinition = [
    {
        pattern: syntax.double_quote_string.pattern,
        token: "tql-string",
    },
    {
        pattern: syntax.simple_quote_string.pattern,
        token: "tql-string",
    },
    {
        pattern: syntax.time_period.pattern,
        token: "tql-time-period",
    },
    {
        pattern: syntax.number.pattern,
        token: "tql-number",
    },
    {
        pattern: syntax.structure.pattern,
        token: "tql-structure",
    },
    {
        pattern: syntax.function.pattern,
        token: "tql-keyword",
    },
    {
        pattern: syntax.operator.pattern,
        token: "tql-operator",
    },
    {
        pattern: /[(]/,
        token: "tql-operator",
    },
    {
        pattern: /[)]/,
        token: "tql-operator",
    },
    {
        pattern: syntax.atvariable.pattern,
        token: "tql-atvariable",
    },
    {
        pattern: syntax.variable.pattern,
        token: "tql-variable",
    },
];

export interface TQLDefinition {
    autocomplete: ReadonlyArray<Completion | string>;
    parser_definition: TQLParserDefinition;
}

export function buildParserDefinition(
    additional_keywords: ReadonlyArray<string> = [],
): TQLParserDefinition {
    if (additional_keywords.length > 0) {
        const keywords_pattern = additional_keywords.join("|");
        const additional_keywords_definition = {
            pattern: new RegExp("(?:" + keywords_pattern + ")\\b", "i"),
            token: "variable-2",
        };
        return [...TQL_parser_definition, additional_keywords_definition];
    }
    return TQL_parser_definition;
}

function createTQLStreamParser(definition: TQLParserDefinition): StreamParser<never> {
    const token_table: { [key: string]: Tag } = {};
    definition.forEach(function ({ token }): void {
        token_table[token] = Tag.define(token);
    });

    return {
        name: "tql",
        token(stream: StringStream): string | null {
            for (const key in definition) {
                const { pattern, token } = definition[key];
                if (pattern !== undefined && stream.match(pattern)) {
                    return token;
                }
            }
            stream.next();
            return null;
        },
        tokenTable: token_table,
    };
}

export function TQLLanguageSupport(definition: TQLDefinition): LanguageSupport {
    const tql_parser = createTQLStreamParser(definition.parser_definition);
    const tql_language = StreamLanguage.define(tql_parser);
    const tql_highlight = HighlightStyle.define(
        definition.parser_definition.map(function ({ token }): TagStyle {
            return {
                tag: tql_parser.tokenTable
                    ? tql_parser.tokenTable[token] ?? Tag.define(token)
                    : Tag.define(token),
                class: `cm-${token}`,
            };
        }),
        { scope: tql_language },
    );
    return new LanguageSupport(tql_language, [
        bracketMatching({ brackets: "()" }),
        syntaxHighlighting(tql_highlight),
    ]);
}

export const TQL_autocomplete_keywords: ReadonlyArray<string> = [
    "AND",
    "OR",
    "BETWEEN(",
    "NOW()",
    "IN(",
    "NOT",
    "MYSELF()",
    "WITH PARENT",
    "WITH PARENT ARTIFACT",
    "WITH PARENT TRACKER",
    "WITH TYPE",
    "WITHOUT PARENT",
    "WITHOUT PARENT ARTIFACT",
    "WITHOUT PARENT TRACKER",
    "WITH CHILDREN",
    "WITH CHILDREN ARTIFACT",
    "WITH CHILDREN TRACKER",
    "WITHOUT CHILDREN",
    "WITHOUT CHILDREN ARTIFACT",
    "WITHOUT CHILDREN TRACKER",
    "IS LINKED FROM",
    "IS LINKED FROM ARTIFACT",
    "IS LINKED FROM TRACKER",
    "IS NOT LINKED FROM",
    "IS NOT LINKED FROM ARTIFACT",
    "IS NOT LINKED FROM TRACKER",
    "IS LINKED TO",
    "IS LINKED TO ARTIFACT",
    "IS LINKED TO TRACKER",
    "IS NOT LINKED TO",
    "IS NOT LINKED TO ARTIFACT",
    "IS NOT LINKED TO TRACKER",
    "IS COVERED",
    "IS COVERED BY ARTIFACT",
    "IS NOT COVERED",
    "IS NOT COVERED BY ARTIFACT",
    "IS COVERING",
    "IS COVERING ARTIFACT",
    "IS NOT COVERING",
    "IS NOT COVERING ARTIFACT",
];
