<?php
/*
 * Copyright (c) Enalean SAS, 2016. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, If not, see <http://www.gnu.org/licenses/>
 */

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\CustomColumn;

use Tracker_FormElementFactory;
use PFUser;
use Tracker_ArtifactLinkInfo;
use Tracker_Artifact_Changeset;

/**
 * I am responsible of rendering ArtifactLink value in a Custom Column in table renderer
 */
class ValueFormatter
{
    /**
     * @var OutputStrategy
     */
    private $output;

    /**
     * @var Tracker_FormElementFactory
     */
    private $factory;

    public function __construct(Tracker_FormElementFactory $factory, OutputStrategy $output)
    {
        $this->factory = $factory;
        $this->output  = $output;
    }

    public function fetchFormattedValue(PFUser $user, array $values, $nature, $format)
    {
        $arr = array();
        preg_match_all('/%(?P<names>[a-z_]+)/i', $format, $matches);
        foreach ($values as $artifact_link_info) {
            if ($artifact_link_info->getNature() != $nature) {
                continue;
            }

            $artlink_as_html = $this->getFormattedValue(
                $format,
                $artifact_link_info,
                $matches['names'],
                $user
            );
            if (! $artlink_as_html) {
                $artlink_as_html = $this->output->fetchDefault($artifact_link_info);
            }
            $arr[] = $artlink_as_html;
        }
        $html = implode(', ', $arr);
        return $html;
    }

    private function getFormattedValue(
        $format,
        Tracker_ArtifactLinkInfo $artifact_link_info,
        $matching_field_names,
        PFUser $user
    ) {
        if (! trim($format)) {
            return '';
        }

        $changeset = $artifact_link_info->getArtifact()->getLastChangeset();
        if (! $changeset) {
            return '';
        }

        $search  = array();
        $replace = array();
        try {
            $this->fillSearchAndReplace(
                $search,
                $replace,
                $artifact_link_info,
                $changeset,
                $matching_field_names,
                $user
            );

            return $this->output->fetchFormatted($artifact_link_info, str_replace($search, $replace, $format));
        } catch (NoFieldToFormatException $exception) {
            return $this->output->fetchWhenNoFieldToFormat($artifact_link_info);
        } catch (UnsupportedFieldException $exception) {
            return $this->output->fetchWhenUnsupportedField($artifact_link_info);
        }

        return '';
    }

    private function fillSearchAndReplace(
        array &$search,
        array &$replace,
        Tracker_ArtifactLinkInfo $artifact_link_info,
        Tracker_Artifact_Changeset $changeset,
        array $matching_field_names,
        PFUser $user
    ) {
        foreach ($matching_field_names as $field_name) {
            $search[] = '%' . $field_name;
            $field = $this->factory->getUsedFieldByNameForUser($artifact_link_info->getTrackerId(), $field_name, $user);
            if (! $field) {
                throw new NoFieldToFormatException();
            } else {
                $visitor   = new ReplaceValueVisitor($field, $changeset);
                $replace[] = $visitor->getReplacement();
            }
        }
    }
}
