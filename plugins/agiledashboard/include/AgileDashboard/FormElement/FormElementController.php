<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement;

use Codendi_Request;
use Tracker_ArtifactFactory;

class FormElementController
{
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var BurnupCacheGenerator
     */
    private $burnup_cache_generator;

    public function __construct(Tracker_ArtifactFactory $artifact_factory, BurnupCacheGenerator $burnup_cache_generator)
    {
        $this->artifact_factory       = $artifact_factory;
        $this->burnup_cache_generator = $burnup_cache_generator;
    }

    public function forceBurnupCacheGeneration(Codendi_Request $request)
    {
        $artifact_id = $request->get('aid');
        $artifact    = $this->artifact_factory->getArtifactById($artifact_id);

        if ($artifact === null) {
            $GLOBALS['Response']->redirect('/');
        }

        $current_user = $request->getCurrentUser();
        if (! $artifact->getTracker()->userIsAdmin($current_user)) {
            $GLOBALS['Response']->redirect($artifact->getUri());
        }

        $this->burnup_cache_generator->forceBurnupCacheGeneration($artifact);
        $GLOBALS['Response']->redirect($artifact->getUri());
    }
}
