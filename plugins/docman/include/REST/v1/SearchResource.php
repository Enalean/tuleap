<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1;

use Codendi_HTMLPurifier;
use Docman_FilterFactory;
use Docman_Folder;
use Docman_ItemFactory;
use Docman_PermissionsManager;
use Docman_ReportColumnFactory;
use Docman_SettingsBo;
use Luracast\Restler\RestException;
use PermissionsManager;
use Project;
use ProjectManager;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\ApprovalTable\ApprovalTableStateMapper;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\Docman\REST\v1\Folders\BuildSearchedItemRepresentationsFromSearchReport;
use Tuleap\Docman\REST\v1\Folders\SearchReportBuilder;
use Tuleap\Docman\REST\v1\Folders\SearchRepresentation;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Docman\REST\v1\Metadata\MetadataRepresentationBuilder;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsBuilder;
use Tuleap\Docman\REST\v1\Search\FilePropertiesVisitor;
use Tuleap\Docman\REST\v1\Search\ListOfCustomPropertyRepresentationBuilder;
use Tuleap\Docman\REST\v1\Search\PostSearchRepresentation;
use Tuleap\Docman\REST\v1\Search\SearchColumnCollectionBuilder;
use Tuleap\Docman\REST\v1\Search\SearchRepresentationTypeVisitor;
use Tuleap\Docman\Search\AlwaysThereColumnRetriever;
use Tuleap\Docman\Search\ColumnReportAugmenter;
use Tuleap\Docman\Search\SearchSortPropertyMapper;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\User\Avatar\AvatarHashDao;
use Tuleap\User\Avatar\ComputeAvatarHash;
use Tuleap\User\Avatar\UserAvatarUrlProvider;
use UGroupManager;
use UserHelper;

final class SearchResource extends AuthenticatedResource
{
    public const int MAX_LIMIT = 50;

