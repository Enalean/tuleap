<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
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

require_once dirname(__FILE__) .'/../../include/autoload.php';
require_once dirname(__FILE__) .'/../../../docman/include/autoload.php';
require_once dirname(__FILE__).'/../builders/Docman_File_Builder.php';

class RequestDocmanDataFactoryTest extends TuleapTestCase {

    /* @var ElasticSearch_1_2_RequestDocmanDataFactory */
    protected $request_data_factory;

    public function setUp() {
        parent::setUp();

        $this->date_metadata_title = stub('Docman_Metadata')->getLabel()->returns('date01');
        stub($this->date_metadata_title)->getId()->returns(15);

        $this->date_metadata_title_2 = stub('Docman_Metadata')->getLabel()->returns('date02');
        stub($this->date_metadata_title_2)->getId()->returns(17);

        $this->text_metadata_title = stub('Docman_Metadata')->getLabel()->returns('text01');
        stub($this->text_metadata_title)->getId()->returns(4);

        $this->text_metadata_title_2 = stub('Docman_Metadata')->getLabel()->returns('text02');
        stub($this->text_metadata_title_2)->getId()->returns(3);

        $this->item = stub('Docman_Item')->getGroupId()->returns(200);

        $this->metadata_factory = mock('Docman_MetadataFactory');
        stub($this->metadata_factory)->getMetadataValue($this->item, $this->date_metadata_title)->returns(1403160945);
        stub($this->metadata_factory)->getMetadataValue($this->item, $this->date_metadata_title_2)->returns(1403160949);
        stub($this->metadata_factory)->getMetadataValue($this->item, $this->text_metadata_title)->returns('val01');
        stub($this->metadata_factory)->getMetadataValue($this->item, $this->text_metadata_title_2)->returns('val02');

        $item_date_metadatas = array(
            $this->date_metadata_title,
            $this->date_metadata_title_2
        );

        stub($this->metadata_factory)->getRealMetadataList(
            false,
            array(PLUGIN_DOCMAN_METADATA_TYPE_DATE)
        )->returns($item_date_metadatas);

        $item_text_metadatas = array(
            $this->text_metadata_title,
            $this->text_metadata_title_2
        );

        stub($this->metadata_factory)->getRealMetadataList(
            false,
            array(
                PLUGIN_DOCMAN_METADATA_TYPE_TEXT,
                PLUGIN_DOCMAN_METADATA_TYPE_STRING
            )
        )->returns($item_text_metadatas);

        $hardcoded_metadata_title = stub('Docman_Metadata')->getLabel()->returns('title');
        stub($hardcoded_metadata_title)->getType()->returns(PLUGIN_DOCMAN_METADATA_TYPE_STRING);

        $hardcoded_metadata_description = stub('Docman_Metadata')->getLabel()->returns('description');
        stub($hardcoded_metadata_description)->getType()->returns(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);

        $hardcoded_metadata_owner = stub('Docman_Metadata')->getLabel()->returns('owner');
        stub($hardcoded_metadata_owner)->getType()->returns(PLUGIN_DOCMAN_METADATA_TYPE_STRING);

        $hardcoded_metadata_create_date = stub('Docman_Metadata')->getLabel()->returns('create_date');
        stub($hardcoded_metadata_create_date)->getType()->returns(PLUGIN_DOCMAN_METADATA_TYPE_DATE);

        $hardcoded_metadata_update_date = stub('Docman_Metadata')->getLabel()->returns('update_date');
        stub($hardcoded_metadata_update_date)->getType()->returns(PLUGIN_DOCMAN_METADATA_TYPE_DATE);

        $hardcoded_metadata_status = stub('Docman_Metadata')->getLabel()->returns('status');
        stub($hardcoded_metadata_status)->getType()->returns(PLUGIN_DOCMAN_METADATA_TYPE_LIST);

        $hardcoded_metadata_obsolescence_date = stub('Docman_Metadata')->getLabel()->returns('obsolescence_date');
        stub($hardcoded_metadata_obsolescence_date)->getType()->returns(PLUGIN_DOCMAN_METADATA_TYPE_DATE);

        $hardcoded_metadata = array(
            $hardcoded_metadata_title,
            $hardcoded_metadata_description,
            $hardcoded_metadata_owner,
            $hardcoded_metadata_create_date,
            $hardcoded_metadata_update_date,
            $hardcoded_metadata_update_date,
            $hardcoded_metadata_obsolescence_date
        );

        stub($this->metadata_factory)->getHardCodedMetadataList()->returns($hardcoded_metadata);

        $this->approval_table_factories_factory = mock('Docman_ApprovalTableFactoriesFactory');

        $this->permissions_manager = stub('Docman_PermissionsItemManager')
            ->exportPermissions($this->item)
            ->returns(array(3, 102)
        );

        $this->request_data_factory = new ElasticSearch_1_2_RequestDocmanDataFactory(
            $this->metadata_factory,
            $this->permissions_manager,
            $this->approval_table_factories_factory
        );
    }

