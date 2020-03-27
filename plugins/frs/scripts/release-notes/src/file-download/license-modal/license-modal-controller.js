/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

export default LicenseModalController;

LicenseModalController.$inject = ["modal_instance", "acceptCallback", "SharedPropertiesService"];

function LicenseModalController(modal_instance, acceptCallback, SharedPropertiesService) {
    const self = this;

    const platform_license_info = SharedPropertiesService.getPlatformLicenseInfo();

    Object.assign(self, {
        accept,

        exchange_policy_url: platform_license_info.exchange_policy_url,
        organisation_name: platform_license_info.organisation_name,
        contact_email: platform_license_info.contact_email,
    });

    function accept() {
        modal_instance.tlp_modal.hide();
        acceptCallback();
    }
}
