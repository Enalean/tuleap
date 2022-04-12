/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

type AngularJSRootScope = {
    $apply(): void;
};

type ResolutionCallback = (value: unknown) => void;
type RejectionCallback = (reason: unknown) => void;

type AngularJSPromise = {
    then(resolver: ResolutionCallback): AngularJSPromise;
    catch(rejecter: RejectionCallback): AngularJSPromise;
};

export const createAngularPromiseWrapper =
    ($rootScope: AngularJSRootScope) =>
    (angular_promise: AngularJSPromise): Promise<unknown> =>
        new Promise((resolve, reject) => {
            angular_promise
                .then((result) => {
                    resolve(result);
                })
                .catch((error) => {
                    reject(error);
                });
            $rootScope.$apply();
        });
