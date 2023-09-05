/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { Option } from "@tuleap/option";
import type { ParentArtifact } from "./ParentArtifact";
import type { ParentArtifactIdentifier } from "./ParentArtifactIdentifier";
import type { RetrieveParent } from "./RetrieveParent";
import type { DispatchEvents } from "../DispatchEvents";
import { WillNotifyFault } from "../WillNotifyFault";

export type ParentFeedbackControllerType = {
    getParentArtifact(): PromiseLike<Option<ParentArtifact>>;
};

export const ParentFeedbackController = (
    retriever: RetrieveParent,
    event_dispatcher: DispatchEvents,
    parent_option: Option<ParentArtifactIdentifier>,
): ParentFeedbackControllerType => ({
    getParentArtifact: (): PromiseLike<Option<ParentArtifact>> => {
        return parent_option.mapOr(
            (parent_artifact_identifier) =>
                retriever.getParent(parent_artifact_identifier).match(
                    (artifact): Option<ParentArtifact> => Option.fromValue(artifact),
                    (fault) => {
                        event_dispatcher.dispatch(WillNotifyFault(fault));
                        return Option.nothing();
                    },
                ),
            Promise.resolve(Option.nothing()),
        );
    },
});
