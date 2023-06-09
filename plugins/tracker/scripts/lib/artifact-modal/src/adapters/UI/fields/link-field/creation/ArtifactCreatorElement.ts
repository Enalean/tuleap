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
import {
    getArtifactCreationFeedbackErrorMessage,
    getArtifactCreationInputPlaceholderText,
    getArtifactCreationProjectLabel,
    getArtifactCreationTrackerLabel,
    getArtifactFeedbackShowMoreLabel,
    getCancelArtifactCreationLabel,
    getCreateArtifactButtonInCreatorLabel,
    getProjectTrackersListPickerPlaceholder,
    getSubmitDisabledForProjectsAndTrackersReason,
} from "../../../../../gettext-catalog";
import type { ArtifactCreatorController } from "../../../../../domain/fields/link-field/creation/ArtifactCreatorController";
import type { Project } from "../../../../../domain/Project";
import type { LinkType } from "../../../../../domain/fields/link-field/LinkType";
import type { CollectionOfAllowedLinksTypesPresenters } from "../CollectionOfAllowedLinksTypesPresenters";
import type { ArtifactCrossReference } from "../../../../../domain/ArtifactCrossReference";
import "../LinkTypeSelectorElement";
import { FaultDisplayer } from "./FaultDisplayer";
import type { Tracker } from "../../../../../domain/Tracker";
import { selectOrThrow } from "@tuleap/dom";
import { ProjectIdentifierProxy } from "./ProjectIdentifierProxy";
import { createListPicker } from "@tuleap/list-picker";
import type { ListPicker } from "@tuleap/list-picker";

export type ArtifactCreatorElement = {
    readonly controller: ArtifactCreatorController;
    current_artifact_reference: Option<ArtifactCrossReference>;
    available_types: CollectionOfAllowedLinksTypesPresenters;
    current_link_type: LinkType;
};
type InternalArtifactCreator = Readonly<ArtifactCreatorElement> & {
    is_loading: boolean;
    error_message: Option<string>;
    show_error_details: boolean;
    projects: ReadonlyArray<Project>;
    trackers: ReadonlyArray<Tracker>;
    content(): HTMLElement;
};
export type HostElement = InternalArtifactCreator & HTMLElement;

const onClickShowMore = (host: InternalArtifactCreator): void => {
    host.show_error_details = true;
};

const getErrorTemplate = (host: InternalArtifactCreator): UpdateFunction<ArtifactCreatorElement> =>
    host.error_message.mapOr((error_message) => {
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
    }, html``);

export const observeIsLoading = (host: HostElement, new_value: boolean): void => {
    if (new_value) {
        host.controller.disableSubmit(getSubmitDisabledForProjectsAndTrackersReason());
        return;
    }
    host.controller.enableSubmit();
};

export const onClickCancel = (host: HostElement): void => {
    host.controller.enableSubmit();
    dispatch(host, "cancel");
};

const getOptions = (host: InternalArtifactCreator): UpdateFunction<ArtifactCreatorElement>[] =>
    host.projects.map((project) => html`<option value="${project.id}">${project.label}</option>`);

const getTrackersOptions = (
    host: InternalArtifactCreator
): UpdateFunction<ArtifactCreatorElement>[] =>
    host.trackers.map(
        (tracker) =>
            html`<option
                value="${tracker.id}"
                disabled="${tracker.cannot_create_reason !== ""}"
                data-test="artifact-modal-link-creator-trackers-option"
            >
                ${tracker.label}
            </option>`
    );

const getTrackers = (host: InternalArtifactCreator, event: Event): void => {
    host.is_loading = true;
    const project_id = ProjectIdentifierProxy.fromChangeEvent(event);

    project_id.apply((id) =>
        host.controller.getTrackers(id).then((trackers) => {
            host.trackers = trackers;
            host.is_loading = false;
        })
    );
};

