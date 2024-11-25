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
import { define } from "hybrids";
import { resetState, displayFullyLoaded, displayLoading } from "./display-state";
import { fetchReferencesInText } from "./fetch-reference";
import { loadTooltips } from "@tuleap/tooltip";

export const TAG = "async-cross-reference";

export const CALLBACK_EXECUTION_DELAY_IN_MS = 250;

export type AsyncCrossReference = {
    text: string;
    project_id: number;
};

export type InternalAsyncCrossReference = AsyncCrossReference & {
    timeout_id: NodeJS.Timeout | undefined;
};

export type HostElement = InternalAsyncCrossReference & HTMLElement;

const isAsyncCrossReferenceElement = (
    element: HTMLElement,
): element is HTMLElement & AsyncCrossReference => element.tagName === TAG.toUpperCase();

export const createAsyncCrossReference = (
    text: string,
    project_id: number,
): HTMLElement & AsyncCrossReference => {
    const element = document.createElement(TAG);
    if (!isAsyncCrossReferenceElement(element)) {
        throw new Error("Unable to create an AsyncCrossReferenceElement :/");
    }
    element.text = text;
    element.project_id = project_id;
    return element;
};

const debounceFetchReferenceRequest = (
    host: InternalAsyncCrossReference,
    callback: () => void,
): void => {
    clearTimeout(host.timeout_id);
    host.timeout_id = setTimeout(callback, CALLBACK_EXECUTION_DELAY_IN_MS);
};

export const observeTextChange = (host: HostElement, text: string): void => {
    resetState(host);

    debounceFetchReferenceRequest(host, () => {
        displayLoading(host);
        fetchReferencesInText(text, host.project_id).match(
            (references) => {
                if (!references || !references[0]) {
                    delete host.dataset.href;
                    resetState(host);
                    return;
                }

                host.dataset.href = references[0].link;
                displayFullyLoaded(host);

                if (host.parentElement) {
                    loadTooltips(host.parentElement);
                }
            },
            () => {
                delete host.dataset.href;
                resetState(host);
            },
        );
    });
};

const async_cross_reference = define.compile<InternalAsyncCrossReference>({
    tag: TAG,
    project_id: 0,
    timeout_id: undefined,
    text: {
        value: (host, text) => text,
        observe: observeTextChange,
        connect: (host: HostElement) => {
            const observer = new MutationObserver((mutations) => {
                if (mutations.length === 0) {
                    return;
                }

                const text_mutation = mutations.find(
                    (mutation) => mutation.type === "characterData",
                );

                if (!text_mutation || !(text_mutation.target instanceof Text)) {
                    return;
                }

                host.text = text_mutation.target.data;
            });

            observer.observe(host, { subtree: true, characterData: true });

            return (): void => {
                clearTimeout(host.timeout_id);
                observer.disconnect();
            };
        },
    },
});

if (!window.customElements.get(TAG)) {
    window.customElements.define(TAG, async_cross_reference);
}
