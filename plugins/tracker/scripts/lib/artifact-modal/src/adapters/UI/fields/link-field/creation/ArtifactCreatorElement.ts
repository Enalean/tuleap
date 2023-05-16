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

import { define, dispatch, html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import { Option } from "@tuleap/option";
import {
    getArtifactCreationFeedbackErrorMessage,
    getArtifactCreationInputPlaceholderText,
    getArtifactCreationProjectLabel,
    getCancelArtifactCreationLabel,
    getCreateArtifactButtonInCreatorLabel,
    getSubmitDisabledForProjectsAndTrackersReason,
} from "../../../../../gettext-catalog";
import type { ArtifactCreatorController } from "../../../../../domain/fields/link-field/creation/ArtifactCreatorController";
import type { Project } from "../../../../../domain/Project";
import type { LinkType } from "../../../../../domain/fields/link-field/LinkType";
import type { CollectionOfAllowedLinksTypesPresenters } from "../CollectionOfAllowedLinksTypesPresenters";
import type { ArtifactCrossReference } from "../../../../../domain/ArtifactCrossReference";
import "../LinkTypeSelectorElement";

export type ArtifactCreatorElement = {
    readonly controller: ArtifactCreatorController;
    current_artifact_reference: Option<ArtifactCrossReference>;
    available_types: CollectionOfAllowedLinksTypesPresenters;
    current_link_type: LinkType;
};
type InternalArtifactCreator = Readonly<ArtifactCreatorElement> & {
    is_loading: boolean;
    error_message: Option<string>;
    projects: ReadonlyArray<Project>;
    content(): HTMLElement;
};
export type HostElement = InternalArtifactCreator & HTMLElement;

const getErrorTemplate = (host: InternalArtifactCreator): UpdateFunction<ArtifactCreatorElement> =>
    host.error_message.mapOr(
        () =>
            html`<div
                class="tlp-alert-danger link-field-artifact-creator-alert"
                data-test="creation-error"
            >
                ${getArtifactCreationFeedbackErrorMessage()}
            </div>`,
        html``
    );

export const observeIsLoading = (host: HostElement, new_value: boolean): void => {
    if (new_value) {
        host.controller.disableSubmit(getSubmitDisabledForProjectsAndTrackersReason());
        return;
    }
    host.controller.enableSubmit();
};

export const onClick = (host: HostElement): void => {
    host.controller.enableSubmit();
    dispatch(host, "cancel");
};

const getOptions = (host: InternalArtifactCreator): UpdateFunction<ArtifactCreatorElement>[] =>
    host.projects.map((project) => html`<option>${project.label}</option>`);

export const ArtifactCreatorElement = define<InternalArtifactCreator>({
    tag: "tuleap-artifact-modal-link-artifact-creator",
    controller: {
        set(host, controller: ArtifactCreatorController) {
            controller.registerFaultListener((fault) => {
                host.error_message = Option.fromValue(String(fault));
            });
            host.is_loading = true;
            controller.getProjects().then((projects) => {
                host.projects = projects;
                host.is_loading = false;
            });
            return controller;
        },
    },
    current_artifact_reference: undefined,
    available_types: undefined,
    current_link_type: undefined,
    is_loading: { value: false, observe: observeIsLoading },
    error_message: { set: (host, new_value) => new_value ?? Option.nothing() },
    projects: { set: (host, new_value) => new_value ?? [] },
    content: (host) =>
        html`${getErrorTemplate(host)}<span class="link-field-row-type"
                ><tuleap-artifact-modal-link-type-selector
                    value="${host.current_link_type}"
                    current_artifact_reference="${host.current_artifact_reference}"
                    available_types="${host.available_types}"
                    disabled="${host.is_loading}"
                ></tuleap-artifact-modal-link-type-selector
            ></span>
            <div class="link-field-artifact-creator-form">
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
                    <div>
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
                            >
                                ${getOptions(host)}
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
                    onclick="${onClick}"
                >
                    ${getCancelArtifactCreationLabel()}
                </button>
            </div>`,
});
