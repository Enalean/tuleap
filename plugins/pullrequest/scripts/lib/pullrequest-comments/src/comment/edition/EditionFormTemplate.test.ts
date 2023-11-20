/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { describe, beforeEach, it, expect, vi } from "vitest";
import { selectOrThrow } from "@tuleap/dom";
import { GettextProviderStub } from "../../../tests/stubs/GettextProviderStub";
import { EditionFormPresenterStub } from "../../../tests/stubs/EditionFormPresenterStub";
import { ControlEditionFormStub } from "../../../tests/stubs/ControlEditionFormStub";
import type { HostElement } from "./EditionForm";
import type { ControlEditionForm } from "./EditionFormController";
import { getEditionForm } from "./EditionFormTemplate";

const getRenderedEditionForm = (host: HostElement): ShadowRoot => {
    const doc = document.implementation.createHTMLDocument();
    const target = doc.createElement("div") as unknown as ShadowRoot;
    const render = getEditionForm(host, GettextProviderStub);

    render(host, target);

    return target;
};

describe("EditionFormTemplate", () => {
    let controller: ControlEditionForm;

    beforeEach(() => {
        controller = ControlEditionFormStub();
    });

    describe("The [Cancel] button", () => {
        it("should be disabled when the edited comment is being submitted", () => {
            const edition_form = getRenderedEditionForm({
                controller,
                presenter: EditionFormPresenterStub.withSubmittedComment(),
            } as HostElement);

            expect(
                selectOrThrow(edition_form, "[data-test=button-cancel-edition]").hasAttribute(
                    "disabled",
                ),
            ).toBe(true);
        });

        it("should NOT be disabled when the edited is NOT being submitted", () => {
            const edition_form = getRenderedEditionForm({
                controller,
                presenter: EditionFormPresenterStub.buildInitial(),
            } as HostElement);

            expect(
                selectOrThrow(edition_form, "[data-test=button-cancel-edition]").hasAttribute(
                    "disabled",
                ),
            ).toBe(false);
        });

        it("should close the edition mode when it is clicked", () => {
            const cancelEdition = vi.spyOn(controller, "cancelEdition");
            const edition_form = getRenderedEditionForm({
                controller,
                presenter: EditionFormPresenterStub.buildInitial(),
            } as HostElement);

            selectOrThrow(edition_form, "[data-test=button-cancel-edition]").click();

            expect(cancelEdition).toHaveBeenCalledOnce();
        });
    });

    describe("The [Save] button", () => {
        it("should be disabled when the edited comment is being submitted", () => {
            const edition_form = getRenderedEditionForm({
                controller,
                presenter: EditionFormPresenterStub.withSubmittedComment(),
            } as HostElement);

            expect(
                selectOrThrow(edition_form, "[data-test=button-save-edition]").hasAttribute(
                    "disabled",
                ),
            ).toBe(true);
        });

        it("should be disabled when the edited comment cannot be submitted", () => {
            const edition_form = getRenderedEditionForm({
                controller,
                presenter: EditionFormPresenterStub.withSubmitForbidden(),
            } as HostElement);

            expect(
                selectOrThrow(edition_form, "[data-test=button-save-edition]").hasAttribute(
                    "disabled",
                ),
            ).toBe(true);
        });

        it("should have a spinner when the edited comment is being submitted", () => {
            const edition_form = getRenderedEditionForm({
                controller,
                presenter: EditionFormPresenterStub.withSubmittedComment(),
            } as HostElement);

            expect(
                selectOrThrow(edition_form, "[data-test=edited-comment-being-saved-spinner]"),
            ).toBeDefined();
        });

        it("should save the comment when the user clicks it", () => {
            const saveEditedContent = vi.spyOn(controller, "saveEditedContent");
            const edition_form = getRenderedEditionForm({
                controller,
                presenter: EditionFormPresenterStub.withSubmitPossible(),
            } as HostElement);

            selectOrThrow(edition_form, "[data-test=button-save-edition]").click();

            expect(saveEditedContent).toHaveBeenCalledOnce();
        });
    });
});