    /**
     * Search items
     *
     * Search paginated items in a folder.
     *
     * <p><b>Usage examples:</b></p>
     *
     * <ul>
     * <li><p>All folders:</p>
     * <pre>
     * {<br>
     *  &nbsp; "properties": [ {"name": "type", "value": "folder"} ]<br>
     * }
     * </pre>
     * </li>
     * <li><p>All folders matching "lorem\*" sorted by `title` ascending:</p>
     * <pre>
     * {<br>
     *  &nbsp; "global_search": "lorem\*",<br>
     *  &nbsp; "properties": [ {"name": "type", "value": "folder"} ]<br>
     *  &nbsp; "sort": [ {"name": "ttle", "order": "asc"} ]<br>
     * }
     * </pre>
     * </li>
     * <li><p>All items matching "lorem\*":</p>
     * <pre>
     * {<br>
     *  &nbsp; "global_search": "lorem\*",<br>
     * }
     * </pre>
     * </li>
     * <li><p>All drafts, with a given custom text property matching "lorem\*", created after 2022-01-30:</p>
     * <pre>
     * {<br>
     *  &nbsp; "properties": [<br>
     *  &nbsp; &nbsp; {"name": "status", "value": "draft"},<br>
     *  &nbsp; &nbsp; {"name": "field_3", "value": "lorem\*"},<br>
     *  &nbsp; &nbsp; {"name": "create_date", "value_date": { "date": "2022-01-30", operator: ">" } },<br>
     *  &nbsp; ]<br>
     * }
     * </pre>
     * </li>
     * </ul>
     *
     * <p><b>Note:</b> Global search will search in all text properties of document (but does not look inside the document).</p>
     * <hr>
     *
     * <p><b>Allowed properties:</b></p>
     *
     * <table class="tlp-table">
     * <thead>
     * <tr>
     *   <th>Name</th>
     *   <th>Type</th>
     *   <th>Notes</th>
     * </tr>
     * </thead>
     * <tbody>
     * <tr>
     *   <td>`id`</td>
     *   <td>Number</td>
     *   <td>Exact match of the item id.</td>
     * </tr>
     * <tr>
     *   <td>`type`</td>
     *   <td>List</td>
     *   <td>Type of the item. Searchable types: `folder`, `file`, `embedded`, `wiki`, `link`, `empty`.</td>
     * </tr>
     * <tr>
     *   <td>`filename`</td>
     *   <td>Text</td>
     *   <td>Filename of the item.</td>
     * </tr>
     * <tr>
     *   <td>`title`</td>
     *   <td>Text</td>
     *   <td>Title of the item.</td>
     * </tr>
     * <tr>
     *   <td>`description`</td>
     *   <td>Text</td>
     *   <td>Description of the item.</td>
     * </tr>
     * <tr>
     *   <td>`owner`</td>
     *   <td>Text</td>
     *   <td>Owner of the item. Username or id.</td>
     * </tr>
     * <tr>
     *   <td>`create_date`</td>
     *   <td>Date</td>
     *   <td>Date of creation of the document.</td>
     * </tr>
     * <tr>
     *   <td>`update_date`</td>
     *   <td>Date</td>
     *   <td>Last update date of the document.</td>
     * </tr>
     * <tr>
     *   <td>`obsolescence_date`</td>
     *   <td>Date</td>
     *   <td>Obsolescence date of the document. May be disabled in the project.</td>
     * </tr>
     * <tr>
     *   <td>`status`</td>
     *   <td>List</td>
     *   <td>Status of the document. Searchable status: `none`, `draft`, `approved`, `rejected`. May be disabled in the project.</td>
     * </tr>
     * <tr>
     *   <td>`field_XXX`</td>
     *   <td>string | text | date | list</td>
     *   <td>Custom property defined by the project. The list of custom properties defined in the project is accessible via `projects/:id/docman_metadata`.</td>
     * </tr>
     * </tbody>
     * </table>
     *
     * <hr>
     *
     * <p>`properties` is an array of:</p>
     * <ul>
     * <li>`{ "name": "field_2", "value": "lorem" }` for text and string.</li>
     * <li>`{ "name": "field_2", "value": "102" }` for list, where 102 is the internal id of a value.</li>
     * <li>`{ "name": "field_2", "value_date": { date: "2022-01-30", operator: ">" } }` for date.</li>
     * </ul>
     *
     * <hr>
     *
     * <p>`sort` is an array of:</p>
     * <ul>
     * <li>`{ "name": "field_2", "order": "asc" }` accepted value for `order` are `asc` and `desc`</li>
     * <li>`Note:` the following columns cannot be sorted: `location`, `multi list custom properties` </li>
     * </ul>
     *
     * <hr>
     *
     * <p>Search date format:</p>
     * <ul>
     * <li>`operator`: `<` | `>` | `=`</li>
     * <li>`date`: YYYY-mm-dd
     * </ul>
     *
     * <hr>
     *
     * <p>Allowed patterns for text properties (global_search, title, …):</p>
     * <ul>
     * <li> `lorem`   => exactly "lorem"</li>
     * <li> `lorem*`  => starting by "lorem"</li>
     * <li> `*lorem`  => finishing by "lorem"</li>
     * <li> `*lorem*` => containing "lorem"</li>
     * </ul>
     *
     * @url    POST {id}
     * @access hybrid
     *
     * @param int $id Id of the folder
     * @param PostSearchRepresentation $search_representation search representation {@from body}
     *
     * @status 200
     *
     * @return SearchRepresentation[]
     *
     * @throws RestException 400
     * @throws I18NRestException 400
     */
    public function search(int $id, PostSearchRepresentation $search_representation): array
    {
        $this->checkAccess();
        $this->optionsSearch($id);

        $user_manager    = \UserManager::instance();
        $project_manager = ProjectManager::instance();
        $request_builder = new DocmanItemsRequestBuilder($user_manager, $project_manager);
        $item_request    = $request_builder->buildFromItemId($id);
        $project         = $item_request->getProject();
        $folder          = $item_request->getItem();
        $user            = $item_request->getUser();

        $folder->accept($this->getValidator($project, $user, $folder), []);
        assert($folder instanceof Docman_Folder);

        $project_id            = $folder->getGroupId();
        $docman_settings       = new Docman_SettingsBo($project_id);
        $metadata_factory      = new \Docman_MetadataFactory($project_id);
        $search_report_builder = new SearchReportBuilder(
            $metadata_factory,
            new Docman_FilterFactory($project_id),
            new ItemStatusMapper($docman_settings),
            new AlwaysThereColumnRetriever($docman_settings),
            new ColumnReportAugmenter(new Docman_ReportColumnFactory($project_id), new SearchSortPropertyMapper()),
            $user_manager,
            \EventManager::instance(),
        );
        $status_mapper         = new ItemStatusMapper($docman_settings);
        $item_dao              = new \Docman_ItemDao();
        $item_factory          = Docman_ItemFactory::instance($project_id);
        $version_factory       = new \Docman_VersionFactory();
        $permissions_manager   = Docman_PermissionsManager::instance($project_id);

        $event_manager = \EventManager::instance();
        $event_adder   = new DocmanItemsEventAdder($event_manager);

        $html_purifier = Codendi_HTMLPurifier::instance();

        $provide_user_avatar_url = new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash());

