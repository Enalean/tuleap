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

import { beforeEach, describe, expect, it } from "vitest";
import { Fault } from "@tuleap/fault";
import { ErrorMessageFormatter } from "./ErrorMessageFormatter";
import { setTranslator } from "../../gettext-catalog";
import { LinkRetrievalFault } from "../../domain/links/LinkRetrievalFault";
import { MatchingArtifactRetrievalFault } from "../../domain/MatchingArtifactRetrievalFault";
import { PossibleParentsRetrievalFault } from "../../domain/PossibleParentsRetrievalFault";
import { UserHistoryRetrievalFault } from "../../domain/UserHistoryRetrievalFault";
import { SearchArtifactsFault } from "../../domain/SearchArtifactsFault";

const FAULT_MESSAGE = "An error occurred";

describe(`ErrorMessageFormatter`, () => {
    beforeEach(() => {
        setTranslator({ gettext: (msgid) => msgid });
    });

    const format = (fault: Fault): string => {
        return ErrorMessageFormatter().format(fault);
    };

    it(`casts a Fault to string`, () => {
        const fault = Fault.fromMessage(FAULT_MESSAGE);
        expect(format(fault)).toBe(String(fault));
    });

    function* generateFaults(): Generator<[string, Fault]> {
        const previous = Fault.fromMessage(FAULT_MESSAGE);
        yield ["LinkRetrievalFault", LinkRetrievalFault(previous)];
        yield ["MatchingArtifactRetrievalFault", MatchingArtifactRetrievalFault(previous)];
        yield ["PossibleParentsRetrievalFault", PossibleParentsRetrievalFault(previous)];
        yield ["UserHistoryRetrievalFault", UserHistoryRetrievalFault(previous)];
        yield ["SearchArtifactsFault", SearchArtifactsFault(previous)];
    }

    it.each([...generateFaults()])(`translates a message for %s`, (fault_name, fault) => {
        const message = format(fault);
        expect(message).toContain(FAULT_MESSAGE);
        expect(message).not.toBe(FAULT_MESSAGE);
    });
});
