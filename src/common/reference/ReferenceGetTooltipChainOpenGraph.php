<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Reference;

use ReferenceManager;

class ReferenceGetTooltipChainOpenGraph extends ReferenceGetTooltipChain
{
    /**
     * @var \Codendi_HTMLPurifier
     */
    private $html_purifier;
    /**
     * @var ReferenceOpenGraphDispatcher
     */
    private $open_graph_dispatcher;

    public function __construct(
        \Codendi_HTMLPurifier $html_purifier,
        ReferenceOpenGraphDispatcher $open_graph_dispatcher
    ) {
        $this->html_purifier         = $html_purifier;
        $this->open_graph_dispatcher = $open_graph_dispatcher;
    }

    public function process(
        \Reference $reference,
        \Project $project,
        \PFUser $user,
        string $keyword,
        string $value
    ): void {
        if ($reference->getNature() === ReferenceManager::REFERENCE_NATURE_OTHER) {
            $reference_open_graph = new ReferenceOpenGraph(
                $this->html_purifier,
                $reference,
                $this->open_graph_dispatcher
            );
            echo $reference_open_graph->getContent();
        } else {
            parent::process($reference, $project, $user, $keyword, $value);
        }
    }
}
