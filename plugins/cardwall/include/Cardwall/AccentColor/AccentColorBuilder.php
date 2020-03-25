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

namespace Tuleap\Cardwall\AccentColor;

use PFUser;
use Tracker;
use Tracker_Artifact;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Artifact\Exception\NoChangesetException;
use Tuleap\Tracker\Artifact\Exception\NoChangesetValueException;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDecoratorRetriever;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\NoBindDecoratorException;

class AccentColorBuilder
{
    /** @var BindDecoratorRetriever */
    private $decorator_retriever;
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(
        Tracker_FormElementFactory $form_element_factory,
        BindDecoratorRetriever $decorator_retriever
    ) {
        $this->decorator_retriever  = $decorator_retriever;
        $this->form_element_factory = $form_element_factory;
    }

    public function build(
        Tracker_Artifact $artifact,
        PFUser $current_user
    ) {
        $no_color = new AccentColor('', true);

        $selectbox = $this->form_element_factory->getSelectboxFieldByNameForUser(
            $artifact->getTracker()->getId(),
            Tracker::TYPE_FIELD_NAME,
            $current_user
        );
        \assert($selectbox instanceof \Tracker_FormElement_Field_List);
        if (! $selectbox) {
            return $no_color;
        }

        if (! $selectbox->userCanRead($current_user)) {
            return $no_color;
        }

        try {
            $decorator = $this->decorator_retriever->getDecoratorForFirstValue($selectbox, $artifact);
            if ($decorator->isUsingOldPalette()) {
                return new AccentColor($decorator->css(null), true);
            }

            return new AccentColor($decorator->tlp_color_name, false);
        } catch (NoChangesetException $e) {
            return $no_color;
        } catch (NoChangesetValueException $e) {
            return $no_color;
        } catch (NoBindDecoratorException $e) {
            return $no_color;
        }
    }
}
