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

import { describe, it, expect, beforeEach } from "vitest";
import { EditorState } from "prosemirror-state";
import { LinkStateBuilder } from "./LinkStateBuilder";
import { CheckIsMarkTypeRepeatedInSelectionStub } from "../../../helpers/stubs/CheckIsMarkTypeRepeatedInSelectionStub";
import { ExtractLinkPropertiesStub } from "../../../helpers/stubs/ExtractLinkPropertiesStub";
import { LinkState } from "./LinkState";
import { custom_schema } from "../../../custom_schema";

describe("LinkStateBuilder", () => {
    let state: EditorState;

    beforeEach(() => {
        state = EditorState.create({
            schema: custom_schema,
        });
    });

    it("should return a disabled state when there are several links in the current selection", () => {
        const builder = LinkStateBuilder(
            CheckIsMarkTypeRepeatedInSelectionStub.withSameMarkRepeatedInSelection(),
            ExtractLinkPropertiesStub.withoutLinkProperties(),
        );

        expect(builder.build(state)).toStrictEqual(LinkState.disabled());
    });

    it("should return a state for a link edition when there is a link at the current cursor position", () => {
        const link_properties = {
            href: "https://example.com",
            title: "See example",
        };

        const builder = LinkStateBuilder(
            CheckIsMarkTypeRepeatedInSelectionStub.withoutSameMarkRepeatedInSelection(),
            ExtractLinkPropertiesStub.withLinkProperties(link_properties),
        );

        expect(builder.build(state)).toStrictEqual(LinkState.forLinkEdition(link_properties));
    });

    it("should return a state for a link creation when there is no link at the current cursor position", () => {
        const builder = LinkStateBuilder(
            CheckIsMarkTypeRepeatedInSelectionStub.withoutSameMarkRepeatedInSelection(),
            ExtractLinkPropertiesStub.withoutLinkProperties(),
        );

        expect(builder.build(state)).toStrictEqual(LinkState.forLinkCreation(""));
    });
});
