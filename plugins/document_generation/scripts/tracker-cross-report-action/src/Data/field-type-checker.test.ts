/**
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

import type { ArtifactReportResponseFieldValueWithExtraFields } from "../type";
import { isFieldTakenIntoAccount } from "./field-type-checker";

describe("field-type-checker", () => {
    it("returns false if field is an Artifact Link field", (): void => {
        const field_value: ArtifactReportResponseFieldValueWithExtraFields = {
            field_id: 1,
            type: "art_link",
        } as ArtifactReportResponseFieldValueWithExtraFields;

        expect(isFieldTakenIntoAccount(field_value)).toBeFalsy();
    });
    it("returns false if field is an Attachment field", (): void => {
        const field_value: ArtifactReportResponseFieldValueWithExtraFields = {
            field_id: 1,
            type: "file",
        } as ArtifactReportResponseFieldValueWithExtraFields;

        expect(isFieldTakenIntoAccount(field_value)).toBeFalsy();
    });
    it("returns false if field is a Cross reference field", (): void => {
        const field_value: ArtifactReportResponseFieldValueWithExtraFields = {
            field_id: 1,
            type: "cross",
        } as ArtifactReportResponseFieldValueWithExtraFields;

        expect(isFieldTakenIntoAccount(field_value)).toBeFalsy();
    });
    it("returns false if field is a Permission on artifact field", (): void => {
        const field_value: ArtifactReportResponseFieldValueWithExtraFields = {
            field_id: 1,
            type: "perm",
        } as ArtifactReportResponseFieldValueWithExtraFields;

        expect(isFieldTakenIntoAccount(field_value)).toBeFalsy();
    });
    it("returns false if field is a TestManagement step definition field", (): void => {
        const field_value: ArtifactReportResponseFieldValueWithExtraFields = {
            field_id: 1,
            type: "ttmstepdef",
        } as ArtifactReportResponseFieldValueWithExtraFields;

        expect(isFieldTakenIntoAccount(field_value)).toBeFalsy();
    });
    it("returns false if field is a TestManagement step execution field", (): void => {
        const field_value: ArtifactReportResponseFieldValueWithExtraFields = {
            field_id: 1,
            type: "ttmstepexec",
        } as ArtifactReportResponseFieldValueWithExtraFields;

        expect(isFieldTakenIntoAccount(field_value)).toBeFalsy();
    });
    it("returns false if field is a Burndown chart field", (): void => {
        const field_value: ArtifactReportResponseFieldValueWithExtraFields = {
            field_id: 1,
            type: "burndown",
        } as ArtifactReportResponseFieldValueWithExtraFields;

        expect(isFieldTakenIntoAccount(field_value)).toBeFalsy();
    });
    it("returns false if field is a Burnup chart field", (): void => {
        const field_value: ArtifactReportResponseFieldValueWithExtraFields = {
            field_id: 1,
            type: "burnup",
        } as ArtifactReportResponseFieldValueWithExtraFields;

        expect(isFieldTakenIntoAccount(field_value)).toBeFalsy();
    });
    it("returns false if field is an Encrypted field", (): void => {
        const field_value: ArtifactReportResponseFieldValueWithExtraFields = {
            field_id: 1,
            type: "Encrypted",
        } as ArtifactReportResponseFieldValueWithExtraFields;

        expect(isFieldTakenIntoAccount(field_value)).toBeFalsy();
    });
});
