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
use Codendi_HTMLPurifier;

/**
 * I am responsible of rendering ArtifactLink value in a Custom Column in table renderer
 */
class ValueFormatter
{

    /**
     * @var Codendi_HTMLPurifier
     */
    private $purifier;

    /**
     * @var Tracker_FormElementFactory
     */
    private $factory;

    public function __construct(Tracker_FormElementFactory $factory, Codendi_HTMLPurifier $purifier)
    {
        $this->factory  = $factory;
        $this->purifier = $purifier;
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
                $artlink_as_html = $artifact_link_info->getLink();
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

        $artlink_as_html = '';
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

            $artlink_as_html .= '<a href="'. $artifact_link_info->getUrl() .'">';
            $artlink_as_html .= $this->purifier->purify(str_replace($search, $replace, $format));
            $artlink_as_html .= '</a>';
        } catch (NoFieldToFormatException $exception) {
            $artlink_as_html .= $this->getDefaultFormatWithWarning(
                $artifact_link_info,
                $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'cannot_format')
            );
        } catch (UnsupportedFieldException $exception) {
            $artlink_as_html .= $this->getDefaultFormatWithWarning(
                $artifact_link_info,
                $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'unsupported_field')
            );
        }

        return $artlink_as_html;
    }

    private function getDefaultFormatWithWarning(Tracker_ArtifactLinkInfo $artifact_link_info, $warning)
    {
        $title = $this->purifier->purify($warning);

        return $artifact_link_info->getLink() .
            ' <i class="icon-warning-sign format-warning" title="'. $title .'"></i>';
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
            $search[] = '%'. $field_name;
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
