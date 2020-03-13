<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature;

class NatureValidator
{

    public const SHORTNAME_PATTERN = '[a-zA-Z][a-zA-Z_]*';

    /** @var NatureDao */
    private $dao;

    public function __construct(NatureDao $dao)
    {
        $this->dao = $dao;
    }

    /** @throws InvalidNatureParameterException */
    public function checkShortname($shortname)
    {
        if (! $shortname) {
            throw new InvalidNatureParameterException(
                $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'missing_shortname')
            );
        }
        if (! preg_match('/^' . self::SHORTNAME_PATTERN . '$/', $shortname)) {
            throw new InvalidNatureParameterException(
                $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'shortname_help')
            );
        }
    }

    /** @throws InvalidNatureParameterException */
    public function checkForwardLabel($forward_label)
    {
        if (! $forward_label) {
            throw new InvalidNatureParameterException(
                $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'missing_forward_label')
            );
        }
    }

    /** @throws InvalidNatureParameterException */
    public function checkReverseLabel($reverse_label)
    {
        if (! $reverse_label) {
            throw new InvalidNatureParameterException(
                $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'missing_reverse_label')
            );
        }
    }

    public function checkIsNotOrHasNotBeenUsed($shortname)
    {
        if ($this->dao->isOrHasBeenUsed($shortname)) {
            throw new UnableToDeleteNatureException(
                $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'cannot_delete')
            );
        }
    }
}
