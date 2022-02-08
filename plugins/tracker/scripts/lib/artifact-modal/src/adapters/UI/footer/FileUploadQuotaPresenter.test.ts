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

import { computePercentage, FileUploadQuotaPresenter } from "./FileUploadQuotaPresenter";
import { UserTemporaryFileQuotaStub } from "../../../../tests/stubs/UserTemporaryFileQuotaStub";

const DISK_USAGE = 2000;
const DISK_QUOTA = 100000;

describe(`FileUploadQuotaPresenter`, () => {
    it(`builds from a user's temporary file quota`, () => {
        const presenter = FileUploadQuotaPresenter.fromQuota(
            UserTemporaryFileQuotaStub.withValues(DISK_QUOTA, DISK_USAGE)
        );

        expect(presenter.disk_quota_in_bytes).toBe(DISK_QUOTA);
        expect(presenter.disk_usage_in_bytes).toBe(DISK_USAGE);
        expect(presenter.disk_usage_percentage).toBe(2);
    });

    describe(`computePercentage()`, () => {
        let disk_quota: number;
        beforeEach(() => {
            disk_quota = DISK_QUOTA;
        });

        const runPercentage = (): number =>
            computePercentage(UserTemporaryFileQuotaStub.withValues(disk_quota, 33333));

        it(`given a positive quota, it floors the percentage to two decimal numbers precision`, () => {
            expect(runPercentage()).toBe(33.33);
        });

        it(`given a negative quota, it returns zero`, () => {
            disk_quota = -1;
            expect(runPercentage()).toBe(0);
        });

        it(`given a quota of zero, it returns zero`, () => {
            disk_quota = 0;
            expect(runPercentage()).toBe(0);
        });
    });
});
