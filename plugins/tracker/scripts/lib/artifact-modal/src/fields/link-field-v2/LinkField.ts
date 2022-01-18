/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { define, html } from "hybrids";
import { sprintf } from "sprintf-js";

import {
    getLinkFieldUnderConstructionPlaceholder,
    getLinkFieldFetchErrorMessage,
} from "../../gettext-catalog";
import { isInCreationMode } from "../../modal-creation-mode-state";
import { getLinkedArtifacts } from "./links-retriever";

import type { UpdateFunction } from "hybrids";
import type { LinkedArtifact } from "./links-retriever";

export interface LinkField {
    readonly fieldId: number;
    readonly label: string;
    readonly artifactId: number;
    readonly content: () => HTMLElement;
}

export type HostElement = LinkField & HTMLElement;

export const getFormattedArtifacts = (artifacts: LinkedArtifact[]): UpdateFunction<unknown>[] =>
    artifacts.map(
        (artifact: LinkedArtifact) => html`
            <div>
                <a href="${artifact.html_url}" data-test="artifact-link">
                    <span
                        class="
                            cross-ref-badge
                            cross-ref-badge-${artifact.tracker.color_name}
                            tuleap-artifact-modal-link-badge
                        "
                        data-test="artifact-xref"
                    >
                        ${artifact.xref}
                    </span>
                    <span data-test="artifact-title">${artifact.title}</span>
                </a>
            </div>
        `
    );

const formatErrorMessage = (error: Error): string =>
    sprintf(getLinkFieldFetchErrorMessage(), error.message);

export const LinkField = define<LinkField>({
    tag: "tuleap-artifact-modal-link-field-v2",
    fieldId: 0,
    label: "",
    artifactId: 0,
    content: (host) => html`
        <div class="tlp-form-element">
            <label for="${"tracker_field_" + host.fieldId}" class="tlp-label">${host.label}</label>
            <input
                id="${"tracker_field_" + host.fieldId}"
                type="text"
                class="tlp-input"
                placeholder="${getLinkFieldUnderConstructionPlaceholder()}"
                disabled
            />
        </div>
        ${!isInCreationMode() &&
        html`
            <div class="tlp-property" data-test="linked-artifacts-list">
                ${html.resolve(
                    getLinkedArtifacts(host.artifactId)
                        .then(getFormattedArtifacts)
                        .catch(
                            (err) => html`
                                <div class="tlp-alert-danger">${formatErrorMessage(err)}</div>
                            `
                        ),
                    html`
                        <span class="tlp-skeleton-text"></span>
                    `
                )}
            </div>
        `}
    `,
});
