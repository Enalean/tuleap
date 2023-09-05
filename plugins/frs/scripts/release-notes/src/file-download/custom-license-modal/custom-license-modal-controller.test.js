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
import tuleap_frs_module from "../../app.js";
import BaseController from "./custom-license-modal-controller";

import "angular-mocks";

describe(`CustomLicenseModalController`, () => {
    let CustomLicenseModalController, acceptCallback, modal_instance, SharedPropertiesService;

    beforeEach(() => {
        angular.mock.module(tuleap_frs_module);

        let $controller;
        angular.mock.inject(function (_$controller_, _SharedPropertiesService_) {
            $controller = _$controller_;
            SharedPropertiesService = _SharedPropertiesService_;
        });

        acceptCallback = jest.fn();
        modal_instance = { tlp_modal: { hide: jest.fn() } };

        jest.spyOn(SharedPropertiesService, "getCustomLicenseAgreement").mockReturnValue({
            title: "Bitterheartedness tacheometer",
            content: `<p>enwreathe unbordered precatively atypical betimes counterpray faucitis premake unsurging</p>`,
        });

        CustomLicenseModalController = $controller(BaseController, {
            modal_instance,
            acceptCallback,
            SharedPropertiesService,
        });
    });

    describe(`init`, () => {
        it(`will publish custom license information`, () => {
            expect(CustomLicenseModalController.title).toBe("Bitterheartedness tacheometer");
            expect(CustomLicenseModalController.content).toBe(
                `<p>enwreathe unbordered precatively atypical betimes counterpray faucitis premake unsurging</p>`,
            );
        });
    });

    describe(`accept()`, () => {
        it(`will close the modal and call the acceptCallback`, () => {
            CustomLicenseModalController.accept();

            expect(modal_instance.tlp_modal.hide).toHaveBeenCalled();
            expect(acceptCallback).toHaveBeenCalled();
        });
    });
});
