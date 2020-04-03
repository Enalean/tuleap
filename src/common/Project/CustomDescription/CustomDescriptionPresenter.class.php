<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

use Tuleap\Project\Admin\DescriptionFields\DescriptionFieldLabelBuilder;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Project_CustomDescription_CustomDescriptionPresenter
{

    /** @var Project_CustomDescription_CustomDescription */
    private $custom_description;

    /** @var string */
    private $value;

    /** @var string */
    private $form_prefix;

    public function __construct(Project_CustomDescription_CustomDescription $custom_description, $value, $form_prefix)
    {
        $this->value              = $value;
        $this->form_prefix        = $form_prefix;
        $this->custom_description = $custom_description;
    }

    public function getName()
    {
        return DescriptionFieldLabelBuilder::getFieldTranslatedName($this->custom_description->getName());
    }

    public function getDescription()
    {
        $hp = Codendi_HTMLPurifier::instance();
        return $hp->purify(DescriptionFieldLabelBuilder::getFieldTranslatedDescription($this->custom_description->getDescription()), CODENDI_PURIFIER_LIGHT);
    }

    public function isRequired()
    {
        return $this->custom_description->isRequired();
    }

    public function isText()
    {
        return $this->custom_description->isText();
    }

    public function getFormName()
    {
        return $this->form_prefix . $this->custom_description->getId();
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }
}
