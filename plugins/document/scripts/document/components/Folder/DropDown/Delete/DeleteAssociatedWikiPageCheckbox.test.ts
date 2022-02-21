/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../../../helpers/local-vue";
import { TYPE_WIKI } from "../../../../constants";
import WikiCheckbox from "./DeleteAssociatedWikiPageCheckbox.vue";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import type { Wiki } from "../../../../type";
import type { ItemPath } from "../../../../store/actions-helpers/build-parent-paths";

describe("ModalConfirmationDeletion", () => {
    function createWrapper(wikiPageReferencers: Array<ItemPath>): Wrapper<WikiCheckbox> {
        const item = {
            id: 42,
            title: "my wiki",
            wiki_properties: {
                page_name: "my wiki",
            },
            type: TYPE_WIKI,
        } as Wiki;

        const store = createStoreMock({
            state: {
                configuration: { project_id: 104 },
            },
        });
        return shallowMount(WikiCheckbox, {
            mocks: {
                $store: store,
            },
            localVue: localVue,
            propsData: { item, wikiPageReferencers },
        });
    }

    it("shows the warning only when option is checked", async () => {
        const wiki_checkbox = createWrapper([
            {
                id: 43,
                path: "Project documentation/another wiki",
            },
        ]);

        const checkbox_input = wiki_checkbox.get(
            "[data-test=delete-associated-wiki-page-checkbox]"
        );

        checkbox_input.trigger("click");
        await wiki_checkbox.vm.$nextTick();

        expect(
            wiki_checkbox.find("[data-test=delete-associated-wiki-page-warning-message]").exists()
        ).toBeTruthy();

        checkbox_input.trigger("click");
        await wiki_checkbox.vm.$nextTick();

        expect(
            wiki_checkbox.find("[data-test=delete-associated-wiki-page-warning-message]").exists()
        ).toBeFalsy();
    });

    it("does not show the warning when wikiPageReferencers is empty no matter if the option is checked or not", async () => {
        const wiki_checkbox = createWrapper([]);
        const checkbox_input = wiki_checkbox.get(
            "[data-test=delete-associated-wiki-page-checkbox]"
        );

        checkbox_input.trigger("click");
        await wiki_checkbox.vm.$nextTick();

        expect(
            wiki_checkbox.find("[data-test=delete-associated-wiki-page-warning-message]").exists()
        ).toBeFalsy();

        checkbox_input.trigger("click");

        expect(
            wiki_checkbox.find("[data-test=delete-associated-wiki-page-warning-message]").exists()
        ).toBeFalsy();
    });

    it("renders a list of links", async () => {
        const wiki_checkbox = createWrapper([
            {
                id: 43,
                path: "Project documentation/another wiki",
            },
            {
                id: 44,
                path: "Project documentation/some folder/another wiki",
            },
        ]);

        const checkbox_input = wiki_checkbox.get(
            "[data-test=delete-associated-wiki-page-checkbox]"
        );

        checkbox_input.trigger("click");
        await wiki_checkbox.vm.$nextTick();

        const links = wiki_checkbox.findAll("[data-test=wiki-page-referencer-link]");

        expect(links.length).toEqual(2);

        expect(links.at(0).element.tagName).toEqual("A");
        expect(links.at(0).attributes("href")).toEqual(
            "/plugins/docman/?group_id=104&action=show&id=43"
        );
        expect(links.at(0).text()).toEqual("Project documentation/another wiki");

        expect(links.at(1).element.tagName).toEqual("A");
        expect(links.at(1).attributes("href")).toEqual(
            "/plugins/docman/?group_id=104&action=show&id=44"
        );
        expect(links.at(1).text()).toEqual("Project documentation/some folder/another wiki");
    });
});
