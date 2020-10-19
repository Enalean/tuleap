<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ActionButtons;

use Codendi_HTMLPurifier;
use PFUser;
use Tracker_Artifact_Changeset_IncomingMailGoldenRetriever;
use Tuleap\Tracker\Artifact\Artifact;

class ArtifactIncomingEmailButtonPresenterBuilder
{
    /**
     * @var Tracker_Artifact_Changeset_IncomingMailGoldenRetriever
     */
    private $mail_golden_retriever;

    public function __construct(Tracker_Artifact_Changeset_IncomingMailGoldenRetriever $mail_golden_retriever)
    {
        $this->mail_golden_retriever = $mail_golden_retriever;
    }

    public function getIncomingEmailButton(PFUser $user, Artifact $artifact)
    {
        if (! $user->isSuperUser()) {
            return;
        }

        $raw_mail = $this->mail_golden_retriever->getRawMailThatCreatedArtifact($artifact);
        if (! $raw_mail) {
            return;
        }

        $raw_email_button_title = dgettext('tuleap-tracker', 'Display original email');
        $raw_mail               = Codendi_HTMLPurifier::instance()->purify($raw_mail);

        return new ArtifactOriginalEmailButtonPresenter(
            $raw_email_button_title,
            $raw_mail
        );
    }
}
