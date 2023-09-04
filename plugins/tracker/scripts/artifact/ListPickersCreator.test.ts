/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { ListPickersCreator } from "./ListPickersCreator";
import { SelectWrappedByListPickerStore } from "./SelectWrappedByListPickerStore";
import * as list_picker from "@tuleap/list-picker";

const LOCALE = "fr_FR";

describe("list-pickers-creator", () => {
    let doc: Document, createListPicker: jest.SpyInstance;

    function createArtifactFormElementFieldInReadModeOfType(type: string): {
        button: HTMLButtonElement;
        select: HTMLSelectElement;
    } {
        const field = doc.createElement("div");
        field.setAttribute("class", `tracker_artifact_field-${type}`);

        const button = doc.createElement("button");
        button.setAttribute("class", "tracker_formelement_edit");

        const hidden_edition_field = doc.createElement("div");
        hidden_edition_field.setAttribute("class", "tracker_hidden_edition_field");

        const select = doc.createElement("select");

        if (type === "msb") {
            select.setAttribute("multiple", "multiple");
        }

        hidden_edition_field.appendChild(select);
        field.appendChild(button);
        field.appendChild(hidden_edition_field);
        doc.body.appendChild(field);

        return {
            button,
            select,
        };
    }

    function createArtifactFormElementFieldInEditionModeOfType(
        type: string,
        is_in_edition_mode = false,
    ): HTMLSelectElement {
        const field = doc.createElement("div");
        field.setAttribute("class", `tracker_artifact_field-${type}`);

        if (is_in_edition_mode) {
            field.classList.add("in-edition");
        }

        const select = doc.createElement("select");

        if (type === "msb") {
            select.setAttribute("multiple", "multiple");
        }

        field.appendChild(select);
        doc.body.appendChild(field);

        return select;
    }

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();

        doc.body.dataset.userLocale = LOCALE;
        createListPicker = jest.spyOn(list_picker, "createListPicker").mockReturnValue({
            destroy: () => {
                // Do nothing
            },
        });
    });

    describe(`listenToggleEditionEvents()`, () => {
        const listenToggleEditionEvents = (): void => {
            const creator = ListPickersCreator(doc, SelectWrappedByListPickerStore());
            return creator.listenToggleEditionEvents();
        };

        it.each([["sb"], ["msb"]])(
            "should listen for clicks on fields labels to create a list picker when the <select> is shown",
            (type: string) => {
                const { button, select } = createArtifactFormElementFieldInReadModeOfType(type);

                listenToggleEditionEvents();
                button.dispatchEvent(new Event("click"));

                expect(createListPicker).toHaveBeenCalledWith(select, {
                    locale: LOCALE,
                    is_filterable: true,
                    none_value: null,
                });
            },
        );

        it("when the field has targets (field dependencies), then it should initialize the target fields recursively", () => {
            const { button: button_1, select: select_1 } =
                createArtifactFormElementFieldInReadModeOfType("sb");
            const { select: select_2 } = createArtifactFormElementFieldInReadModeOfType("sb");
            const { select: select_3 } = createArtifactFormElementFieldInReadModeOfType("msb");

            select_1.id = "tracker_field_5";
            select_2.id = "tracker_field_10";
            select_3.id = "tracker_field_25";

            select_1.setAttribute("data-target-fields-ids", JSON.stringify(["10"]));
            select_2.setAttribute("data-target-fields-ids", JSON.stringify(["25"]));

            listenToggleEditionEvents();
            button_1.dispatchEvent(new Event("click"));

            expect(createListPicker).toHaveBeenCalledWith(select_1, {
                locale: LOCALE,
                is_filterable: true,
                none_value: null,
            });
            expect(createListPicker).toHaveBeenCalledWith(select_2, {
                locale: LOCALE,
                is_filterable: true,
                none_value: null,
            });
            expect(createListPicker).toHaveBeenCalledWith(select_3, {
                locale: LOCALE,
                is_filterable: true,
                none_value: null,
            });
        });

        it(`when a field is required and has no value (and thus is in edition),
            and when it's also target of a field dependency,
            it should create only one list picker per select`, () => {
            const target_select = createArtifactFormElementFieldInEditionModeOfType("msb", true);
            target_select.id = "tracker_field_123";
            const { select: source_select, button } =
                createArtifactFormElementFieldInReadModeOfType("msb");
            source_select.id = "tracker_field_456";
            source_select.setAttribute("data-target-fields-ids", JSON.stringify(["123"]));

            const creator = ListPickersCreator(doc, SelectWrappedByListPickerStore());
            creator.initListPickersPostUpdateErrorView();
            creator.listenToggleEditionEvents();
            button.click();

            expect(createListPicker).toHaveBeenCalledTimes(2);
        });
    });

    describe(`initListPickersInArtifactCreationView()`, () => {
        const initListPickersInArtifactCreationView = (): void => {
            const creator = ListPickersCreator(doc, SelectWrappedByListPickerStore());
            return creator.initListPickersInArtifactCreationView();
        };

        it.each([["sb"], ["msb"]])(
            "should init list-pickers when the artifact view is in creation mode",
            (type: string) => {
                const select = createArtifactFormElementFieldInEditionModeOfType(type);
                initListPickersInArtifactCreationView();

                expect(createListPicker).toHaveBeenCalledWith(select, {
                    locale: LOCALE,
                    is_filterable: true,
                    none_value: null,
                });
            },
        );
    });

    describe(`initListPickersPostUpdateErrorView()`, () => {
        const initListPickersPostUpdateErrorView = (): void => {
            const creator = ListPickersCreator(doc, SelectWrappedByListPickerStore());
            return creator.initListPickersPostUpdateErrorView();
        };

        it.each([["sb"], ["msb"]])(
            "should init list-pickers when list fields are in edition mode",
            (type: string) => {
                const select = createArtifactFormElementFieldInEditionModeOfType(type, true);
                initListPickersPostUpdateErrorView();

                expect(createListPicker).toHaveBeenCalledWith(select, {
                    locale: LOCALE,
                    is_filterable: true,
                    none_value: null,
                });
            },
        );

        it.each([["sb"], ["msb"]])(
            "should init list-pickers with none value when a none option exist",
            (type: string) => {
                const select = createArtifactFormElementFieldInEditionModeOfType(type, true);
                const option_none = doc.createElement("option");
                option_none.value = "100";
                option_none.innerText = "None";

                select.add(option_none);

                initListPickersPostUpdateErrorView();

                expect(createListPicker).toHaveBeenCalledWith(select, {
                    locale: LOCALE,
                    is_filterable: true,
                    none_value: "100",
                });
            },
        );
    });
});
