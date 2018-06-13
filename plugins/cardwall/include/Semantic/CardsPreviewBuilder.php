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

namespace Tuleap\Cardwall\Semantic;

use Cardwall_Semantic_CardFields;

class CardsPreviewBuilder
{
    /**
     * @var SingleCardPreviewDetailsBuilder
     */
    private $single_card_preview_details_builder;

    public function __construct(SingleCardPreviewDetailsBuilder $single_card_preview_details_builder)
    {
        $this->single_card_preview_details_builder = $single_card_preview_details_builder;
    }

    public function build(Cardwall_Semantic_CardFields $semantic_card)
    {
        $cards_preview = [];

        $cards_backgrounds = $this->getPossibleBackgrounds($semantic_card);

        foreach ($cards_backgrounds as $possible_background) {
            $artifact               = $this->single_card_preview_details_builder->build($semantic_card, $possible_background);
            $artifact['background'] = $possible_background['background_color'];
            $cards_preview[]        = $artifact;
        }

        return $cards_preview;
    }

    private function getPossibleBackgrounds(Cardwall_Semantic_CardFields $semantic_card)
    {
        try {
            $background_field = $semantic_card->getBackgroundColorField();
        } catch (BackgroundColorSemanticFieldNotFoundException $exception) {
            return [
                [
                    'background_color' => '',
                    'field_id'         => '',
                    'decorated_value'  => ''
                ]
            ];
        }

        $colors = [];

        $default_values = $background_field->getAllValues();
        $decorators     = $background_field->getBind()->getDecorators();
        foreach ($default_values as $value) {
            $decorator_background_color = $this->getDecorator($decorators, $value);
            if (! isset($colors[$decorator_background_color])) {
                $colors[$decorator_background_color] = [
                    'background_color' => $decorator_background_color,
                    'field_id'         => $background_field->getId(),
                    'decorated_value'  => $this->getDecoratedValue($decorators, $value)
                ];
            }
        }

        return $colors;
    }

    private function getDecorator(array $decorators, \Tracker_FormElement_Field_List_BindValue $value)
    {
        return isset($decorators[$value->getId()]) ? $decorators[$value->getId()]->getCurrentColor() : '';
    }

    /**
     * @param $decorators
     * @param $value
     *
     * @return mixed
     */
    private function getDecoratedValue(array $decorators, \Tracker_FormElement_Field_List_BindValue $value)
    {
        return (isset($decorators[$value->getId()])) ? $decorators[$value->getId()]->decorate($value->getLabel()) : $value->getLabel();
    }
}
