<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <section class="tlp-modal-body-section">
        <div class="tlp-form-element">
            <label class="tlp-label tlp-label-skeleton">
                <span class="tlp-skeleton-text"></span>
            </label>
            <select
                class="tlp-select tlp-skeleton-field tracker-workflow-transition-modal-authorized-ugroups"
                disabled
            ></select>
        </div>
        <div class="tlp-form-element">
            <label class="tlp-label tlp-label-skeleton">
                <span class="tlp-skeleton-text"></span>
            </label>
            <select class="tlp-select tlp-skeleton-field" disabled></select>
        </div>
        <label class="tlp-label tlp-checkbox" slot="comment-not-empty">
            <input type="checkbox" class="tlp-skeleton-field" disabled />
            <span class="tlp-skeleton-text"></span>
        </label>
    </section>
</template>

<script>
export default {
    name: "PreConditionsSkeleton",
};
</script>
