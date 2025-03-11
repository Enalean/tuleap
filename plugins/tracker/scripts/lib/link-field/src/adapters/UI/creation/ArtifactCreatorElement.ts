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

import type { UpdateFunction } from "hybrids";
import { define, dispatch, html } from "hybrids";
import { Option } from "@tuleap/option";
import { selectOrThrow } from "@tuleap/dom";
import { createListPicker } from "@tuleap/list-picker";
import { WillDisableSubmit } from "@tuleap/plugin-tracker-artifact-common";
import {
    getArtifactCreationFeedbackErrorMessage,
    getArtifactCreationInputPlaceholderText,
    getArtifactCreationProjectLabel,
    getArtifactCreationTrackerLabel,
    getArtifactFeedbackShowMoreLabel,
    getCancelArtifactCreationLabel,
    getCreateArtifactButtonInCreatorLabel,
    getProjectTrackersListPickerPlaceholder,
    getSubmitDisabledForLinkableArtifactCreationReason,
    getSubmitDisabledForProjectsAndTrackersReason,
} from "../../../gettext-catalog";
import type { ArtifactCreatorController } from "../../../domain/creation/ArtifactCreatorController";
import type { Project } from "../../../domain/Project";
import type { LinkType } from "../../../domain/links/LinkType";
import type { CollectionOfAllowedLinksTypesPresenters } from "../CollectionOfAllowedLinksTypesPresenters";
import type { ArtifactCrossReference } from "../../../domain/ArtifactCrossReference";
import "../LinkTypeSelectorElement";
import { FaultDisplayer } from "./FaultDisplayer";
import type { Tracker } from "../../../domain/Tracker";
import { ProjectIdentifierProxy } from "./ProjectIdentifierProxy";
import type { ProjectIdentifier } from "../../../domain/ProjectIdentifier";
import type { LinkableArtifact } from "../../../domain/links/LinkableArtifact";
import { TrackerIdentifierBuilder } from "./TrackerIdentifierBuilder";
import type { TrackerIdentifier } from "../../../domain/TrackerIdentifier";

export type ArtifactCreatorElement = {
    readonly controller: ArtifactCreatorController;
    current_artifact_reference: Option<ArtifactCrossReference>;
    available_types: CollectionOfAllowedLinksTypesPresenters;
    current_link_type: LinkType;
    artifact_title: string;
};
type InternalArtifactCreator = Readonly<ArtifactCreatorElement> & {
    is_loading: boolean;
    error_message: Option<string>;
    show_error_details: boolean;
    projects: ReadonlyArray<Project>;
    trackers: ReadonlyArray<Tracker>;
    selected_project: ProjectIdentifier;
    selected_tracker: Option<TrackerIdentifier>;
    project_selectbox: HTMLSelectElement;
    has_tracker_selection_error: boolean;
    tracker_selectbox: HTMLSelectElement;
    render(): HTMLElement;
};
export type HostElement = InternalArtifactCreator & HTMLElement;

export const TAG = "tuleap-tracker-link-artifact-creator";

export type ArtifactCreatedEvent = { readonly artifact: LinkableArtifact };

const onClickShowMore = (host: InternalArtifactCreator): void => {
    host.show_error_details = true;
};

const getErrorTemplate = (host: InternalArtifactCreator): UpdateFunction<ArtifactCreatorElement> =>
    host.error_message.mapOr(
        (error_message) => {
            const buttonOrDetails = !host.show_error_details
                ? html`<button
                      type="button"
                      class="tlp-button-primary tlp-button-outline tlp-button-small"
                      onclick="${onClickShowMore}"
                  >
                      ${getArtifactFeedbackShowMoreLabel()}
                  </button>`
                : html`<p data-test="creation-error-details">${error_message}</p>`;

            return html`<div
                class="tlp-alert-danger link-field-artifact-creator-alert"
                data-test="creation-error"
            >
                <p>${getArtifactCreationFeedbackErrorMessage()}</p>
                ${buttonOrDetails}
            </div>`;
        },
        html``,
    );

export const onClickCancel = (host: HostElement): void => {
    host.controller.enableSubmit();
    dispatch(host, "cancel");
};

export const onSubmit = async (host: HostElement, event: Event): Promise<void> => {
    event.preventDefault();
    if (!(event.target instanceof HTMLFormElement)) {
        return;
    }
    const title_input = event.target.elements.namedItem("artifact_title");
    if (!(title_input instanceof HTMLInputElement)) {
        return;
    }
    host.is_loading = true;
    const created_artifact = await host.controller.createArtifact(
        title_input.value,
        WillDisableSubmit(getSubmitDisabledForLinkableArtifactCreationReason()),
    );
    host.is_loading = false;
    created_artifact.apply((artifact) => {
        dispatch(host, "artifact-created", { detail: { artifact } });
    });
};

