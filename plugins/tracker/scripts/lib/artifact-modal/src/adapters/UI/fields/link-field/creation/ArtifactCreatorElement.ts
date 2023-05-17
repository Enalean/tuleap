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
import {
    getArtifactCreationInputPlaceholderText,
    getCancelArtifactCreationLabel,
    getCreateArtifactButtonInCreatorLabel,
    getSubmitDisabledForProjectsAndTrackersReason,
} from "../../../../../gettext-catalog";
import type { ArtifactCreatorController } from "../../../../../domain/fields/link-field/creation/ArtifactCreatorController";

export type ArtifactCreatorElement = {
    readonly controller: ArtifactCreatorController;
    is_loading: boolean;
    content(): HTMLElement;
};
export type HostElement = ArtifactCreatorElement & HTMLElement;

export type LoadingChangeEvent = { readonly is_loading: boolean };
export const observeIsLoading = (host: HostElement, new_value: boolean): void => {
    dispatch(host, "loadingchange", { detail: { is_loading: new_value } });
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

export const ArtifactCreatorElement = define<ArtifactCreatorElement>({
    tag: "tuleap-artifact-modal-link-artifact-creator",
    controller: undefined,
    is_loading: { value: true, observe: observeIsLoading },
    content: (host) =>
        html`<div class="link-field-artifact-creator-inputs">
                <input
                    type="text"
                    class="tlp-input tlp-input-small"
                    placeholder="${getArtifactCreationInputPlaceholderText()}"
                    disabled="${host.is_loading}"
                    data-test="artifact-creator-title"
                />
                ${host.is_loading &&
                html`<i
                    class="fa-solid fa-spin fa-circle-notch link-field-artifact-creator-spinner"
                    aria-hidden="true"
                    data-test="artifact-creator-spinner"
                ></i>`}
            </div>
            <div class="link-field-artifact-creator-actions">
                <button
                    type="button"
                    class="tlp-button-primary tlp-button-small link-field-artifact-creator-button"
                    disabled="${host.is_loading}"
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
