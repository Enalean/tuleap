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
import { EditorState } from "prosemirror-state";
import { DOMParser } from "prosemirror-model";
import { EditorView } from "prosemirror-view";
import { buildCustomSchema } from "../../../custom_schema";
import { createLocalDocument } from "../../../helpers";
import { AllCrossReferencesLoader } from "./AllCrossReferencesLoader";

describe("AllCrossReferencesLoader", () => {
    it("Should detect all the cross references inside the document and insert async_cross_reference Marks", () => {
        const doc = createLocalDocument();
        const content = doc.createElement("div");

        content.insertAdjacentHTML(
            "afterbegin",
            `
            <p>
                This document references art #123, art #124 (followup of art #123) and also the following artifacts:
            </p>
            <ol>
                <li>
                    <p>art #100</p>
                    <ol>
                        <li>
                            <p>art #101</p>
                        </li>
                        <li>
                            <p>art #102</p>
                        </li>
                    </ol>
                </li>
            </ol>
            <blockquote><p>This quote has doc #123 in it</p></blockquote>
        `,
        );

        const schema = buildCustomSchema();
        const state = EditorState.create({
            doc: DOMParser.fromSchema(schema).parse(content),
            schema: schema,
        });
        const view = new EditorView(doc.createElement("div"), { state });

        view.dispatch(AllCrossReferencesLoader(state, 102).loadAllCrossReferences());

        expect(view.dom.innerHTML).toMatchInlineSnapshot(`
          <p>This document references <async-cross-reference>art #123</async-cross-reference>, <async-cross-reference>art #124</async-cross-reference> (followup of <async-cross-reference>art #123</async-cross-reference>) and also the following artifacts:</p>
          <ol>
            <li>
              <p><async-cross-reference>art #100</async-cross-reference></p>
              <ol>
                <li>
                  <p><async-cross-reference>art #101</async-cross-reference></p>
                </li>
                <li>
                  <p><async-cross-reference>art #102</async-cross-reference></p>
                </li>
              </ol>
            </li>
          </ol>
          <blockquote>
            <p>This quote has <async-cross-reference>doc #123</async-cross-reference> in it</p>
          </blockquote>
        `);
    });
});