const getProjectOptions = (
    host: InternalArtifactCreator,
): UpdateFunction<ArtifactCreatorElement>[] =>
    host.projects.map(
        (project) =>
            html`<option
                value="${project.id}"
                selected="${host.selected_project.id === project.id}"
                data-test="artifact-modal-link-creator-projects-option"
            >
                ${project.label}
            </option>`,
    );

export const getTrackerSelectClasses = (
    host: InternalArtifactCreator,
): Record<string, boolean> => ({
    "tlp-form-element": true,
    "tlp-form-element-error": host.has_tracker_selection_error,
});

const getTrackersOptions = (
    host: InternalArtifactCreator,
): UpdateFunction<ArtifactCreatorElement>[] =>
    host.trackers.map(
        (tracker) =>
            html`<option
                value="${tracker.id}"
                selected="${host.selected_tracker.mapOr(
                    (identifier) => identifier.id === tracker.id,
                    false,
                )}"
                disabled="${tracker.cannot_create_reason !== ""}"
                data-test="artifact-modal-link-creator-trackers-option"
            >
                ${tracker.label}
            </option>`,
    );

export const onProjectChange = (host: InternalArtifactCreator, event: Event): void => {
    ProjectIdentifierProxy.fromChangeEvent(event).apply((project_identifier) => {
        host.is_loading = true;
        return host.controller
            .selectProjectAndGetItsTrackers(
                project_identifier,
                WillDisableSubmit(getSubmitDisabledForProjectsAndTrackersReason()),
            )
            .then((trackers) => {
                host.controller.enableSubmit();
                host.trackers = trackers;
                host.selected_tracker = host.controller.getSelectedTracker();
                host.is_loading = false;
            });
    });
};

export const onTrackerChange = (host: InternalArtifactCreator, event: Event): void => {
    host.has_tracker_selection_error = !host.tracker_selectbox.checkValidity();
    TrackerIdentifierBuilder()
        .buildFromChangeEvent(event)
        .match(
            (tracker_id) => {
                host.selected_tracker = host.controller.selectTracker(tracker_id);
            },
            () => {
                host.selected_tracker = host.controller.clearTracker();
            },
        );
};

type DisconnectFunction = () => void;
const initListPickers = (host: InternalArtifactCreator): DisconnectFunction => {
    const locale = host.controller.getUserLocale();
    const project_picker = createListPicker(host.project_selectbox, {
        locale,
        is_filterable: true,
    });

    const tracker_picker = createListPicker(host.tracker_selectbox, {
        locale,
        placeholder: getProjectTrackersListPickerPlaceholder(),
        is_filterable: true,
        items_template_formatter: (html, value_id, option_label) => {
            const current_tracker = host.trackers.find(
                (tracker) => Number.parseInt(value_id, 10) === tracker.id,
            );
            if (!current_tracker) {
                return html``;
            }

            const tooltip = html`<span
                class="artifact-modal-link-creator-list-picker-tooltip"
                title="${current_tracker.cannot_create_reason}"
                ><i class="fa-solid fa-question-circle tlp-button-icon" aria-hidden="true"></i
            ></span>`;
            return html`<span class="artifact-modal-link-creator-list-picker-container"
                ><span class="artifact-modal-link-creator-list-picker-option-label"
                    ><span
                        class="tlp-swatch-${current_tracker.color_name} list-picker-circular-color"
                    ></span
                    >${option_label}</span
                >${current_tracker.cannot_create_reason === "" ? html`` : tooltip}</span
            >`;
        },
    });
    return (): void => {
        project_picker.destroy();
        tracker_picker.destroy();
    };
};

export function onErrorMessageChange(host: InternalArtifactCreator): void {
    host.render().querySelector("[data-form]")?.scrollIntoView({ block: "center" });
}

