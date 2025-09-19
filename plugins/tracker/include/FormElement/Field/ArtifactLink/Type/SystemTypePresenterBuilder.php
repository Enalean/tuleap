<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Type;

use Override;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;

final class SystemTypePresenterBuilder implements RetrieveSystemTypePresenter
{
    public function __construct(private \EventManager $event_manager)
    {
    }

    #[Override]
    public function getSystemTypeFromShortname(?string $shortname): ?TypePresenter
    {
        if ($shortname === null) {
            return new TypePresenter('', '', '', true);
        }

        if ($shortname === ArtifactLinkField::DEFAULT_LINK_TYPE) {
            return new DefaultLinkTypePresenter();
        }

        if ($shortname === ArtifactLinkField::TYPE_IS_CHILD) {
            return new TypeIsChildPresenter();
        }

        return $this->getTypePresenterDefinedInPlugins($shortname);
    }

    private function getTypePresenterDefinedInPlugins(string $shortname): ?TypePresenter
    {
        $presenter = null;

        $params = [
            'presenter' => &$presenter,
            'shortname' => $shortname,
        ];

        $this->event_manager->processEvent(
            TypePresenterFactory::EVENT_GET_TYPE_PRESENTER,
            $params
        );

        return $presenter;
    }
}
