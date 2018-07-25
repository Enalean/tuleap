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

namespace Tuleap\Tracker\REST\v1;

use Luracast\Restler\RestException;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElementFactory;
use Tracker_REST_FieldRepresentation;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\UserManager;
use Tuleap\REST\v1\TrackerFieldRepresentations\TrackerFieldPatchRepresentation;

class TrackerFieldsResource extends AuthenticatedResource
{

    /**
     * @url OPTIONS {id}
     *
     * @param string $id Id of the tracker field
     */
    public function optionsId($id)
    {
        Header::allowOptionsPatch();
    }


    /**
     * Partial update of a tracker field
     *
     * REST route to update a tracker field
     * <br/>
     *
     * <pre>
     * /!\ REST route under construction and subject to changes /!\
     * </pre>
     * <br/>
     *
     * This partial update allows user to add new values to a simple list field (selectbox or radiobutton).
     * <br/>
     * <br/>
     * To add a value:
     * <pre>
     * {<br>
     * &nbsp;"new_values": ["new01", "new02"]<br/>
     * }
     *
     * @url PATCH {id}
     *
     * @access protected
     *
     * @param int                             $id    Id of the field
     * @param TrackerFieldPatchRepresentation $patch New values for the field {@from body} {@type Tuleap\REST\v1\TrackerFieldRepresentations\TrackerFieldPatchRepresentation}
     *
     * @return Tracker_REST_FieldRepresentation
     *
     * @access protected
     *
     * @throws 400
     * @throws 401
     * @throws 404
     */
    protected function patch($id, $patch)
    {
        $this->checkAccess();
        $this->optionsId($id);

        $rest_user_manager = UserManager::build();
        $user              = $rest_user_manager->getCurrentUser();

        $form_element_factory = Tracker_FormElementFactory::instance();
        $field                = $form_element_factory->getFieldById($id);
        if (! $field) {
            throw new RestException(404, "Field not found.");
        }

        $tracker = $field->getTracker();
        if (! $tracker->userIsAdmin($user)) {
            throw new RestException(401, "User is not tracker administrator.");
        }

        if (! $field->isUsed()) {
            throw new RestException(400, "Field is not used in tracker.");
        }

        if (! $form_element_factory->isFieldASimpleListField($field)) {
            throw new RestException(400, "Field is not a simple list.");
        }

        if (! is_a($field->getBind(), Tracker_FormElement_Field_List_Bind_Static::class)) {
            throw new RestException(400, "Field values can be only add with static values.");
        }

        $request['add'] = implode("\n", $patch->new_values);
        $field->getBind()->process($request, true);

        $field_representation = new Tracker_REST_FieldRepresentation();
        $field_representation->build(
            $field,
            $form_element_factory->getType($field),
            []
        );

        return $field_representation;
    }
}
