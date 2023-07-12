<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

use ForgeConfig;
use Luracast\Restler\RestException;
use PFUser;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_File;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElementFactory;
use Tracker_REST_FormElementRepresentation;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\REST\v1\TrackerFieldRepresentations\TrackerFieldPatchRepresentation;
use Tuleap\Tracker\FormElement\Field\File\Upload\EmptyFileToUploadFinisher;
use Tuleap\Tracker\FormElement\Field\File\Upload\FileOngoingUploadDao;
use Tuleap\Tracker\FormElement\Field\File\Upload\FileToUploadCreator;
use Tuleap\Tracker\FormElement\Field\File\Upload\UploadPathAllocator;
use UserManager;

class TrackerFieldsResource extends AuthenticatedResource
{
    public const ROUTE = 'tracker_fields';

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
     * @return Tracker_REST_FormElementRepresentation
     *
     * @access protected
     *
     * @throws RestException 400
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function patch(int $id, TrackerFieldPatchRepresentation $patch)
    {
        $this->checkAccess();
        $this->optionsId($id);

        $user_manager = UserManager::instance();
        $user         = $user_manager->getCurrentUser();

        $field = $this->getField($id, $user);

        if (! $field->getTracker()->userIsAdmin($user)) {
            throw new RestException(403, "User is not tracker administrator.");
        }

        $form_element_factory = Tracker_FormElementFactory::instance();
        if (! $form_element_factory->isFieldASimpleListField($field)) {
            throw new RestException(400, "Field is not a simple list.");
        }

        if (! is_a($field->getBind(), Tracker_FormElement_Field_List_Bind_Static::class)) {
            throw new RestException(400, "Field values can be only add with static values.");
        }

        $request = ['add' => null];
        if ($patch->new_values !== null) {
            $request['add'] = implode("\n", $patch->new_values);
        }
        $field->getBind()->process($request, true);

        return Tracker_REST_FormElementRepresentation::build(
            $field,
            $form_element_factory->getType($field),
            [],
            null
        );
    }

    /**
     * @url OPTIONS {id}/files
     *
     * @param int $id Id of the tracker field
     */
    public function optionsFiles(int $id): void
    {
        Header::allowOptionsPost();
    }

    /**
     * Create file
     *
     * Create a file in a File field so that it can be attached to an artifact later.
     *
     * Only File field allows this route.
     *
     * <br>
     *
     * After having uploaded the files, you need to update (or create) an artifact with the file ID
     * you got from this endpoint.
     *
     * @url POST {id}/files
     *
     * @access protected
     *
     * @param int                    $id                        The id of the field
     *
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function postFiles(int $id, FilePOSTRepresentation $file_post_representation): CreatedFileRepresentation
    {
        $this->checkAccess();
        $this->optionsFiles($id);

        $user_manager = UserManager::instance();
        $user         = $user_manager->getCurrentUser();

        $field = $this->getFileFieldUserCanUpdate($id, $user);

        $file_ongoing_upload_dao = new FileOngoingUploadDao();

        $upload_path_allocator = new UploadPathAllocator($file_ongoing_upload_dao, Tracker_FormElementFactory::instance());
        $file_creator          = new FileCreator(
            new FileToUploadCreator(
                $file_ongoing_upload_dao,
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                (int) ForgeConfig::get('sys_max_size_upload')
            ),
            new EmptyFileToUploadFinisher($upload_path_allocator)
        );

        return $file_creator->create($field, $user, $file_post_representation, new \DateTimeImmutable());
    }

    private function getField(int $id, PFUser $user): Tracker_FormElement_Field
    {
        $form_element_factory = Tracker_FormElementFactory::instance();
        $field                = $form_element_factory->getFieldById($id);

        if (! $field) {
            throw new RestException(404, "Field not found.");
        }

        $tracker = $field->getTracker();
        if (! $tracker) {
            throw new RestException(404);
        }
        if (! $tracker->userCanView($user)) {
            throw new RestException(404);
        }

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $tracker->getProject()
        );

        if (! $field->isUsed()) {
            throw new RestException(400, "Field is not used in tracker.");
        }

        return $field;
    }

    /**
     * @throws RestException
     */
    private function getFileFieldUserCanUpdate(int $id, PFUser $user): Tracker_FormElement_Field_File
    {
        $field = $this->getField($id, $user);
        \assert($field instanceof Tracker_FormElement_Field_File);

        $form_element_factory = Tracker_FormElementFactory::instance();
        if (! $form_element_factory->isFieldAFileField($field)) {
            throw new RestException(400, "Field must be of type File.");
        }

        if (! $field->userCanSubmit($user) && ! $field->userCanUpdate($user)) {
            throw new RestException(403);
        }

        return $field;
    }
}
