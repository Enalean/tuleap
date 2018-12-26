<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
  -
  -->

<template functional>
    <div class="tlp-modal-footer">
        <button
            type="button"
            class="tlp-button-primary tlp-button-outline tlp-modal-action"
            data-dismiss="modal"
            v-translate
        >
            Cancel
        </button>
        <button
            type="submit"
            class="tlp-button-primary tlp-modal-action"
            v-bind:disabled="is_loading"
        >
            <i v-if="props.is_loading" class="fa fa-plus tlp-button-icon fa-spin fa-spinner"></i>
            <translate>Create document</translate>
        </button>
    </div>
</template>
