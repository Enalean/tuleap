/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import * as mark_deprecation_acknowledgement from "./mark-deprecation-acknowledgement";
import { displayBrowserDeprecationModalIfNeeded } from "./browser-deprecation-modal";

describe("browser-deprecation-modal", () => {
    it("Shows the modal when the deprecation is not marked as already seen", () => {
        jest.spyOn(
            mark_deprecation_acknowledgement,
            "markAndCheckBrowserDeprecationAcknowledgement",
        ).mockReturnValue(false);
        const show_modal = jest.fn();
        const show_non_dismissible_modal = jest.fn();

        displayBrowserDeprecationModalIfNeeded(
            createDocumentWithModal(),
            show_modal,
            show_non_dismissible_modal,
            localStorage,
        );
        expect(show_modal).toHaveBeenCalled();
        expect(show_non_dismissible_modal).not.toHaveBeenCalled();
    });

    it("Does not show the modal when the deprecation is not marked as already seen", () => {
        jest.spyOn(
            mark_deprecation_acknowledgement,
            "markAndCheckBrowserDeprecationAcknowledgement",
        ).mockReturnValue(true);
        const show_modal = jest.fn();
        const show_non_dismissible_modal = jest.fn();

        displayBrowserDeprecationModalIfNeeded(
            createDocumentWithModal(),
            show_modal,
            show_non_dismissible_modal,
            localStorage,
        );
        expect(show_modal).not.toHaveBeenCalled();
        expect(show_non_dismissible_modal).not.toHaveBeenCalled();
    });

    it("Always shows the non dismissible modal", () => {
        jest.spyOn(
            mark_deprecation_acknowledgement,
            "markAndCheckBrowserDeprecationAcknowledgement",
        ).mockReturnValue(false);
        const show_modal = jest.fn();
        const show_non_dismissible_modal = jest.fn();

        displayBrowserDeprecationModalIfNeeded(
            createDocumentWithNonDismissibleModal(),
            show_modal,
            show_non_dismissible_modal,
            localStorage,
        );
        expect(show_non_dismissible_modal).toHaveBeenCalled();
        expect(show_modal).not.toHaveBeenCalled();
    });

    it("Throws an error when the modal cannot be found in the DOM", () => {
        const mount_point = document.implementation.createHTMLDocument();
        expect(() =>
            displayBrowserDeprecationModalIfNeeded(mount_point, jest.fn(), jest.fn(), localStorage),
        ).toThrow();
    });

    function createDocumentWithModal(): Document {
        const local_document = document.implementation.createHTMLDocument();
        const modal = local_document.createElement("div");
        modal.setAttribute("id", "browser-deprecation-modal");
        local_document.body.appendChild(modal);
        return local_document;
    }

    function createDocumentWithNonDismissibleModal(): Document {
        const local_document = document.implementation.createHTMLDocument();
        const modal = local_document.createElement("div");
        modal.setAttribute("id", "browser-deprecation-modal");
        modal.setAttribute("data-non-dismissible", "");
        local_document.body.appendChild(modal);
        return local_document;
    }
});
