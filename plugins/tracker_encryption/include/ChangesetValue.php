<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\TrackerEncryption;

use Codendi_Diff;
use Codendi_HTMLPurifier;
use Codendi_HtmlUnifiedDiffFormatter;
use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue;
use Tracker_Artifact_ChangesetValueVisitor;
use Tracker_FormElementFactory;
use Tuleap;
use Tuleap\Tracker\REST\Artifact\EncryptedRepresentation;

class ChangesetValue extends Tracker_Artifact_ChangesetValue
{
    /**
     * @var string
     */
    private $value;

    public function __construct($id, Tracker_Artifact_Changeset $changeset, $field, $has_changed, $value)
    {
        parent::__construct($id, $changeset, $field, $has_changed);

        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string|false
     */
    public function diff($changeset_value, $format = 'html', ?PFUser $user = null, $ignore_perms = false)
    {
        $previous = explode(PHP_EOL, $changeset_value->getValue());
        $next     = explode(PHP_EOL, $this->getValue());

        return $this->fetchDiff($previous, $next);
    }

    /**
     * @return string
     */
    private function fetchDiff($previous, $next)
    {
        $string         = '';
        $formatted_diff = $this->getFormatedDiff($previous, $next);
        if ($formatted_diff) {
            $string = $this->fetchDiffInFollowUp($formatted_diff);
        }

        return $string;
    }

    private function getFormatedDiff($previous, $next)
    {
        $callback = array(Codendi_HTMLPurifier::instance(), 'purify');
        $formater = new Codendi_HtmlUnifiedDiffFormatter();
        $diff     = new Codendi_Diff(
            array_map($callback, $previous, array_fill(0, count($previous), CODENDI_PURIFIER_CONVERT_HTML)),
            array_map($callback, $next, array_fill(0, count($next), CODENDI_PURIFIER_CONVERT_HTML))
        );

        return $formater->format($diff);
    }

    protected function fetchDiffInFollowUp($formated_diff)
    {
        return '<div class="diff">' . $formated_diff . '</div>';
    }


    public function nodiff($format = 'html')
    {
    }

    /**
     *
     * @return Tuleap\Tracker\REST\Artifact\ArtifactFieldValueRepresentation
     */
    public function getRESTValue(PFUser $user)
    {
        return $this->getFullRESTValue($user);
    }

    /**
     *
     * @return Tuleap\Tracker\REST\Artifact\ArtifactFieldValueRepresentation
     */
    public function getFullRESTValue(PFUser $user)
    {
        return $this->getFullRESTRepresentation($this->getValue());
    }

    protected function getFullRESTRepresentation($value)
    {
        $artifact_field_value_full_representation = new EncryptedRepresentation();
        $artifact_field_value_full_representation->build(
            $this->field->getId(),
            Tracker_FormElementFactory::instance()->getType($this->field),
            $this->field->getLabel(),
            $value
        );

        return $artifact_field_value_full_representation;
    }

    /**
     * @return mixed
     */
    public function accept(Tracker_Artifact_ChangesetValueVisitor $visitor)
    {
        return $visitor->visitExternalField($this);
    }
}
