/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { HostElement } from "./ComputedField";
import { ComputedField, getDisplayedValue, onInput, validateInput } from "./ComputedField";
import { setCatalog } from "../../gettext-catalog";

const FIELD_ID = 371;

const noop = (): void => {
    //Do nothing
};

const getDocument = (): Document => document.implementation.createHTMLDocument();

function getHost(data?: Partial<ComputedField>): HostElement {
    return {
        fieldId: FIELD_ID,
        label: "Computed Field",
        required: false,
        disabled: false,
        autocomputed: false,
        manualValue: 5,
        value: 8,
        dispatchEvent: noop,
        ...data,
    } as unknown as HostElement;
}

describe(`ComputedField`, () => {
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
    });

    describe(`input events`, () => {
        it.each([
            ["when the input is emptied", "empty string", "", ""],
            ["when the input's value is a number", "the number", "26.79", 26.79],
            ["when the input's value is not a number", "empty string", "not a number", ""],
        ])(
            `%s, it dispatches a "value-changed" event with manual value as %s`,
            (when_statement, expected_statement, input_value, expected_manual_value) => {
                const host = getHost({ autocomputed: false });
                const dispatchEvent = jest.spyOn(host, "dispatchEvent");
                const inner_input = getDocument().createElement("input");
                inner_input.addEventListener("input", (event) => onInput(host, event));

                inner_input.value = input_value;
                inner_input.dispatchEvent(new InputEvent("input"));

                const event = dispatchEvent.mock.calls[0][0];
                if (!(event instanceof CustomEvent)) {
                    throw new Error("Expected a CustomEvent");
                }
                expect(event.type).toBe("value-changed");
                expect(event.detail.field_id).toBe(FIELD_ID);
                expect(event.detail.autocomputed).toBe(false);
                expect(event.detail.manual_value).toBe(expected_manual_value);
            }
        );
    });

    describe(`Template`, () => {
        let target: ShadowRoot;
        beforeEach(() => {
            target = getDocument().createElement("div") as unknown as ShadowRoot;
        });

        it(`when the field is switched to auto-computed,
            it dispatches a "value-changed" event with autocomputed true and manual value as empty string`, () => {
            const host = getHost({ manualValue: 3, autocomputed: false });
            const dispatchEvent = jest.spyOn(host, "dispatchEvent");
            const update = ComputedField.content(host);
            update(host, target);

            const autocompute_button = target.querySelector("[data-test=switch-to-auto]");
            if (!(autocompute_button instanceof HTMLButtonElement)) {
                throw new Error("Could not find the AutoCompute button");
            }
            autocompute_button.click();

            expect(host.autocomputed).toBe(true);
            expect(host.manualValue).toBe("");
            const event = dispatchEvent.mock.calls[0][0];
            if (!(event instanceof CustomEvent)) {
                throw new Error("Expected a CustomEvent");
            }
            expect(event.type).toBe("value-changed");
            expect(event.detail.field_id).toBe(FIELD_ID);
            expect(event.detail.autocomputed).toBe(true);
            expect(event.detail.manual_value).toBe("");
        });

        it(`when the field is switched to manual,
            it dispatches a "value-changed" event with autocomputed false and manual value as empty string`, () => {
            const host = getHost({ manualValue: "", autocomputed: true });
            const dispatchEvent = jest.spyOn(host, "dispatchEvent");
            const update = ComputedField.content(host);
            update(host, target);

            const edit_button = target.querySelector("[data-test=switch-to-manual]");
            if (!(edit_button instanceof HTMLButtonElement)) {
                throw new Error("Could not find the Edit button");
            }
            edit_button.click();

            expect(host.autocomputed).toBe(false);
            const event = dispatchEvent.mock.calls[0][0];
            if (!(event instanceof CustomEvent)) {
                throw new Error("Expected a CustomEvent");
            }
            expect(event.type).toBe("value-changed");
            expect(event.detail.field_id).toBe(FIELD_ID);
            expect(event.detail.autocomputed).toBe(false);
            expect(event.detail.manual_value).toBe("");
        });

        it(`when the field is disabled, it only renders its value`, () => {
            const host = getHost({ disabled: true, value: 95.1 });
            const update = ComputedField.content(host);
            update(host, target);

            const div = target as unknown as HTMLDivElement;
            expect(div.innerHTML).toMatchSnapshot();
        });
    });

    it.each([
        ["the auto-computed value if not null", 89.5, null, "89.5"],
        ["the manual value if not null", null, 71.7, "71.7"],
        ["the translated empty string if both are null", null, null, "Empty"],
    ])(
        `when the field is disabled, it renders %s`,
        (rendered_statement, computed_value, manual_value, expected_result) => {
            const host = getHost({ value: computed_value, manualValue: manual_value });
            const rendered_value = getDisplayedValue(host);
            expect(rendered_value).toBe(expected_result);
        }
    );

    describe(`validateInput()`, () => {
        it.each([
            [null, "accept null", null],
            ["not a number", "set to null", null],
            ["66.3", "accept the number", 66.3],
        ])(`given %s, it will %s`, (given_value, will_statement, expected_value) => {
            expect(validateInput(getHost(), given_value)).toBe(expected_value);
        });
    });
});
