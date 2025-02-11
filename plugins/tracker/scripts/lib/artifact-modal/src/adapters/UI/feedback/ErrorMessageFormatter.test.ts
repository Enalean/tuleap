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

import { Fault } from "@tuleap/fault";
import { ErrorMessageFormatter as LinkFieldFormatter } from "@tuleap/plugin-tracker-link-field";
import { ErrorMessageFormatter } from "./ErrorMessageFormatter";
import { setCatalog } from "../../../gettext-catalog";
import { ParentRetrievalFault } from "../../../domain/parent/ParentRetrievalFault";
import { CommentsRetrievalFault } from "../../../domain/comments/CommentsRetrievalFault";
import { FileUploadFault } from "../../../domain/fields/file-field/FileUploadFault";
import { ArtifactCreationFault } from "../../../domain/ArtifactCreationFault";

const FAULT_MESSAGE = "An error occurred";

describe(`ErrorMessageFormatter`, () => {
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
    });

    const format = (fault: Fault): string => {
        return ErrorMessageFormatter(LinkFieldFormatter()).format(fault);
    };

    it(`casts a Fault to string`, () => {
        const fault = Fault.fromMessage(FAULT_MESSAGE);
        expect(format(fault)).toBe(String(fault));
    });

    function* generateFaults(): Generator<[string, Fault]> {
        const previous = Fault.fromMessage(FAULT_MESSAGE);
        yield ["ParentRetrievalFault", ParentRetrievalFault(previous)];
        yield ["CommentsRetrievalFault", CommentsRetrievalFault(previous)];
        yield ["ArtifactCreationFault", ArtifactCreationFault(previous)];
        yield ["FileUploadFault", FileUploadFault(previous, "sempiternity_ringgiver.txt")];
    }

    it.each([...generateFaults()])(`translates a message for %s`, (fault_name, fault) => {
        const message = format(fault);
        expect(message).toContain(FAULT_MESSAGE);
        expect(message).not.toBe(FAULT_MESSAGE);
    });
});
