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

import type { ArtifactCreatedEvent, HostElement } from "./ArtifactCreatorElement";
import {
    ArtifactCreatorElement,
    observeIsLoading,
    onClickCancel,
    onSubmit,
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
import { CurrentProjectIdentifierStub } from "../../../../../../tests/stubs/CurrentProjectIdentifierStub";
import { ProjectIdentifierStub } from "../../../../../../tests/stubs/ProjectIdentifierStub";

describe(`ArtifactCreatorElement`, () => {
    let doc: Document;
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
        doc = document.implementation.createHTMLDocument();
    });

    describe(`events`, () => {
        let controller: ArtifactCreatorController;
        beforeEach(() => {
            const project: Project = { id: 144, label: "Next Omega" };
            controller = ArtifactCreatorController(
                DispatchEventsStub.buildNoOp(),
                RetrieveProjectsStub.withProjects(project),
                RetrieveProjectTrackersStub.withoutTracker(),
                CurrentProjectIdentifierStub.withId(144),
                en_US_LOCALE
            );
        });

        const getHost = (): HostElement => {
            const projects: ReadonlyArray<Project> = [];
            const trackers: ReadonlyArray<Tracker> = [];
            const element = doc.createElement("div");
            return Object.assign(element, {
                controller,
                current_artifact_reference: Option.nothing(),
                available_types: CollectionOfAllowedLinksTypesPresenters.buildEmpty(),
                current_link_type: LinkTypeStub.buildUntyped(),
                is_loading: false,
                error_message: Option.nothing(),
                show_error_details: false,
                projects,
                trackers,
                content: () => element as HTMLElement,
            } as HostElement);
        };

        it(`when I click on the "Cancel" button, it will enable the modal submit
            and dispatch a "cancel" event`, () => {
            const host = getHost();
            const dispatchEvent = jest.spyOn(host, "dispatchEvent");
            const enableSubmit = jest.spyOn(controller, "enableSubmit");

            onClickCancel(host);

            expect(enableSubmit).toHaveBeenCalled();
            const event = dispatchEvent.mock.calls[0][0];
            expect(event.type).toBe("cancel");
        });

        it(`when I submit the creation form, it will prevent default (to avoid redirecting)
            and it will dispatch an "artifact-created" event containing a LinkableArtifact`, () => {
            const host = getHost();
            const dispatchEvent = jest.spyOn(host, "dispatchEvent");

            const inner_event = new Event("submit", { cancelable: true });
            onSubmit(host, inner_event);

            expect(inner_event.defaultPrevented).toBe(true);
            const event = dispatchEvent.mock.calls[0][0] as CustomEvent<ArtifactCreatedEvent>;
            expect(event.type).toBe("artifact-created");
            expect(event.detail.artifact.id).toBe(-1);
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
        let is_loading: boolean,
            error_message: Option<string>,
            show_error_details: boolean,
            trackers: ReadonlyArray<Tracker>,
            projects: ReadonlyArray<Project>;
        const selected_project_id = 806;

        beforeEach(() => {
            is_loading = false;
            error_message = Option.nothing();
            show_error_details = false;
            projects = [];
            trackers = [];
        });

        const render = (): HTMLElement => {
            const element = doc.createElement("div");
            const host = Object.assign(element, {
                controller: {} as ArtifactCreatorController,
                current_artifact_reference: Option.nothing(),
                available_types: CollectionOfAllowedLinksTypesPresenters.buildEmpty(),
                current_link_type: LinkTypeStub.buildUntyped(),
                is_loading,
                error_message,
                show_error_details,
                projects,
                trackers,
                selected_project: ProjectIdentifierStub.withId(selected_project_id),
                content: () => element as HTMLElement,
            } as HostElement);

            const updateFunction = ArtifactCreatorElement.content(host);
            updateFunction(host, element as unknown as ShadowRoot);
            return element;
        };

        it(`when it is loading, it will disable inputs and buttons and will show a spinner icon`, () => {
            is_loading = true;
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

        it(`tracks the currently selected project in the select`, () => {
            projects = [
                { id: 775, label: "Next Omega" },
                { id: selected_project_id, label: "Cloudy Bird" },
            ];

            const target = render();
            const options = target.querySelectorAll<HTMLOptionElement>(
                "[data-test=artifact-modal-link-creator-projects-option]"
            );

            expect(options).toHaveLength(2);
            expect(options.item(0).selected).toBe(false);
            expect(options.item(1).selected).toBe(true);
        });

        it(`disables the option when the user cannot create artifacts`, () => {
            trackers = [
                { id: 201, label: "GT", color_name: "deep-blue", cannot_create_reason: "" },
                { id: 206, label: "Shelby", color_name: "green", cannot_create_reason: "" },
                {
                    id: 158,
                    label: "Mach-e",
                    color_name: "red",
                    cannot_create_reason: "Not a Mustang",
                },
                {
                    id: selected_project_id,
                    label: "Mach 1",
                    color_name: "red",
                    cannot_create_reason: "",
                },
            ];

            const target = render();
            const tracker_options = target.querySelectorAll<HTMLOptionElement>(
                "[data-test=artifact-modal-link-creator-trackers-option]"
            );

            expect(tracker_options).toHaveLength(4);
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
