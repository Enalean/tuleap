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

import { after_render_once_descriptor, PullRequestLabelsList } from "./PullRequestLabelsList";
import type { HostElement } from "./PullRequestLabelsList";
import type { ProjectLabel } from "@tuleap/core-rest-api-types";
import * as fetch_result from "@tuleap/fetch-result";
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";

const labels: ProjectLabel[] = [
    {
        id: 1,
        label: "Chicken",
        color: "graffiti-yellow",
        is_outline: false,
    },
    {
        id: 2,
        label: "Tomato",
        color: "fiesta-red",
        is_outline: true,
    },
    {
        id: 3,
        label: "Onion",
        color: "plum-crazy",
        is_outline: true,
    },
];

describe("PullRequestLabelsList", () => {
    let doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
    });

    it("When the component is rendered for the first time, Then it should fetch the labels using the provided pull-request id", async () => {
        const host = {
            pullRequestId: 15,
            labels: [],
        } as unknown as HostElement;

        jest.spyOn(fetch_result, "getAllJSON").mockReturnValue(okAsync(labels));

        await after_render_once_descriptor.observe(host);

        expect(host.labels).toStrictEqual(labels);
    });

    it("When an error occurs while retrieving the labels, Then it should dispatch a fetch-error event containing a fault", async () => {
        const tuleap_api_fault = Fault.fromMessage("Forbidden");
        jest.spyOn(fetch_result, "getAllJSON").mockReturnValue(errAsync(tuleap_api_fault));

        const host = Object.assign(doc.createElement("div"), {
            pullRequestId: 15,
        }) as unknown as HostElement;

        const dispatchEvent = jest.spyOn(host, "dispatchEvent");

        await after_render_once_descriptor.observe(host);

        const event = dispatchEvent.mock.calls[0][0];
        if (!(event instanceof CustomEvent)) {
            throw new Error("Expected a CustomEvent");
        }
        expect(event.type).toBe("fetch-error");
        expect(event.detail.fault).toBe(tuleap_api_fault);
    });

    it("Given a list of labels, Then it should fetch the labels and display them as badges with the right styles", () => {
        const target = doc.createElement("div") as unknown as ShadowRoot;
        const host = { labels } as unknown as HostElement;

        const render = PullRequestLabelsList.content(host);

        render(host, target);

        const badges = target.querySelectorAll("[data-test=pull-request-label]");

        expect(badges).toHaveLength(3);

        const [chicken_badge, tomato_badge, onion_badge] = badges;

        expect(Array.from(chicken_badge.classList)).toStrictEqual(["tlp-badge-graffiti-yellow"]);
        expect(Array.from(tomato_badge.classList)).toStrictEqual([
            "tlp-badge-fiesta-red",
            "tlp-badge-outline",
        ]);
        expect(Array.from(onion_badge.classList)).toStrictEqual([
            "tlp-badge-plum-crazy",
            "tlp-badge-outline",
        ]);
    });
});
