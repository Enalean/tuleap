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

import type { AddLinkMarkedForRemoval } from "../../../src/domain/links/AddLinkMarkedForRemoval";

export interface AddLinkMarkedForRemovalStub extends AddLinkMarkedForRemoval {
    getCallCount(): number;
}

export const AddLinkMarkedForRemovalStub = {
    withCount: (): AddLinkMarkedForRemovalStub => {
        let call_count = 0;
        return {
            addLinkMarkedForRemoval(): void {
                call_count++;
            },
            getCallCount: (): number => call_count,
        };
    },
};
