<!--
  - Copyright (c) Enalean, 2018-Present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="tlp-modal tlp-modal-danger" role="dialog">
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title">
                <translate>Oops</translate>
            </h1>
            <button class="tlp-modal-close" type="button" data-dismiss="modal" aria-label="Close">
                <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <p>
                <translate>
                    An error occurred while retrieving needed information to create a pull request.
                </translate>
            </p>
            <p>
                <translate>
                    Refresh the page and try again; if the error persists, contact your
                    administrator.
                </translate>
            </p>
        </div>
        <div class="tlp-modal-footer tlp-modal-footer-large">
            <button
                type="submit"
                class="tlp-button-danger tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                <translate>Close</translate>
            </button>
        </div>
    </div>
</template>