        $representation_builder =  new ItemRepresentationBuilder(
            $item_dao,
            $user_manager,
            $item_factory,
            $permissions_manager,
            new \Docman_LockFactory(new \Docman_LockDao(), new \Docman_Log()),
            new ApprovalTableStateMapper(),
            new MetadataRepresentationBuilder(
                new \Docman_MetadataFactory($project->getID()),
                $html_purifier,
                UserHelper::instance()
            ),
            new ApprovalTableRetriever(
                new \Docman_ApprovalTableFactoriesFactory(),
                $version_factory
            ),
            new DocmanItemPermissionsForGroupsBuilder(
                $permissions_manager,
                $project_manager,
                PermissionsManager::instance(),
                new UGroupManager()
            ),
            $html_purifier,
            $provide_user_avatar_url,
        );


        $visitor = new ItemRepresentationVisitor(
            $representation_builder,
            $version_factory,
            new \Docman_LinkVersionFactory(),
            $item_factory,
            $event_manager,
            $event_adder
        );

        $search_representations_builder = new BuildSearchedItemRepresentationsFromSearchReport(
            $status_mapper,
            $user_manager,
            new ItemRepresentationCollectionBuilder(
                $item_factory,
                $permissions_manager,
                $visitor,
                $item_dao
            ),
            $item_factory,
            new SearchRepresentationTypeVisitor(\EventManager::instance()),
            new FilePropertiesVisitor($version_factory, $event_manager),
            new ListOfCustomPropertyRepresentationBuilder(),
            $provide_user_avatar_url,
        );

        $wanted_custom_properties = (new SearchColumnCollectionBuilder())
            ->getCollection($metadata_factory)
            ->extractColumnsOnCustomProperties();

        $report     = $search_report_builder->buildReport($folder, $search_representation, $wanted_custom_properties);
        $collection = $search_representations_builder->build(
            $report,
            $folder,
            $user,
            $search_representation->limit,
            $search_representation->offset,
            $wanted_custom_properties
        );
        Header::sendPaginationHeaders(
            $search_representation->limit,
            $search_representation->offset,
            $collection->total,
            self::MAX_LIMIT
        );

        return $collection->search_representations;
    }

    /**
     * @url OPTIONS {id}
     */
    public function optionsSearch(int $id): void
    {
        Header::allowOptionsPost();
    }

    private function getValidator(
        Project $project,
        \PFUser $current_user,
        \Docman_Item $item,
    ): DocumentBeforeModificationValidatorVisitor {
        return new DocumentBeforeModificationValidatorVisitor(
            Docman_PermissionsManager::instance($project->getGroupId()),
            $current_user,
            $item,
            new DoesItemHasExpectedTypeVisitor(Docman_Folder::class),
        );
    }
}