    public function itBuildsTextMetadataValues() {
        $expected_data = array(
            'property_4' => 'val01',
            'property_3' => 'val02'
        );

        $this->assertEqual(
            $expected_data,
            $this->request_data_factory->getCustomTextualMetadataValue(
                $this->item
            )
        );
    }

    public function itBuildsCustomDateForMapping() {
        $expected_data = array(
            '200' => array(
                'properties' => array(
                    'property_15' => array(
                        'type' => 'date',
                        'format' => 'date_time_no_millis'
                    ),
                    'property_17' => array(
                        'type' => 'date',
                        'format' => 'date_time_no_millis'
                    )
                )
            )
        );

        $mapping = array(
            'docman' => array(
                'mappings' => array(
                    '200' => array(
                        'properties' => array(
                            'title' => array(
                                'type' => 'string'
                            )
                        )
                    )
                )
            )
        );

        $this->assertEqual(
            $expected_data,
            $this->request_data_factory->getPUTDateMappingMetadata(
                $this->item,
                $mapping
            )
        );
    }

    public function itBuildsCustomDateDataForItem() {
        $expected_data = array(
            'property_15' => '2014-06-19T08:55:45+02:00',
            'property_17' => '2014-06-19T08:55:49+02:00',
        );

        $this->assertEqual(
            $expected_data,
            $this->request_data_factory->getCustomDateMetadataValues(
                $this->item
            )
        );
    }

    public function itBuildsDataForPutRequestCreateMapping() {
        $project_id = 200;

        $expected_data = array(
            '200' => array(
                'properties' => array(
                    'title' => array(
                        'type' => 'string'
                    ),
                    'description' => array(
                        'type' => 'string'
                    ),
                    'owner' => array(
                        'type' => 'string'
                    ),
                    'create_date' => array(
                        'type' => 'date',
                        'format' => 'date_time_no_millis'
                    ),
                    'update_date' => array(
                        'type' => 'date',
                        'format' => 'date_time_no_millis'
                    ),
                    'obsolescence_date' => array(
                        'type' => 'date',
                        'format' => 'date_time_no_millis'
                    ),
                    'file' => array(
                        'type'   => 'attachment',
                        'fields' => array(
                            'title' => array(
                                'store' => 'yes'
                            ),
                            'file' => array(
                                'term_vector' => 'with_positions_offsets',
                                'store'       => 'yes'
                            )
                        )
                    ),
                    'content' => array(
                        'type'  => 'string'
                    ),
                    'permissions' => array(
                        'type'  => 'string',
                        'index' => 'not_analyzed'
                    ),
                    'approval_table_comments' => array(
                        'properties' => array(
                            'user_id' => array(
                                'type' => 'integer',
                            ),
                            'date_added' => array(
                                'type' => 'date',
                                'format' => 'date_time_no_millis'
                            ),
                            'comment' => array(
                                'type' => 'string',
                            ),
                        )
                    )
                )
            )
        );
        
        $this->assertEqual(
            $expected_data,
            $this->request_data_factory->getPUTMappingData($project_id)
        );
    }

}

class getDocumentApprovalTableComments extends RequestDocmanDataFactoryTest {

    private $other_item;
    private $approval_table_file_factory;
    private $version;
    
    public function setUp() {
        parent::setUp();

        $this->other_item = aDocman_File()
            ->withId(101)
            ->withTitle('Coin')
            ->withDescription('Duck typing')
            ->withGroupId(200)
            ->build();

        $this->approval_table_file_factory = mock('Docman_ApprovalTableFileFactory');
        $this->version                     = mock('Docman_Version');
    }

    public function itReturnsEmptyArrayIfNoTableForDoc() {
        stub($this->approval_table_file_factory)->getTable()->returns(null);
        stub($this->approval_table_factories_factory)
            ->getSpecificFactoryFromItem($this->other_item)->returns($this->approval_table_file_factory);

        $comments = $this->request_data_factory->getDocumentApprovalTableComments($this->other_item, $this->version);

        $this->assertEqual($comments, array());
    }