let listPicker: ListPicker;
const initListPicker = (
    host: InternalArtifactCreator,
    controller: ArtifactCreatorController
): void => {
    const select_element = selectOrThrow(
        host.content(),
        "#artifact-modal-link-creator-trackers",
        HTMLSelectElement
    );

    listPicker = createListPicker(select_element, {
        locale: controller.getUserLocale(),
        placeholder: getProjectTrackersListPickerPlaceholder(),
        is_filterable: true,
        items_template_formatter: (html, value_id, option_label) => {
            const current_tracker = host.trackers.find(
                (tracker) => Number(value_id) === tracker.id
            );
            if (!current_tracker) {
                return html``;
            }

            const tooltip = html` <span
                class="artifact-modal-link-creator-list-picker-tooltip"
                title="${current_tracker.cannot_create_reason}"
            >
                <i class="fa-solid fa-question-circle tlp-button-icon" aria-hidden="true"></i
            ></span>`;
            return html` <span class="artifact-modal-link-creator-list-picker-container">
                <span class="artifact-modal-link-creator-list-picker-option-label">
                    <span
                        class="tlp-swatch-${current_tracker.color_name} list-picker-circular-color"
                    ></span>
                    ${option_label}
                </span>
                ${current_tracker.cannot_create_reason === "" ? html`` : tooltip}
            </span>`;
        },
    });
};
export const setErrorMessage = (
    host: HostElement,
    new_value: Option<string> | undefined
): Option<string> => {
    if (new_value) {
        host.content().querySelector("[data-form]")?.scrollIntoView({ block: "center" });
        return new_value;
    }
    return Option.nothing();
};

function getDisconnectedCallback(): () => void {
    return () => {
        listPicker.destroy();
    };
}

export const ArtifactCreatorElement = define<InternalArtifactCreator>({
    tag: "tuleap-artifact-modal-link-artifact-creator",
    controller: {
        set(host, controller: ArtifactCreatorController) {
            const displayer = FaultDisplayer();
            controller.registerFaultListener((fault) => {
                host.error_message = Option.fromValue(displayer.formatForDisplay(fault));
            });
            host.is_loading = true;
            controller.getProjects().then((projects) => {
                host.projects = projects;
                host.is_loading = false;
            });

            initListPicker(host, controller);

            return controller;
        },
        connect: () => getDisconnectedCallback(),
    },
    current_artifact_reference: undefined,
    available_types: undefined,
    current_link_type: undefined,
    is_loading: { value: false, observe: observeIsLoading },
    error_message: { set: setErrorMessage },
    show_error_details: false,
    projects: { set: (host, new_value) => new_value ?? [] },
    trackers: { set: (host, new_value) => new_value ?? [] },
    content: (host) =>
        html`${getErrorTemplate(host)}<span class="link-field-row-type"
                ><tuleap-artifact-modal-link-type-selector
                    value="${host.current_link_type}"
                    current_artifact_reference="${host.current_artifact_reference}"
                    available_types="${host.available_types}"
                    disabled="${host.is_loading}"
                ></tuleap-artifact-modal-link-type-selector
            ></span>
            <div class="link-field-artifact-creator-form" data-form>
                <div class="link-field-artifact-creator-inputs">
                    <input
                        type="text"
                        class="tlp-input tlp-input-small"
                        placeholder="${getArtifactCreationInputPlaceholderText()}"
                        disabled="${host.is_loading}"
                        data-test="artifact-creator-title"
                    />${host.is_loading &&
                    html`<i
                        class="fa-solid fa-spin fa-circle-notch link-field-artifact-creator-spinner"
                        aria-hidden="true"
                        data-test="artifact-creator-spinner"
                    ></i>`}
                    <div class="artifact-modal-link-creator-container">
                        <div
                            class="tlp-form-element"
                            id="artifact-modal-link-creator-project-wrapper"
                        >
                            <label for="artifact-modal-link-creator-projects" class="tlp-label"
                                >${getArtifactCreationProjectLabel()}
                                <i class="fa-solid fa-asterisk" aria-hidden="true"></i></label
                            ><select
                                class="tlp-select tlp-select-small"
                                form=""
                                required
                                id="artifact-modal-link-creator-projects"
                                oninput="${getTrackers}"
                            >
                                ${getOptions(host)}
                            </select>
                        </div>
                        <div
                            class="tlp-form-element artifact-modal-link-creator-container-tracker-select"
                            id="artifact-modal-link-creator-tracker-wrapper"
                        >
                            <label for="artifact-modal-link-creator-trackers" class="tlp-label"
                                >${getArtifactCreationTrackerLabel()}
                                <i class="fa-solid fa-asterisk" aria-hidden="true"></i></label
                            ><select class="" form="" id="artifact-modal-link-creator-trackers">
                                ${getTrackersOptions(host)}
                            </select>
                        </div>
                    </div>
                </div>
                <button
                    type="button"
                    class="tlp-button-primary tlp-button-small link-field-artifact-creator-button"
                    disabled
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
            </div>`,
});
