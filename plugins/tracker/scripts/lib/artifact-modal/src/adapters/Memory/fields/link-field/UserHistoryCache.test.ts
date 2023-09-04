/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { LinkableArtifact } from "../../../../domain/fields/link-field/LinkableArtifact";
import { LinkableArtifactStub } from "../../../../../tests/stubs/LinkableArtifactStub";
import type { UserIdentifier } from "../../../../domain/UserIdentifier";
import { UserIdentifierStub } from "../../../../../tests/stubs/UserIdentifierStub";
import type { RetrieveUserHistory } from "../../../../domain/fields/link-field/RetrieveUserHistory";
import { RetrieveUserHistoryStub } from "../../../../../tests/stubs/RetrieveUserHistoryStub";
import { okAsync } from "neverthrow";
import { UserHistoryCache } from "./UserHistoryCache";

describe(`UserHistoryCache`, () => {
    let first_entry: LinkableArtifact,
        second_entry: LinkableArtifact,
        user_identifier: UserIdentifier;

    beforeEach(() => {
        first_entry = LinkableArtifactStub.withDefaults({ id: 867 });
        second_entry = LinkableArtifactStub.withDefaults({ id: 628 });
        user_identifier = UserIdentifierStub.fromUserId(125);
    });

    const getCache = (): RetrieveUserHistory => {
        const current_retriever = RetrieveUserHistoryStub.withUserHistory(
            okAsync([first_entry, second_entry]),
        );
        return UserHistoryCache(current_retriever);
    };

    it(`delegates to the user history retriever the first call,
        and on later calls it returns the cached result`, async () => {
        const cache = getCache();
        const first_call_result = await cache.getUserArtifactHistory(user_identifier);
        if (!first_call_result.isOk()) {
            throw new Error("Expected an Ok");
        }
        expect(first_call_result.value).toHaveLength(2);
        expect(first_call_result.value).toContain(first_entry);
        expect(first_call_result.value).toContain(second_entry);

        const second_call_result = await cache.getUserArtifactHistory(user_identifier);
        if (!second_call_result.isOk()) {
            throw new Error("Expected an Ok");
        }
        expect(second_call_result.value).toStrictEqual(first_call_result.value);
    });
});
