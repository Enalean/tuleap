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

namespace Tuleap\Cardwall\BackgroundColor;

use Cardwall_Semantic_CardFields;
use PFUser;
use Tracker_Artifact;
use Tuleap\Cardwall\Semantic\BackgroundColorSemanticFieldNotFoundException;
use Tuleap\Tracker\Artifact\Exception\NoChangesetException;
use Tuleap\Tracker\Artifact\Exception\NoChangesetValueException;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDecoratorRetriever;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\NoBindDecoratorException;

class BackgroundColorBuilder
{
    /** @var BindDecoratorRetriever */
    private $decorator_retriever;

    public function __construct(BindDecoratorRetriever $decorator_retriever)
    {
        $this->decorator_retriever = $decorator_retriever;
    }

    public function build(
        Cardwall_Semantic_CardFields $card_fields_semantic,
        Tracker_Artifact $artifact,
        PFUser $current_user
    ) {
        $background_color_name = $this->getBackgroundColor($card_fields_semantic, $artifact, $current_user);
        return new BackgroundColor($background_color_name);
    }

    private function getBackgroundColor(
        Cardwall_Semantic_CardFields $card_fields_semantic,
        Tracker_Artifact $artifact,
        PFUser $current_user
    ) {
        try {
            $background_color_field = $card_fields_semantic->getBackgroundColorField();
        } catch (BackgroundColorSemanticFieldNotFoundException $e) {
            // Ignore, there won't be a background color
            return '';
        }
        if (! $background_color_field->userCanRead($current_user)) {
            return '';
        }

        try {
            $decorator = $this->decorator_retriever->getDecoratorForFirstValue($background_color_field, $artifact);

            return $decorator->tlp_color_name;
        } catch (NoChangesetException $e) {
            return '';
        } catch (NoChangesetValueException $e) {
            return '';
        } catch (NoBindDecoratorException $e) {
            return '';
        }
    }
}
