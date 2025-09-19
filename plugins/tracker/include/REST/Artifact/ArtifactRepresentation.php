<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact;

use PFUser;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\Changeset\ChangesetRepresentation;
use Tuleap\Tracker\REST\TrackerRepresentation;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;
use Tuleap\User\ProvideAnonymousUser;
use Tuleap\User\REST\MinimalUserRepresentation;
use Tuleap\User\RetrieveUserById;

/**
 * @psalm-immutable
 */
class ArtifactRepresentation
{
    public const ROUTE = 'artifacts';

    /**
     * @var int ID of the artifact {@type int} {@required true}
     */
    public $id;

    /**
     * @var string URI of the artifact {@type string} {@required true}
     */
    public $uri;

    /**
     * @var string The xref of the artifact (ex: bug #123) {@type string}
     */
    public $xref;

    /**
     * @var TrackerRepresentation  {@type \Tuleap\Tracker\REST\TrackerRepresentation} {@required true}
     */
    public $tracker;

    /**
     * @var \Tuleap\Project\REST\ProjectReference ID of the project the artifact belongs to {@type \Tuleap\Project\REST\ProjectReference} {@required true}
     */
    public $project;

    /**
     * @var int ID of the user who created the first version of the artifact {@type int} {@required true}
     */
    public $submitted_by;

    /**
     * @var MinimalUserRepresentation the minimal user representation who created the first version of the artifact {@type \Tuleap\User\REST\MinimalUserRepresentation} {@required true}
     */
    public $submitted_by_user;

    /**
     * @var string Date, when the first version of the artifact was created {@type string} {@required true}
     */
    public $submitted_on;

    /**
     * @var string URL to access the artifact with the Web UI {@type string} {@required true}
     */
    public $html_url;

    /**
     * @var string URI to access the artifact history {@type string} {@required true}
     */
    public $changesets_uri;

    /**
     * @var array | null Field values {@type array}
     */
    public $values = null;

    /**
     * @var array | null values by field {@type array}
     */
    public $values_by_field = null;

    /**
     * @var int ID of the user who have done the last modification {@type int} {@required true}
     */
    public $last_update_by;

    /**
     * @var MinimalUserRepresentation the minimal user representation who have done the last modification {@type \Tuleap\User\REST\MinimalUserRepresentation} {@required true}
     */
    public $last_update_by_user;

    /**
     * @var string Date, when the last modification occurs {@type string} {@required true}
     */
    public $last_modified_date;

    /**
     * @var string | null the semantic status value {@type string}
     */
    public $status;

    /**
     * @var StatusValueRepresentation | null the data linked to status representation
     */
    public $full_status;

    /**
     * @var bool whether the current status semantic value is considered open or closed {@type bool}
     */
    public bool $is_open;

    /**
     * @var string | null the semantic title value {@type string}
     */
    public $title;

    /**
     * @var array the semantic assignee value {@type array}
     */
    public $assignees;

    private function __construct(
        int $id,
        string $uri,
        string $xref,
        TrackerRepresentation $tracker,
        ProjectReference $project,
        int $submitted_by,
        MinimalUserRepresentation $submitted_by_user,
        string $submitted_on,
        string $html_url,
        string $changesets_uri,
        ?array $values,
        ?array $values_by_field,
        string $last_modified_date,
        int $last_update_by,
        MinimalUserRepresentation $last_update_by_user,
        ?string $status,
        bool $is_open,
        ?string $title,
        array $assignees,
        ?StatusValueRepresentation $status_value_representation,
    ) {
        $this->id                  = $id;
        $this->uri                 = $uri;
        $this->xref                = $xref;
        $this->tracker             = $tracker;
        $this->project             = $project;
        $this->submitted_by        = $submitted_by;
        $this->submitted_by_user   = $submitted_by_user;
        $this->submitted_on        = $submitted_on;
        $this->html_url            = $html_url;
        $this->changesets_uri      = $changesets_uri;
        $this->values              = $values;
        $this->values_by_field     = $values_by_field;
        $this->last_update_by      = $last_update_by;
        $this->last_update_by_user = $last_update_by_user;
        $this->last_modified_date  = $last_modified_date;
        $this->status              = $status;
        $this->is_open             = $is_open;
        $this->title               = $title;
        $this->assignees           = $assignees;
        $this->full_status         = $status_value_representation;
    }

    public static function build(
        PFUser $current_user,
        Artifact $artifact,
        ?array $values,
        ?array $values_by_field,
        TrackerRepresentation $tracker_representation,
        StatusValueRepresentation $status_value_representation,
        ProvideUserAvatarUrl $provide_user_avatar_url,
        RetrieveUserById $retrieve_user_by_id,
        ProvideAnonymousUser $provide_anonymous_user,
    ): self {
        $artifact_id = $artifact->getId();

        $assignees = [];
        foreach ($artifact->getAssignedTo($current_user) as $assignee) {
            $user_representation = MinimalUserRepresentation::build($assignee, $provide_user_avatar_url);
            $assignees[]         = $user_representation;
        }

        $status_representation = $status_value_representation;
        if ($status_value_representation->value === '') {
            $status_representation = null;
        }

        $submitted_by_user   = $artifact->getSubmittedByUser();
        $last_update_by      = $artifact->getLastModifiedBy();
        $last_update_by_user = null;
        if ($last_update_by !== null) {
            $last_update_by_user = $retrieve_user_by_id->getUserById($last_update_by);
        }
        if ($last_update_by_user === null) {
            $last_update_by_user = $provide_anonymous_user->getUserAnonymous();
        }

        return new self(
            JsonCast::toInt($artifact_id),
            self::ROUTE . '/' . $artifact_id,
            $artifact->getXRef(),
            $tracker_representation,
            new ProjectReference($artifact->getTracker()->getProject()),
            JsonCast::toInt($submitted_by_user->getId()),
            MinimalUserRepresentation::build($submitted_by_user, $provide_user_avatar_url),
            JsonCast::toDate($artifact->getSubmittedOn()),
            $artifact->getUri(),
            self::ROUTE . '/' . $artifact_id . '/' . ChangesetRepresentation::ROUTE,
            $values,
            $values_by_field,
            JsonCast::toDate($artifact->getLastUpdateDate()),
            JsonCast::toInt($last_update_by_user->getId()),
            MinimalUserRepresentation::build($last_update_by_user, $provide_user_avatar_url),
            $status_value_representation->value,
            $artifact->isOpen(),
            $artifact->getTitle(),
            $assignees,
            $status_representation,
        );
    }
}