export const renderArtifactCreatorElement = (
    host: InternalArtifactCreator,
): UpdateFunction<InternalArtifactCreator> =>
    html`${getErrorTemplate(host)}
        <form class="link-field-artifact-creator-main" onsubmit="${onSubmit}">
            <span class="link-field-row-type"
                ><tuleap-tracker-link-type-selector
                    value="${host.current_link_type}"
                    current_artifact_reference="${host.current_artifact_reference}"
                    available_types="${host.available_types}"
                    disabled="${host.is_loading}"
                ></tuleap-tracker-link-type-selector
            ></span>
            <div class="link-field-artifact-creator-form" data-form>
                <div class="link-field-artifact-creator-inputs">
                    <input
                        type="text"
                        name="artifact_title"
                        class="tlp-input tlp-input-small"
                        placeholder="${getArtifactCreationInputPlaceholderText()}"
                        disabled="${host.is_loading}"
                        data-test="artifact-creator-title"
                        value="${host.artifact_title}"
                        required
                    />${host.is_loading &&
                    html`<i
                        class="fa-solid fa-spin fa-circle-notch link-field-artifact-creator-spinner"
                        aria-hidden="true"
                        data-test="artifact-creator-spinner"
                    ></i>`}
                    <div class="artifact-modal-link-creator-container">
                        <div class="tlp-form-element link-field-artifact-creator-project-list">
                            <label for="artifact-modal-link-creator-projects" class="tlp-label"
                                >${getArtifactCreationProjectLabel()}
                                <i class="fa-solid fa-asterisk" aria-hidden="true"></i></label
                            ><select
                                id="artifact-modal-link-creator-projects"
                                onchange="${onProjectChange}"
                            >
                                <option value=""></option>
                                ${getProjectOptions(host)}
                            </select>
                        </div>
                        <div
                            class="${getTrackerSelectClasses(host)}"
                            id="artifact-modal-link-creator-tracker-wrapper"
                            data-test="tracker-picker-form-element"
                        >
                            <label for="artifact-modal-link-creator-trackers" class="tlp-label"
                                >${getArtifactCreationTrackerLabel()}
                                <i class="fa-solid fa-asterisk" aria-hidden="true"></i></label
                            ><select
                                required
                                id="artifact-modal-link-creator-trackers"
                                onchange="${onTrackerChange}"
                            >
                                <option value=""></option>
                                ${getTrackersOptions(host)}
                            </select>
                        </div>
                    </div>
                </div>
                <button
                    type="submit"
                    class="tlp-button-primary tlp-button-small link-field-artifact-creator-button"
                    disabled="${host.is_loading}"
                    data-test="artifact-creator-submit"
                >
                    ${getCreateArtifactButtonInCreatorLabel()}
                </button>
                <button
                    type="button"
                    class="tlp-button-secondary tlp-button-small link-field-artifact-creator-button"
                    onclick="${onClickCancel}"
                >
                    ${getCancelArtifactCreationLabel()}
                </button>
            </div>
        </form>`;

export const ArtifactCreatorElement = define.compile<InternalArtifactCreator>({
    tag: TAG,
    controller: {
        value: (host, controller) => controller,
        observe: (host, controller) => {
            const displayer = FaultDisplayer();
            controller.registerFaultListener((fault) => {
                host.error_message = Option.fromValue(displayer.formatForDisplay(fault));
            });

            host.selected_project = controller.getSelectedProject();
            host.is_loading = true;

            const event = WillDisableSubmit(getSubmitDisabledForProjectsAndTrackersReason());
            Promise.all([
                controller.getProjects(event),
                controller.selectProjectAndGetItsTrackers(host.selected_project, event),
            ]).then(([projects, trackers]) => {
                host.controller.enableSubmit();
                host.projects = projects;
                host.trackers = trackers;
                host.selected_tracker = controller.getSelectedTracker();
                host.is_loading = false;
            });
        },
        connect: initListPickers,
    },
    current_artifact_reference: (host, current_artifact_reference) => current_artifact_reference,
    available_types: (host, available_types) => available_types,
    current_link_type: (host, current_link_type) => current_link_type,
    is_loading: false,
    error_message: {
        value: (host, error_message) => error_message ?? Option.nothing(),
        observe: onErrorMessageChange,
    },
    show_error_details: false,
    projects: (host, new_value) => new_value ?? [],
    trackers: (host, new_value) => new_value ?? [],
    selected_project: (host, selected_project) => selected_project,
    selected_tracker: (host, new_value) => new_value ?? Option.nothing(),
    artifact_title: "",
    project_selectbox: (host: InternalArtifactCreator) =>
        selectOrThrow(host.render(), "#artifact-modal-link-creator-projects", HTMLSelectElement),
    has_tracker_selection_error: false,
    tracker_selectbox: (host: InternalArtifactCreator) =>
        selectOrThrow(host.render(), "#artifact-modal-link-creator-trackers", HTMLSelectElement),
    render: renderArtifactCreatorElement,
});

if (!window.customElements.get(TAG)) {
    window.customElements.define(TAG, ArtifactCreatorElement);
}
