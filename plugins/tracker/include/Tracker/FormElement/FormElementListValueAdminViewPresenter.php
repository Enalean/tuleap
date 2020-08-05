<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use Tracker_FormElement_Field_List_Value;
use Tuleap\Tracker\Colorpicker\ColorpickerMountPointPresenter;

/**
 * @psalm-immutable
 */
class FormElementListValueAdminViewPresenter
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var ColorpickerMountPointPresenter | null
     */
    public $decorator;
    /**
     * @var string
     */
    public $label;
    /**
     * @var string
     */
    public $image_hidden_alt;
    /**
     * @var bool
     */
    public $value_can_be_hidden;
    /**
     * @var string
     */
    public $image_hidden_title;
    /**
     * @var bool
     */
    public $is_hidden;
    /**
     * @var string
     */
    public $image_hidden_prefix;
    /**
     * @var string | null
     */
    public $delete_url;
    /**
     * @var bool
     */
    public $value_can_be_deleted;
    /**
     * @var bool
     */
    public $is_none_value;
    /**
     * @var bool
     */
    public $is_custom_value;

    public function __construct(
        Tracker_FormElement_Field_List_Value $value,
        ?ColorpickerMountPointPresenter $decorator,
        bool $value_can_be_hidden,
        bool $value_can_be_deleted,
        ?string $delete_url,
        string $image_hidden_title,
        string $image_hidden_alt,
        string $image_hidden_prefix,
        bool $is_custom_value
    ) {
        $this->id                   = $value->getId();
        $this->label                = self::getListValueLabel($value);
        $this->is_hidden            = (bool) $value->isHidden();
        $this->value_can_be_hidden  = $value_can_be_hidden;
        $this->value_can_be_deleted = $value_can_be_deleted;
        $this->image_hidden_alt     = $image_hidden_alt;
        $this->delete_url           = $delete_url;
        $this->image_hidden_title   = $image_hidden_title;
        $this->image_hidden_prefix  = $image_hidden_prefix;
        $this->decorator            = $decorator;
        $this->is_none_value        = (int) $value->getId() === \Tracker_FormElement_Field_List::NONE_VALUE;
        $this->is_custom_value      = $is_custom_value;
    }

    private static function getListValueLabel(Tracker_FormElement_Field_List_Value $list_value): string
    {
        return $list_value->getLabel();
    }
}
