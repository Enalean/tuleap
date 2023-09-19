/**
 * Copyright (c) Enalean, 2013 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

import type { GetText } from "@tuleap/gettext";
import { getPOFileFromLocale, initGettext } from "@tuleap/gettext";
import { getJSON, uri } from "@tuleap/fetch-result";
import { html, render } from "lit/html.js";

document.addEventListener("DOMContentLoaded", async () => {
    const containers = document.querySelectorAll(".artifact-type");
    if (containers.length === 0) {
        return;
    }
    const locale = document.body.dataset.userLocale ?? "en_US";

    const gettext_provider = await initGettext(
        locale,
        "tracker_artifact",
        (locale) =>
            import(
                /* webpackChunkName: "tracker-artifact-po-" */ "../po/" +
                    getPOFileFromLocale(locale)
            ),
    );

    for (const container of containers) {
        if (!(container instanceof HTMLElement)) {
            continue;
        }

        const artifact_id = Number(container.dataset.artifactId);
        if (!artifact_id) {
            continue;
        }

        initHierarchyViewer(container, gettext_provider, artifact_id);
    }
});

function initHierarchyViewer(
    container: HTMLElement,
    gettext_provider: GetText,
    artifact_id: number,
): void {
    const renderer = renderHierarchyViewer(container, gettext_provider);
    const provider = initHierarchyViewerItemProvider(renderer);
    const root = renderer.insertRoot({
        id: artifact_id,
        parent_id: null,
        xref: null,
        title: null,
        status: null,
        url: null,
        has_children: false,
    });

    const top_item = initHierarchyViewerItem(artifact_id, root, provider);

    top_item.open();
}

interface ArtifactRepresentation {
    readonly id: number;
    readonly parent_id: number | null;
    readonly url: string | null;
    readonly xref: string | null;
    readonly title: string | null;
    readonly status: string | null;
    readonly has_children: boolean;
}

interface HierarchyViewerRenderer {
    readonly startSpinner: () => void;
    readonly stopSpinner: () => void;
    readonly insertRoot: (root: ArtifactRepresentation) => Element;
    readonly insertChildAfter: (
        parent_element: Element,
        child: ArtifactRepresentation,
    ) => Element | null;
}

/**
 * Display children and subchildren in the page
 */
function renderHierarchyViewer(
    container: HTMLElement,
    gettext_provider: GetText,
): HierarchyViewerRenderer {
    insertTable();
    const body = container.querySelector("tbody");

    const renderItem = (item: ArtifactRepresentation): Element => {
        const template = html`
            <tr data-child-id="${item.id}" data-parent-id="${item.parent_id}">
                <td>
                    <a href="#" class="toggle-child"><i class="fa fa-caret-right fa-fw"></i></a>
                    <a href="${item.url}">${item.xref}</a>
                </td>
                <td>${item.title}</td>
                <td>${item.status}</td>
            </tr>
        `;

        const document_fragment = document.createDocumentFragment();
        render(template, document_fragment);

        const document_element = document_fragment.firstElementChild;
        if (document_element !== null && document_fragment.children.length === 1) {
            return document_element;
        }
        throw new Error("Cannot create item element");
    };

    return {
        startSpinner,
        stopSpinner,
        insertRoot,
        insertChildAfter,
    };

    function insertTable(): void {
        const title = gettext_provider.gettext("Title");
        const status = gettext_provider.gettext("Status");

        const template = html`
            <table class="table artifact-children-table">
                <thead>
                    <tr class="artifact-children-table-head">
                        <th></th>
                        <th>${title}</th>
                        <th>${status}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        `;

        const document_fragment = document.createDocumentFragment();
        render(template, document_fragment);

        const document_element = document_fragment.firstElementChild;
        if (document_element !== null && document_fragment.children.length === 1) {
            container.appendChild(document_element);
            return;
        }
        throw new Error("Cannot create table element");
    }

    function insertRoot(root: ArtifactRepresentation): Element {
        if (!body) {
            throw new Error("Body not found");
        }

        body.appendChild(renderItem(root));
        const element = body.children[body.children.length - 1];
        element.setAttribute("data-is-root", "1");
        if (element instanceof HTMLElement) {
            element.style.display = "none";
        }

        return element;
    }

    function insertChildAfter(
        parent_element: Element,
        child: ArtifactRepresentation,
    ): Element | null {
        parent_element.insertAdjacentElement("afterend", renderItem(child));

        const element = parent_element.nextElementSibling;
        if (element) {
            if (!child.has_children) {
                const toggle = element.querySelector("a.toggle-child");
                if (toggle instanceof HTMLElement) {
                    toggle.style.visibility = "hidden";
                }
            }
            adjustPadding(parent_element, element);
        }

        return element;
    }

    function adjustPadding(parent: Element, child: Element): void {
        let padding_left = 0;
        if (!parent.getAttribute("data-is-root")) {
            const td = parent.querySelector("td");
            if (td) {
                padding_left = ~~td.style.paddingLeft.replace("px", "") + 24;
            }
        }
        const td = child.querySelector("td");
        if (td) {
            td.style.paddingLeft = padding_left + "px";
        }
    }

    function startSpinner(): void {
        document.body.style.cursor = "progress";
    }

    function stopSpinner(): void {
        document.body.style.cursor = "default";
    }
}

