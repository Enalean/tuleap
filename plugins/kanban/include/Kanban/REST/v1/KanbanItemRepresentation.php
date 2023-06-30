<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban\REST\v1;

use Tuleap\Cardwall\BackgroundColor\BackgroundColor;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\Artifact\Artifact;

/**
 * @psalm-immutable
 */
final class KanbanItemRepresentation
{
    /**
     * @var Int
     */
    public $id;

    /**
     * @var String
     */
    public $item_name;

    /**
     * @var String
     */
    public $label;

    /**
     * @var String
     */
    public $color;

    /*
     * @var array
     */
    public $card_fields;

    /*
     * @var array
     */
    public $timeinfo;

    /**
     * @var mixed string | int
     */
    public $in_column;

    /**
     * @var string
     */
    public $background_color_name;

    /**
     * @param string|int $in_column
     */
    private function __construct(
        int $id,
        string $item_name,
        string $label,
        string $color,
        array $card_fields,
        array $timeinfo,
        $in_column,
        string $background_color_name,
    ) {
        $this->id                    = $id;
        $this->item_name             = $item_name;
        $this->label                 = $label;
        $this->color                 = $color;
        $this->card_fields           = $card_fields;
        $this->timeinfo              = $timeinfo;
        $this->in_column             = $in_column;
        $this->background_color_name = $background_color_name;
    }

    /**
     * @param string|int $in_column
     */
    public static function build(
        Artifact $artifact,
        array $timeinfo,
        $in_column,
        array $card_fields,
        BackgroundColor $background_color,
    ): self {
        return new self(
            JsonCast::toInt($artifact->getId()),
            $artifact->getTracker()->getItemName(),
            $artifact->getTitle() ?? '',
            $artifact->getTracker()->getColor()->getName(),
            $card_fields,
            $timeinfo,
            $in_column,
            $background_color->getBackgroundColorName(),
        );
    }
}
