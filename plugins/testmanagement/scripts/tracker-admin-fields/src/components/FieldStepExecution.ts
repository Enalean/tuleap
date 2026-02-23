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
import "./TestmanagementSteps";

export const TAG = "tuleap-field-ttmstepexec";

export type FieldStepExecution = Readonly<{
    render(): HTMLElement;
}>;

export type InternalFieldStepExecution = Readonly<FieldStepExecution> & {};

export type HostElement = InternalFieldStepExecution & HTMLElement;

const renderFieldStepExecution = (): UpdateFunction<InternalFieldStepExecution> => html`
    <tuleap-test-management-steps are_results_badges_displayed="${true}" />
`;

define<InternalFieldStepExecution>({
    tag: TAG,
    render: renderFieldStepExecution,
});