    public function itReturnsArrayOfReviews() {
        $review_1 = mock('Docman_ApprovalReviewer');
        stub($review_1)->getId()->returns(4580);
        stub($review_1)->getReviewDate()->returns(012154465325);
        stub($review_1)->getComment()->returns('I like it like that');

        $review_2 = mock('Docman_ApprovalReviewer');
        stub($review_2)->getId()->returns(333);
        stub($review_2)->getReviewDate()->returns(25448);
        stub($review_2)->getComment()->returns('Looks good to me, approved');

        $reviewer_factory = mock('Docman_ApprovalTableReviewerFactory');
        stub($reviewer_factory)->getReviewerListForLatestVersion()->returns(
            array($review_1,$review_2)
        );

        stub($this->approval_table_file_factory)->getTable()->returns(mock('Docman_ApprovalTable'));
        stub($this->approval_table_factories_factory)->getReviewerFactory()->returns($reviewer_factory);

        stub($this->approval_table_factories_factory)
            ->getSpecificFactoryFromItem($this->other_item)->returns($this->approval_table_file_factory);

        $comments = $this->request_data_factory->getDocumentApprovalTableComments($this->other_item, $this->version);

        $this->assertCount($comments, 2);

        $this->assertEqual($comments[0]['user_id'], 4580);
        $this->assertEqual($comments[1]['user_id'], 333);

        $this->assertEqual($comments[0]['date_added'], 012154465325);
        $this->assertEqual($comments[1]['date_added'], 25448);

        $this->assertEqual($comments[0]['comment'], 'I like it like that');
        $this->assertEqual($comments[1]['comment'], 'Looks good to me, approved');
    }
}

class RequestWikiDataFactoryTest extends TuleapTestCase {

    /* @var ElasticSearch_1_2_RequestWikiDataFactory */
    protected $request_data_factory;

    /* @var WikiPage */
    protected $wiki_page;

    /* @var UserManager */
    protected $user_manager;

    /** @var PFUser */
    protected $user;

    public function setUp() {
        parent::setUp();

        $this->permissions_manager = mock('Wiki_PermissionsManager');

        $this->user         = aUser()->withId(123)->build();
        $this->user_manager = stub('UserManager')->getUserByUserName('*')->returns($this->user);

        $this->request_data_factory = new ElasticSearch_1_2_RequestWikiDataFactory(
            $this->permissions_manager,
            $this->user_manager
        );

        $this->wiki_page = stub(\Tuleap\PHPWiki\WikiPage::class)->getPagename()->returns('wiki_page');
        stub($this->wiki_page)->getId()->returns(1940);
        stub($this->wiki_page)->getGid()->returns(200);
    }

    public function itBuildsDataForFirstIndexationOfWikiPage() {
        stub($this->wiki_page)->getMetadata()->returns(array(
            'mtime' => 1405061249,
            'author_id' => null,
        ));

        stub($this->permissions_manager)->getFromattedUgroupsThatCanReadWikiPage()->returns(array(
            '@site_active'
        ));

        $expected_data = array(
            'id'                 => 1940,
            'group_id'           => 200,
            'page_name'          => 'wiki_page',
            'last_modified_date' => '2014-07-11T08:47:29+02:00',
            'last_author'        => null,
            'last_summary'       => '',
            'content'            => '',
            'permissions'        => array('@site_active')
        );

        $this->assertEqual(
            $expected_data,
            $this->request_data_factory->getIndexedWikiPageData($this->wiki_page)
        );
    }

    public function itBuildsDataForFirstIndexationOfWikiPageOnUpdate() {
        stub($this->wiki_page)->getMetadata()->returns(array(
            'mtime'     => 1405061249,
            'author'    => 'user',
            'author_id' => 123,
            'content'   => 'cont',
            'summary'   => 'sum'
        ));

        stub($this->permissions_manager)->getFromattedUgroupsThatCanReadWikiPage()->returns(array(
            '@site_active'
        ));

        $expected_data = array(
            'id'                 => 1940,
            'group_id'           => 200,
            'page_name'          => 'wiki_page',
            'last_modified_date' => '2014-07-11T08:47:29+02:00',
            'last_author'        => 123,
            'last_summary'       => 'sum',
            'content'            => 'cont',
            'permissions'        => array('@site_active')
        );

        $this->assertEqual(
            $expected_data,
            $this->request_data_factory->getIndexedWikiPageData($this->wiki_page)
        );
    }

    public function itBuildsDataForPutRequestCreateMapping() {
        $project_id = 200;

        $expected_data = array(
            '200' => array(
                'properties' => array(
                    'page_name' => array(
                        'type' => 'string'
                    ),
                    'last_modified_date' => array(
                        'type' => 'date',
                        'format' => 'date_time_no_millis'
                    ),
                    'last_author' => array(
                        'type' => 'long'
                    ),
                    'last_summary' => array(
                        'type' => 'string'
                    ),
                    'content' => array(
                        'type' => 'string'
                    ),
                    'permissions' => array(
                        'type'  => 'string',
                        'index' => 'not_analyzed'
                    )
                )
            )
        );

        $this->assertEqual(
            $expected_data,
            $this->request_data_factory->getPUTMappingData($project_id)
        );
    }
}
