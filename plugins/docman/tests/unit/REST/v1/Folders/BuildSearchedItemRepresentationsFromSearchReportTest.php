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

namespace Tuleap\Docman\REST\v1\Folders;

use ArrayIterator;
use Codendi_HTMLPurifier;
use Docman_File;
use Docman_Folder;
use Docman_ItemDao;
use Docman_ItemFactory;
use Docman_Metadata;
use Docman_PermissionsManager;
use Docman_Report;
use Docman_SettingsBo;
use Docman_Version;
use Override;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Docman\Item\Icon\ItemIconPresenter;
use Tuleap\Docman\Item\Icon\ItemIconPresenterBuilder;
use Tuleap\Docman\Item\Icon\ItemIconPresenterEvent;
use Tuleap\Docman\REST\v1\Files\FilePropertiesRepresentation;
use Tuleap\Docman\REST\v1\ItemRepresentation;
use Tuleap\Docman\REST\v1\ItemRepresentationCollectionBuilder;
use Tuleap\Docman\REST\v1\ItemRepresentationVisitor;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Docman\REST\v1\Search\ListOfCustomPropertyRepresentationBuilder;
use Tuleap\Docman\REST\v1\Search\SearchColumn;
use Tuleap\Docman\REST\v1\Search\SearchColumnCollection;
use Tuleap\Docman\REST\v1\Search\SearchRepresentationTypeVisitor;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\User\REST\MinimalUserRepresentation;
use UserManager;

#[DisableReturnValueGenerationForTestDoubles]
final class BuildSearchedItemRepresentationsFromSearchReportTest extends TestCase
{
    use GlobalLanguageMock;

    private UserManager&MockObject $user_manager;
    private Docman_ItemFactory&MockObject $item_factory;
    private BuildSearchedItemRepresentationsFromSearchReport $representation_builder;
    private ItemStatusMapper $status_mapper;
    private ItemRepresentationVisitor&MockObject $item_representation_visitor;
    private \Docman_VersionFactory&\PHPUnit\Framework\MockObject\Stub $version_factory;

    #[Override]
    protected function setUp(): void
    {
        $docman_settings = $this->createMock(Docman_SettingsBo::class);
        $docman_settings->method('getMetadataUsage')->with('status')->willReturn('1');
        $item_dao               = $this->createMock(Docman_ItemDao::class);
        $this->status_mapper    = new ItemStatusMapper($docman_settings);
        $this->user_manager     = $this->createMock(UserManager::class);
        $permissions_manager    = $this->createMock(Docman_PermissionsManager::class);
        $event_manager          =  EventDispatcherStub::withCallback(
            static function (object $event): object {
                if ($event instanceof ItemIconPresenterEvent) {
                    $event->setPresenter(new ItemIconPresenter('fa-solid fa-rocket', 'fiesta-red'));
                }

                return $event;
            }
        );
        $this->version_factory  = $this->createStub(\Docman_VersionFactory::class);
        $icon_presenter_builder = new ItemIconPresenterBuilder($event_manager, $this->version_factory);

        $this->item_factory                = $this->createMock(Docman_ItemFactory::class);
        $this->item_representation_visitor = $this->createMock(ItemRepresentationVisitor::class);
        $this->representation_builder      = new BuildSearchedItemRepresentationsFromSearchReport(
            $this->status_mapper,
            $this->user_manager,
            new ItemRepresentationCollectionBuilder(
                $this->item_factory,
                $permissions_manager,
                $this->item_representation_visitor,
                $item_dao,
            ),
            $this->item_factory,
            new SearchRepresentationTypeVisitor(EventDispatcherStub::withIdentityCallback()),
            new ListOfCustomPropertyRepresentationBuilder(),
            ProvideUserAvatarUrlStub::build(),
            $this->item_representation_visitor,
            $icon_presenter_builder
        );

        UserManager::setInstance($this->user_manager);
    }

    #[Override]
    protected function tearDown(): void
    {
        UserManager::clearInstance();
    }

