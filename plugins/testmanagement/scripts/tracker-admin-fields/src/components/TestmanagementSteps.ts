/*
 * Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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
import type { UpdateFunction } from "hybrids";
import { gettext_provider } from "../gettext-provider";
import "./StepDefinitionArrow";
import style from "./test-management-steps.scss?inline";

export const TAG = "tuleap-test-management-steps";

type TestManagementSteps = {
    are_results_badges_displayed: boolean;
};

type InternalTestManagementSteps = TestManagementSteps &
    Readonly<{
        render(): HTMLElement;
    }>;

type Status = "passed" | "failed";
type StepWithResult = {
    description: string;
    expected_results: string;
    status: Status;
};

const steps_with_results: StepWithResult[] = [
    {
        description: gettext_provider.gettext("Step 1"),
        expected_results: gettext_provider.gettext("Result of step 1"),
        status: "passed",
    },
    {
        description: gettext_provider.gettext("Step 2"),
        expected_results: gettext_provider.gettext("Result of step 2"),
        status: "failed",
    },
];

function getStatusBadgeClasses(status: Status): string {
    if (status === "failed") {
        return "tlp-badge-danger";
    }

    return "tlp-badge-success";
}

function getBadgeLabel(status: Status): string {
    if (status === "failed") {
        return gettext_provider.gettext("Failed");
    }

    return gettext_provider.gettext("Passed");
}

const renderTestmanagementSteps = (
    host: InternalTestManagementSteps,
): UpdateFunction<InternalTestManagementSteps> =>
    html`
        <div class="steps">
            ${steps_with_results.map(
                (step, rank) => html`
                    <div class="step" data-test="step">
                        <span class="step-rank">${rank + 1}</span>
                        <div class="step-definition">
                            <div class="step-description">${step.description}</div>
                            <section class="step-results">
                                <tuleap-step-definition-arrow></tuleap-step-definition-arrow>
                                ${gettext_provider.gettext("Expected results")}
                                <div class="step-expected">${step.expected_results}</div>
                            </section>
                            ${host.are_results_badges_displayed &&
                            html`
                                <span
                                    class="step-execution-status ${getStatusBadgeClasses(
                                        step.status,
                                    )}"
                                >
                                    ${getBadgeLabel(step.status)}
                                </span>
                            `}
                        </div>
                    </div>
                `,
            )}
        </div>
    `.style(style);

define<InternalTestManagementSteps>({
    tag: TAG,
    are_results_badges_displayed: {
        value: false,
    },
    render: renderTestmanagementSteps,
});
