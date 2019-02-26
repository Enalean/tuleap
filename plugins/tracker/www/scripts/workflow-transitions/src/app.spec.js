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

import "tlp-mocks";

import "./api/rest-querier.spec.js";
import "./store/getters.spec.js";
import "./store/transition-modal/transition-mutations.spec.js";
import "./store/transition-modal/transition-getters.spec.js";
import "./store/transition-modal/transition-actions.spec.js";
import "./store/exception-handler.spec.js";
import "./store/mutations.spec.js";
import "./store/actions.spec.js";
import "./components/TransitionMatrixContent.spec.js";
import "./components/TransitionRulesEnforcementWarning.spec.js";
import "./components/BaseTrackerWorkflowTransitions.spec.js";
import "./components/TransitionDeleter.spec.js";
import "./components/TransitionModal/PostAction/RunJobAction.spec.js";
import "./components/TransitionModal/PostAction/IntInput.spec.js";
import "./components/TransitionModal/PostAction/DateInput.spec.js";
import "./components/TransitionModal/PostAction/SetValueAction.spec.js";
import "./components/TransitionModal/PostAction/FloatInput.spec.js";
import "./components/TransitionModal/FilledPreConditionsSection.spec.js";
import "./components/TransitionModal/PostActionsSection.spec.js";
import "./components/FirstConfiguration/FirstConfigurationSections.spec.js";
import "./components/TransitionMatrixColumnHeader.spec.js";
import "./support/string.spec.js";
