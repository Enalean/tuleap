/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { Meta, StoryObj } from "@storybook/web-components-vite";
import type { TemplateResult } from "lit";
import { html } from "lit";
import "./DatePickerWrapper";

type DatePickerProps = {
    time_picker: boolean;
};

function getTemplate(args: DatePickerProps): TemplateResult {
    //prettier-ignore
    return args.time_picker ? html`
<div class="tlp-form-element">
    <label for="datetime-picker" class="tlp-label">Pick a date and a time</label>
    <div class="tlp-form-element tlp-form-element-prepend">
        <span class="tlp-prepend"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i></span>
        <input type="text"
               id="datetime-picker"
               class="tlp-input"
               data-enabletime="true"
               size="19">
    </div>
</div>` : html`
<div class="tlp-form-element">
    <label for="date-picker" class="tlp-label">Pick a date</label>
    <div class="tlp-form-element tlp-form-element-prepend">
        <span class="tlp-prepend"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i></span>
        <input type="text" id="date-picker" class="tlp-input" size="11">
    </div>
</div>`;
}

const meta: Meta<DatePickerProps> = {
    title: "TLP/Forms/Date picker",
    parameters: {
        controls: {
            exclude: ["time_picker"],
        },
    },
    render: (args) => {
        return getTemplate(args);
    },
    args: {
        time_picker: false,
    },
    decorators: [
        (story): TemplateResult =>
            html`<tuleap-date-picker-wrapper>${story()}</tuleap-date-picker-wrapper>`,
    ],
};
export default meta;
type Story = StoryObj<DatePickerProps>;

export const DatePicker: Story = {};

export const DateTimePicker: Story = {
    args: {
        time_picker: true,
    },
};
