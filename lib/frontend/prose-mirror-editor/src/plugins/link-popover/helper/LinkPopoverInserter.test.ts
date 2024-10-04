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

import { describe, it, expect, beforeEach, afterEach, vi } from "vitest";
import { createLocalDocument, gettext_provider } from "../../../helpers";
import { LinkPopoverInserter } from "./LinkPopoverInserter";
import { FindDOMNodeAtPositionStub } from "./stubs/FindDOMNodeAtPositionStub";
import { CrossReferenceHTMLElementDetector } from "./CrossReferenceNodeDetector";
import { CrossReferenceUrlExtractor } from "./CrossReferenceUrlExtractor";
import { ExtractLinkPropertiesStub } from "../../../helpers/stubs/ExtractLinkPropertiesStub";

import * as popover_creator from "./create-link-popover";
import type { ExtractLinkProperties } from "../../../helpers/LinkPropertiesExtractor";
import type { CheckEmptySelection } from "./EmptySelectionChecker";
import { EmptySelectionCheckerStub } from "./stubs/EmptySelectionCheckerStub";
import { BuildRemoveLinkCallbackStub } from "./stubs/BuildRemoveLinkCallbackStub";
import { BuildEditLinkCallbackStub } from "./stubs/BuildEditLinkCallbackStub";
import { BuildEditCrossReferenceCallbackStub } from "./stubs/BuildEditCrossReferenceCallbackStub";

const editor_id = "aaaa-bbbb-cccc-dddd";
const position = 1;
const remove_link_callback = (): void => {
    // Do nothing
};

const edit_link_callback = (): void => {
    // Do nothing
};

const edit_cross_reference_callback = (): void => {
    // Do nothing
};

describe("LinkPopoverInserter", () => {
    let doc: Document, check_empty_selection: CheckEmptySelection;

    beforeEach(() => {
        doc = createLocalDocument();
        check_empty_selection = EmptySelectionCheckerStub.withEmptySelection();

        vi.spyOn(popover_creator, "removePopover");
    });

    const insertPopover = (
        node: Node,
        editor_link_url_extractor: ExtractLinkProperties,
    ): boolean => {
        return LinkPopoverInserter(
            doc,
            gettext_provider,
            editor_id,
            check_empty_selection,
            FindDOMNodeAtPositionStub.withNode(node),
            CrossReferenceHTMLElementDetector(),
            CrossReferenceUrlExtractor(),
            editor_link_url_extractor,
            BuildRemoveLinkCallbackStub.withCallback(remove_link_callback),
            BuildEditLinkCallbackStub.withCallback(edit_link_callback),
            BuildEditCrossReferenceCallbackStub.withCallback(edit_cross_reference_callback),
        ).insertPopover(position);
    };

    afterEach(() => {
        expect(popover_creator.removePopover).toHaveBeenCalledOnce();
        expect(popover_creator.removePopover).toHaveBeenCalledWith(doc, editor_id);
    });

    it("When the selection is not empty, then it should return false", () => {
        const node = doc.createTextNode("I'm a text node");
        check_empty_selection = EmptySelectionCheckerStub.withoutEmptySelection();

        expect(insertPopover(node, ExtractLinkPropertiesStub.withoutLinkProperties())).toBe(false);
    });

    it("When the Node found at the given position has no parentElement, then it should return false", () => {
        const node = doc.createTextNode("I'm a text node");

        expect(insertPopover(node, ExtractLinkPropertiesStub.withoutLinkProperties())).toBe(false);
    });

    it("When no url can be found, then it should return false", () => {
        const node_text = doc.createTextNode("I'm a text node");
        const node = doc.createElement("span");
        node.appendChild(node_text);

        expect(insertPopover(node_text, ExtractLinkPropertiesStub.withoutLinkProperties())).toBe(
            false,
        );
    });

    it("When the parent element of the DOM node found at the given position is a cross reference node, then it should insert a popover for cross references and return true", () => {
        const cross_reference_text = doc.createTextNode("art #123");
        const cross_reference_url = "https://example.com/";
        const cross_reference = doc.createElement("span");

        cross_reference.setAttribute("data-href", cross_reference_url);
        cross_reference.appendChild(cross_reference_text);

        const insertCrossReferenceLinkPopover = vi.spyOn(
            popover_creator,
            "insertCrossReferenceLinkPopover",
        );

        expect(
            insertPopover(cross_reference_text, ExtractLinkPropertiesStub.withoutLinkProperties()),
        ).toBe(true);

        expect(insertCrossReferenceLinkPopover).toHaveBeenCalledOnce();
        expect(insertCrossReferenceLinkPopover).toHaveBeenCalledWith(
            doc,
            gettext_provider,
            cross_reference,
            editor_id,
            {
                href: cross_reference_url,
                title: cross_reference_text.textContent,
            },
            edit_cross_reference_callback,
        );
    });

    it("When the DOM node found at the given position is a regular link node, then it should insert a popover for regular links and return true", () => {
        const link_properties = {
            href: "https://example.com/",
            title: "See example",
        };
        const link_text = doc.createTextNode(link_properties.title);
        const link = doc.createElement("a");

        link.setAttribute("href", link_properties.href);
        link.appendChild(link_text);

        const insertLinkPopover = vi.spyOn(popover_creator, "insertLinkPopover");

        expect(
            insertPopover(link_text, ExtractLinkPropertiesStub.withLinkProperties(link_properties)),
        ).toBe(true);

        expect(insertLinkPopover).toHaveBeenCalledOnce();

        expect(insertLinkPopover).toHaveBeenCalledWith(
            doc,
            gettext_provider,
            link,
            editor_id,
            link_properties,
            remove_link_callback,
            edit_link_callback,
        );
    });
});
