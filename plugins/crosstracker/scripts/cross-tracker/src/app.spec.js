/*
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

import "tlp-mocks";

import Vue from "vue";
import GettextPlugin from "vue-gettext";

Vue.use(GettextPlugin, {
    translations: {},
    silent: true
});

import "./api/rest-querier.spec.js";
import "./components/ArtifactTable.spec.js";
import "./components/ExportCSVButton.spec.js";
import "./CrossTrackerWidget.spec.js";
import "./reading-mode/ReadingMode.spec.js";
import "./store/mutations.spec.js";
import "./writing-mode/QueryEditor.spec.js";
import "./writing-mode/TrackerListWritingMode.spec.js";
import "./writing-mode/TrackerSelection.spec.js";
import "./writing-mode/WritingMode.spec.js";
