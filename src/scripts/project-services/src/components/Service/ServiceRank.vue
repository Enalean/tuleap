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
        <label class="tlp-label" v-bind:for="props.id">
            <translate>Rank</translate>
            <i class="fa fa-asterisk"></i>
        </label>
        <input
            type="number"
            class="tlp-input"
            v-bind:id="props.id"
            name="rank"
            placeholder="150"
            size="5"
            maxlength="5"
            v-bind:min="props.minimal_rank"
            required
            v-bind:value="props.value"
        />
    </div>
</template>
