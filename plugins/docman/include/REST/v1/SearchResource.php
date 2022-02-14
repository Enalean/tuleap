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
use Tuleap\Docman\REST\v1\Search\PostSearchRepresentation;
use Tuleap\Docman\REST\v1\Search\SearchRepresentationTypeVisitor;
use Tuleap\Docman\Search\AlwaysThereColumnRetriever;
use Tuleap\Docman\Search\ColumnReportAugmenter;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use UGroupManager;
use UserHelper;

final class SearchResource extends AuthenticatedResource
{
    public const MAX_LIMIT = 50;

    /**
     * Search items
     *
     * Search recursively for items in a folder.
     *
     * <table class="tlp-table">
     * <thead>
     * <tr>
     *   <th>Allowed criteria</th>
     *   <th>Type</th>
     *   <th>Notes</th>
     * </tr>
     * </thead>
     * <tbody>
     * <tr>
     *   <td>`global_search`</td>
     *   <td>Text</th>
     *   <td>Global search will search in all text properties of document (but does not look inside the document).</td>
     * </tr>
     * <tr>
     *   <td>`type`</td>
     *   <td></td>
     *   <td>Type of the item. Allowed types: folder, file, embedded, wiki, link, empty</td>
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
     *   <td></td>
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
     * </tbody>
     * </table>
     *
     * <hr>
     *
     * <p>Search date format:</p>
     * <ul>
     * <li>`operator`: `<` | `>` | `=`</li>
     * <li>`date`: YYYY-mm-dd
     * </ul>
     *
     * <pre>
     * {"create_date": {"operator": "<", "date": "2022-01-30"}}
     * </pre>
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
     * <p>Usage example:</p>
     *
     * <pre>
     * {"global_search": "lorem\*"}
     * </pre>
     *
     * <hr>
     *
     * <p>You can combine multiple search at once. For example to restrict previous example to empty items:</p>
     *
     * <pre>
     * {"global_search": "lorem\*", "type": "empty"}
     * </pre>
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
        $search_report_builder = new SearchReportBuilder(
            new \Docman_MetadataFactory($project_id),
            new Docman_FilterFactory($project_id),
            new AlwaysThereColumnRetriever($docman_settings),
            new ColumnReportAugmenter(new Docman_ReportColumnFactory($project_id)),
            $user_manager,
        );
        $status_mapper         = new ItemStatusMapper($docman_settings);
        $item_dao              = new \Docman_ItemDao();
        $item_factory          = Docman_ItemFactory::instance($project_id);
        $version_factory       = new \Docman_VersionFactory();
        $permissions_manager   = Docman_PermissionsManager::instance($project_id);

        $event_manager = \EventManager::instance();
        $event_adder   = new DocmanItemsEventAdder($event_manager);

        $html_purifier = Codendi_HTMLPurifier::instance();

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
            $html_purifier
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
            new SearchRepresentationTypeVisitor(),
            new FilePropertiesVisitor($version_factory),
        );

        $report     = $search_report_builder->buildReport($folder, $search_representation);
        $collection = $search_representations_builder->build(
            $report,
            $folder,
            $user,
            $search_representation->limit,
            $search_representation->offset
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
            new DoesItemHasExpectedTypeVisitor(Docman_Folder::class)
        );
    }
}
