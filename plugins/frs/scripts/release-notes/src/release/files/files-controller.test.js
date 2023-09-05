/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import angular from "angular";
import tuleap_frs_module from "../../app";
import BaseController from "./files-controller";

import "angular-mocks";

describe(`FilesController`, () => {
    let $controller, SharedPropertiesService;
    beforeEach(() => {
        angular.mock.module(tuleap_frs_module);
        angular.mock.inject(function (_$rootScope_, _$controller_, _SharedPropertiesService_) {
            $controller = _$controller_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        jest.spyOn(SharedPropertiesService, "getRelease").mockImplementation(() => {});
        jest.spyOn(SharedPropertiesService, "getCustomLicenseAgreement").mockImplementation(
            () => {},
        );
    });

    describe(`isEmpty()`, () => {
        it(`when there are files in the release, it will return false`, () => {
            const release = {
                files: [{ id: 12 }],
                links: [],
                release_note: "",
                changelog: "",
            };
            jest.spyOn(SharedPropertiesService, "getRelease").mockReturnValue(release);

            const FilesController = $controller(BaseController);
            expect(FilesController.isEmpty()).toBe(false);
        });

        it(`when there are links in the release, it will return false`, () => {
            const release = {
                files: [],
                links: [{ id: 23 }],
                release_note: "",
                changelog: "",
            };
            jest.spyOn(SharedPropertiesService, "getRelease").mockReturnValue(release);

            const FilesController = $controller(BaseController);
            expect(FilesController.isEmpty()).toBe(false);
            jest.spyOn(SharedPropertiesService, "getRelease").mockReturnValue(release);
        });

        it(`when there is a release note, it will return false`, () => {
            const release = {
                files: [],
                links: [],
                release_note: "Lorem ipsum dolor sit amet",
                changelog: "",
            };
            jest.spyOn(SharedPropertiesService, "getRelease").mockReturnValue(release);

            const FilesController = $controller(BaseController);
            expect(FilesController.isEmpty()).toBe(false);
            jest.spyOn(SharedPropertiesService, "getRelease").mockReturnValue(release);
        });

        it(`when there is a changelog, it will return false`, () => {
            const release = {
                files: [],
                links: [],
                release_note: "",
                changelog: "Lorem ipsum dolor sit amet",
            };
            jest.spyOn(SharedPropertiesService, "getRelease").mockReturnValue(release);

            const FilesController = $controller(BaseController);
            expect(FilesController.isEmpty()).toBe(false);
            jest.spyOn(SharedPropertiesService, "getRelease").mockReturnValue(release);
        });

        it(`when there is none of the above, it will return true`, () => {
            const release = {
                files: [],
                links: [],
                release_note: "",
                changelog: "",
            };
            jest.spyOn(SharedPropertiesService, "getRelease").mockReturnValue(release);

            const FilesController = $controller(BaseController);
            expect(FilesController.isEmpty()).toBe(true);
            jest.spyOn(SharedPropertiesService, "getRelease").mockReturnValue(release);
        });
    });
});
