<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

<template functional>
    <div class="tlp-form-element">
        <label class="tlp-label tlp-checkbox" v-bind:for="props.id">
            <input
                v-bind:id="props.id"
                type="checkbox"
                name="is_used"
                value="1"
                v-bind:checked="props.value"
                v-bind:disabled="props.value === false && props.disabledReason !== ''"
                data-test="service-is-used"
            />
            <translate>Enabled</translate>
        </label>
        <div
            v-if="props.value === false && props.disabledReason !== ''"
            class="tlp-text-info"
            data-test="service-disabled-message"
        >
            {{ props.disabledReason }}
        </div>
    </div>
</template>
