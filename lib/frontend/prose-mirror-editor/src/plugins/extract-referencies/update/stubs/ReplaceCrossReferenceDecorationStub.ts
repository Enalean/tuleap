/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import type { ReplaceCrossReferenceDecoration } from "../CrossReferenceDecorationReplacer";
import type { Transaction } from "prosemirror-state";
import type { UpdatedCrossReference } from "../../../../helpers/UpdatedCrossReferenceTransactionDispatcher";
import type { Decoration } from "prosemirror-view";

type StubReplaceCrossReferenceDecoration = ReplaceCrossReferenceDecoration & {
    getReplacedDecoration(): Decoration | null;
    getReplacingReference(): UpdatedCrossReference | null;
};

export const ReplaceCrossReferenceDecorationStub = {
    willNotReplace: (): ReplaceCrossReferenceDecoration => ({
        replace: (): Transaction => {
            throw new Error(
                "ReplaceCrossReferenceDecorationStub::replace() was not expected to be called.",
            );
        },
    }),
    willReplaceWithTransaction: (transaction: Transaction): StubReplaceCrossReferenceDecoration => {
        let replaced_decoration: Decoration | null = null;
        let replacing_reference: UpdatedCrossReference | null = null;

        return {
            getReplacedDecoration: (): Decoration | null => replaced_decoration,
            getReplacingReference: (): UpdatedCrossReference | null => replacing_reference,
            replace: (decoration_to_replace, updated_cross_reference): Transaction => {
                replaced_decoration = decoration_to_replace;
                replacing_reference = updated_cross_reference;

                return transaction;
            },
        };
    },
};
