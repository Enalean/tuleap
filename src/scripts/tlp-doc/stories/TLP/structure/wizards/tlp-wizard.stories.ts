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

type WizardProps = {
    current_step: string;
    nb_step: string;
    first_label: string;
};

const new_line = "\n";

function getClasses(args: WizardProps, step: number): string {
    let classes = "tlp-wizard-step-";
    const current_step_number = parseInt(args.current_step, 10);
    if (current_step_number === step) {
        classes += "current";
    } else if (current_step_number < step) {
        classes += "next";
    } else {
        classes += "previous";
    }
    return classes;
}

function createSteps(args: WizardProps): TemplateResult[] {
    const steps: TemplateResult[] = [];
    const nb_step = parseInt(args.nb_step, 10);
    // prettier-ignore
    steps.push(html`<span class="${getClasses(args, 1)}">${args.first_label}</span>${nb_step > 1 ? new_line : ``}`);
    for (let i = 2; i <= nb_step; i++) {
        // prettier-ignore
        steps.push(html`    <span class="${getClasses(args, i)}">Step ${i}</span>${i !== nb_step ? new_line : ``}`);
    }
    return steps;
}

function getTemplate(args: WizardProps): TemplateResult {
    // prettier-ignore
    return html`
<nav class="tlp-wizard">
    ${createSteps(args)}
</nav>`;
}

const meta: Meta<WizardProps> = {
    title: "TLP/Structure & Navigation/Wizards",
    render: (args: WizardProps) => {
        return getTemplate(args);
    },
    argTypes: {
        nb_step: {
            name: "Number of steps",
            description: "Set the number of steps you want in the example",
            control: { type: "select" },
            options: ["1", "2", "3", "4", "5", "6", "7", "8", "9", "10"],
            table: {
                type: { summary: undefined },
            },
        },
        current_step: {
            name: "Step current",
            description: "Set the current step by applying",
            control: { type: "select" },
            options: ["1", "2", "3", "4", "5", "6", "7", "8", "9", "10"],
            table: {
                type: { summary: ".tlp-wizard-step-current" },
            },
        },
        first_label: {
            name: "First label",
            description: "Set the label of the first step",
        },
    },
    args: {
        nb_step: "4",
        current_step: "3",
        first_label: "Step 1",
    },
};

export default meta;
type Story = StoryObj;

export const Wizard: Story = {};
