/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import type { HostElement } from "./ArtifactCreatorElement";
import {
    ArtifactCreatorElement,
    observeIsLoading,
    onClickCancel,
    setErrorMessage,
} from "./ArtifactCreatorElement";
import { ArtifactCreatorController } from "../../../../../domain/fields/link-field/creation/ArtifactCreatorController";
import { DispatchEventsStub } from "../../../../../../tests/stubs/DispatchEventsStub";
import { setCatalog } from "../../../../../gettext-catalog";
import { selectOrThrow } from "@tuleap/dom";
import { RetrieveProjectsStub } from "../../../../../../tests/stubs/RetrieveProjectsStub";
import type { Project } from "../../../../../domain/Project";
import { Option } from "@tuleap/option";
import { LinkTypeStub } from "../../../../../../tests/stubs/LinkTypeStub";
import { CollectionOfAllowedLinksTypesPresenters } from "../CollectionOfAllowedLinksTypesPresenters";
import { RetrieveProjectTrackersStub } from "../../../../../../tests/stubs/RetrieveProjectTrackersStub";
import { en_US_LOCALE } from "@tuleap/core-constants";
import type { Tracker } from "../../../../../domain/Tracker";

describe(`ArtifactCreatorElement`, () => {
    let doc: Document;
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
        doc = document.implementation.createHTMLDocument();
    });

    describe(`events`, () => {
        let controller: ArtifactCreatorController, dispatchEvent: jest.SpyInstance;
        beforeEach(() => {
            const project: Project = { id: 144, label: "Next Omega" };
            controller = ArtifactCreatorController(
                DispatchEventsStub.buildNoOp(),
                RetrieveProjectsStub.withProjects(project),
                RetrieveProjectTrackersStub.withoutTracker(),
                en_US_LOCALE
            );
            dispatchEvent = jest.fn();
        });

        const getHost = (): HostElement => {
            const element = doc.createElement("span");
            return Object.assign(element, {
                controller,
                current_artifact_reference: Option.nothing(),
                available_types: CollectionOfAllowedLinksTypesPresenters.buildEmpty(),
                current_link_type: LinkTypeStub.buildUntyped(),
                is_loading: false,
                error_message: Option.nothing(),
                show_error_details: false,
                projects: [],
                trackers: [],
                content: () => element,
                dispatchEvent,
            }) as HostElement;
        };

        it(`when I click on the "Cancel" button, it will enable the modal submit
            and dispatch a "cancel" event`, () => {
            const host = getHost();
            const enableSubmit = jest.spyOn(controller, "enableSubmit");

            onClickCancel(host);

            expect(enableSubmit).toHaveBeenCalled();
            const event = dispatchEvent.mock.calls[0][0];
            expect(event.type).toBe("cancel");
        });

        it(`when is_loading becomes true, it will disable the modal submit`, () => {
            const host = getHost();
            const disableSubmit = jest.spyOn(controller, "disableSubmit");

            observeIsLoading(host, true);

            expect(disableSubmit).toHaveBeenCalled();
        });

        it(`when is_loading becomes false, it will enable the modal submit`, () => {
            const host = getHost();
            const enableSubmit = jest.spyOn(controller, "enableSubmit");

            observeIsLoading(host, false);

            expect(enableSubmit).toHaveBeenCalled();
        });
    });

    describe(`render`, () => {
        let is_loading: boolean, error_message: Option<string>, show_error_details: boolean;

        beforeEach(() => {
            is_loading = true;
            error_message = Option.nothing();
            show_error_details = false;
        });
        const render = (): HTMLElement => {
            const target = doc.createElement("div");
            const host = Object.assign(target, {
                controller: {} as ArtifactCreatorController,
                current_artifact_reference: Option.nothing(),
                available_types: CollectionOfAllowedLinksTypesPresenters.buildEmpty(),
                current_link_type: LinkTypeStub.buildUntyped(),
                is_loading,
                error_message,
                show_error_details,
                projects: [],
                trackers: [
                    {
                        id: 1,
                        label: "GT",
                        color_name: "deep-blue",
                        cannot_create_reason: "",
                    } as Tracker,
                    {
                        id: 2,
                        label: "Shelby",
                        color_name: "green",
                        cannot_create_reason: "",
                    } as Tracker,
                    {
                        id: 3,
                        label: "Mach-e",
                        color_name: "red",
                        cannot_create_reason: "Not a Mustang",
                    } as Tracker,
                    {
                        id: 4,
                        label: "Mach 1",
                        color_name: "red",
                        cannot_create_reason: "",
                    } as Tracker,
                ],
                content: () => target,
            }) as HostElement;
            const updateFunction = ArtifactCreatorElement.content(host);
            updateFunction(host, target as unknown as ShadowRoot);
            return target;
        };

        it(`when it is loading, it will disable inputs and buttons and will show a spinner icon`, () => {
            const target = render();
            const input = selectOrThrow(
                target,
                "[data-test=artifact-creator-title]",
                HTMLInputElement
            );
            const submit = selectOrThrow(
                target,
                "[data-test=artifact-creator-submit]",
                HTMLButtonElement
            );

            expect(input.disabled).toBe(true);
            expect(submit.disabled).toBe(true);
            expect(target.querySelector("[data-test=artifact-creator-spinner]")).not.toBeNull();
        });
        it(`disables the option when if the user cannot creates artifact`, () => {
            const target = render();
            const tracker_options = target.querySelectorAll(
                "[data-test=artifact-modal-link-creator-trackers-option]"
            ) as NodeListOf<HTMLOptionElement>;

            expect(tracker_options.item(0).disabled).toBe(false);
            expect(tracker_options.item(1).disabled).toBe(false);
            expect(tracker_options.item(2).disabled).toBe(true);
            expect(tracker_options.item(3).disabled).toBe(false);
        });
        it(`when there is an error, it will show it`, () => {
            error_message = Option.fromValue("Shtopp dat cart!");
            const target = render();
            expect(target.querySelector("[data-test=creation-error]")).not.toBeNull();
        });

        it(`when I have clicked on the error details, it will show the error message details`, () => {
            error_message = Option.fromValue("Shtopp dat cart!");
            show_error_details = true;
            const target = render();
            expect(target.querySelector("[data-test=creation-error-details]")).not.toBeNull();
        });
    });

    describe(`setters`, () => {
        let form: HTMLElement;

        beforeEach(() => {
            form = doc.createElement("div");
            form.setAttribute("data-form", "");
        });
        const getHost = (): HostElement => {
            const target = doc.createElement("div");
            target.append(form);
            return Object.assign(target, {
                content: () => target,
            }) as unknown as HostElement;
        };

        it(`defaults error message to Nothing`, () => {
            const result = setErrorMessage(getHost(), undefined);
            expect(result.isNothing()).toBe(true);
        });

        it(`scrolls the form into view when given an actual error message`, () => {
            const host = getHost();
            const scrollIntoView = jest.fn();
            Object.assign(form, { scrollIntoView });
            const option = Option.fromValue("Error message");

            const result = setErrorMessage(host, option);

            expect(scrollIntoView).toHaveBeenCalled();
            expect(result).toBe(option);
        });
    });
});