    public function testItBuildsItemRepresentations(): void
    {
        $report = new Docman_Report();
        $folder = new Docman_Folder([
            'item_id'  => 66,
            'group_id' => 101,
        ]);

        $item_one_array = [
            'item_id'     => 1,
            'title'       => 'folder',
            'description' => '',
            'update_date' => '123456789',
            'status'      => PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
            'user_id'     => 101,
            'parent_id'   => 0,
        ];
        $item_one       = new Docman_Folder($item_one_array);
        $item_two_array = [
            'item_id'     => 2,
            'title'       => 'file',
            'description' => '',
            'update_date' => '987654321',
            'status'      => PLUGIN_DOCMAN_ITEM_STATUS_REJECTED,
            'user_id'     => 101,
            'parent_id'   => 0,
        ];
        $item_two       = new Docman_File($item_two_array);

        $metadata = new Docman_Metadata();
        $metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);
        $metadata->setLabel('field_23');
        $metadata->setValue('Lorem ipsum');
        $item_two->addMetadata($metadata);

        $this->item_representation_visitor->expects($this->once())->method('visitFolder')->willReturn(null);
        $user = UserTestBuilder::buildWithDefaults();
        $this->user_manager->method('getCurrentUser')->willReturn($user);
        $this->item_representation_visitor->expects($this->once())->method('visitFile')
            ->willReturn(ItemRepresentation::build(
                $item_two,
                Codendi_HTMLPurifier::instance(),
                MinimalUserRepresentation::build($user, ProvideUserAvatarUrlStub::build()),
                true,
                true,
                ItemRepresentation::TYPE_FILE,
                new ItemIconPresenter('file-icon', 'gold'),
                false,
                true,
                [],
                false,
                false,
                'move_uri',
                null,
                null,
                null,
                FilePropertiesRepresentation::build(
                    new Docman_Version([
                        'number'   => 12,
                        'filetype' => 'text/html',
                        'filesize' => 12345,
                    ]),
                    'download_uri',
                    'open_uri',
                ),
                null,
                null,
                null,
                null,
                null,
            ));

        $current_user = UserTestBuilder::aUser()->build();
        $this->user_manager->method('getCurrentUser')->willReturn($current_user);
        $this->user_manager->method('getUserById')->willReturn(UserTestBuilder::aUser()->withUserName('John')->withRealName('jsmith')->withId(101)->build());

        $this->item_factory
            ->method('getItemList')
            ->with(
                66,
                0,
                [
                    'api_limit'       => 50,
                    'api_offset'      => 0,
                    'filter'          => $report,
                    'user'            => $current_user,
                    'ignore_obsolete' => true,
                ]
            )
            ->willReturn(new ArrayIterator([$item_one, $item_two]));

        $this->version_factory->method('getCurrentVersionForItem')->willReturn(new Docman_Version(['number' => 12]));

        $wanted_custom_properties = new SearchColumnCollection();
        $wanted_custom_properties->add(SearchColumn::buildForSingleValueCustomProperty('field_23', 'Comments'));

        $collection = $this->representation_builder->build(
            $report,
            $folder,
            $current_user,
            50,
            0,
            $wanted_custom_properties
        );

        $this->assertItemEqualsRepresentation($item_one_array, $collection->search_representations[0]);
        $this->assertItemEqualsRepresentation($item_two_array, $collection->search_representations[1]);
        self::assertEquals('folder', $collection->search_representations[0]->type);

        self::assertEquals('file', $collection->search_representations[1]->type);
        self::assertEquals('text/html', $collection->search_representations[1]->file_properties->file_type);
        self::assertEquals('Lorem ipsum', $collection->search_representations[1]->custom_properties['field_23']->value);

        self::assertCount(2, $collection->search_representations);
    }

    private function assertItemEqualsRepresentation(array $item, SearchRepresentation $representation): void
    {
        self::assertSame($item['item_id'], $representation->id);
        self::assertSame($item['title'], $representation->title);
        self::assertSame($item['description'], $representation->post_processed_description);
        self::assertSame($this->status_mapper->getItemStatusFromItemStatusNumber($item['status']), $representation->status);
        self::assertSame($item['user_id'], $representation->owner->id);
    }
}