interface HierarchyViewerItemProvider {
    readonly injectChildren: (item: HierarchyViewerItem) => void;
}

/**
 * Provide children of an item
 */
function initHierarchyViewerItemProvider(
    renderer: HierarchyViewerRenderer,
): HierarchyViewerItemProvider {
    let nb_request = 0;

    const provider = {
        injectChildren,
    };

    return provider;

    function injectChildren(item: HierarchyViewerItem): void {
        nb_request++;
        renderer.startSpinner();
        getJSON<ArtifactRepresentation[]>(
            uri`/plugins/tracker/?aid=${item.getId()}&func=get-children`,
        ).match(
            (children) => {
                receiveChildren(item, children);
                nb_request--;
                if (nb_request <= 0) {
                    renderer.stopSpinner();
                }
            },
            () => {
                nb_request--;
                if (nb_request <= 0) {
                    renderer.stopSpinner();
                }
            },
        );
    }

    function receiveChildren(
        parent: HierarchyViewerItem,
        children: ArtifactRepresentation[],
    ): void {
        children.forEach((child) => {
            const element = renderer.insertChildAfter(parent.getElement(), child);
            if (element) {
                const item = initHierarchyViewerItem(child.id, element, provider);
                parent.addChild(item);
            }
        });
    }
}

interface HierarchyViewerItem {
    readonly getElement: () => Element;
    readonly addChild: (child: HierarchyViewerItem) => void;
    readonly getId: () => number;
    readonly show: () => void;
    readonly hide: () => void;
    readonly open: () => void;
}

/**
 * A child
 */
function initHierarchyViewerItem(
    id: number,
    element: Element,
    item_provider: HierarchyViewerItemProvider,
): HierarchyViewerItem {
    const children: HierarchyViewerItem[] = [];
    let is_open = false;

    const icon = element.querySelector("a.toggle-child > i");
    if (!icon) {
        throw new Error("Unable to find icon of element");
    }

    icon.addEventListener("click", (evt) => {
        if (is_open) {
            close();
        } else {
            open();
        }
        evt.stopPropagation();
        evt.preventDefault();
    });

    const myself = {
        getElement,
        addChild,
        getId,
        show,
        hide,
        open,
    };

    return myself;

    function getElement(): Element {
        return element;
    }

    function getId(): number {
        return id;
    }

    function addChild(child: HierarchyViewerItem): void {
        children.push(child);
    }

    function open(): void {
        is_open = true;
        useHideIcon();
        if (children.length) {
            showChildren();
        } else {
            item_provider.injectChildren(myself);
        }
    }

    function close(): void {
        is_open = false;
        useShowIcon();
        hideChildren();
    }

    function show(): void {
        if (element instanceof HTMLElement) {
            element.style.display = "";
        }
        if (is_open) {
            showChildren();
        }
    }

    function hide(): void {
        if (element instanceof HTMLElement) {
            element.style.display = "none";
        }
        hideChildren();
    }

    function hideChildren(): void {
        children.forEach((child) => child.hide());
    }

    function showChildren(): void {
        children.forEach((child) => child.show());
    }

    function useShowIcon(): void {
        if (icon) {
            icon.classList.remove("fa-caret-down");
            icon.classList.add("fa-caret-right");
        }
    }

    function useHideIcon(): void {
        if (icon) {
            icon.classList.add("fa-caret-down");
            icon.classList.remove("fa-caret-right");
        }
    }
}
