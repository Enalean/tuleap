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

namespace Tuleap\Docman\Upload;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DocumentToUploadCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $dao;
    private $mockery_matcher_callback_wrapped_operations;

    public function setUp()
    {
        \ForgeConfig::store();

        $this->dao = \Mockery::mock(DocumentOngoingUploadDAO::class);

        $this->mockery_matcher_callback_wrapped_operations = \Mockery::on(
            function (callable $operations) {
                $operations($this->dao);
                return true;
            }
        );
    }

    public function tearDown()
    {
        \ForgeConfig::restore();
    }

    public function testCreation()
    {
        $this->dao->shouldReceive('wrapAtomicOperations')->with($this->mockery_matcher_callback_wrapped_operations);
        $creator = new DocumentToUploadCreator($this->dao);

        \ForgeConfig::set('sys_max_size_upload', '999999');
        $parent_item = \Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);
        $current_time = new \DateTimeImmutable();

        $this->dao->shouldReceive('searchDocumentOngoingUploadByParentIDTitleAndExpirationDate')->andReturns([]);
        $this->dao->shouldReceive('saveDocumentOngoingUpload')->once()->andReturns(12);

        $document_to_upload = $creator->create(
            $parent_item,
            $user,
            $current_time,
            'title',
            'description',
            'filename',
            123456
        );

        $this->assertSame(12, $document_to_upload->getItemId());
    }

    public function testANewItemIsNotCreatedIfAnUploadIsOngoingWithTheSameFile()
    {
        $this->dao->shouldReceive('wrapAtomicOperations')->with($this->mockery_matcher_callback_wrapped_operations);
        $creator = new DocumentToUploadCreator($this->dao);

        \ForgeConfig::set('sys_max_size_upload', '999999');
        $parent_item = \Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);
        $current_time = new \DateTimeImmutable();

        $this->dao->shouldReceive('searchDocumentOngoingUploadByParentIDTitleAndExpirationDate')->andReturns([
            ['item_id' => 12, 'user_id' => 102, 'filename' => 'filename', 'filesize' => 123456]
        ]);

        $document_to_upload = $creator->create(
            $parent_item,
            $user,
            $current_time,
            'title',
            'description',
            'filename',
            123456
        );

        $this->assertSame(12, $document_to_upload->getItemId());
    }

    /**
     * @expectedException \Tuleap\Docman\Upload\DocumentToUploadCreationConflictException
     */
    public function testCreationIsRejectedWhenAnotherUserIsCreatingTheDocument()
    {
        $this->dao->shouldReceive('wrapAtomicOperations')->with($this->mockery_matcher_callback_wrapped_operations);
        $creator = new DocumentToUploadCreator($this->dao);

        \ForgeConfig::set('sys_max_size_upload', '999999');
        $parent_item = \Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);
        $current_time = new \DateTimeImmutable();

        $this->dao->shouldReceive('searchDocumentOngoingUploadByParentIDTitleAndExpirationDate')->andReturns([
            ['user_id' => 103]
        ]);

        $creator->create(
            $parent_item,
            $user,
            $current_time,
            'title',
            'description',
            'filename',
            123456
        );
    }

    /**
     * @expectedException \Tuleap\Docman\Upload\DocumentToUploadCreationFileMismatchException
     */
    public function testCreationIsRejectedWhenTheUserIsAlreadyCreatingTheDocumentWithAnotherFile()
    {
        $this->dao->shouldReceive('wrapAtomicOperations')->with($this->mockery_matcher_callback_wrapped_operations);
        $creator = new DocumentToUploadCreator($this->dao);

        \ForgeConfig::set('sys_max_size_upload', '999999');
        $parent_item = \Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);
        $current_time = new \DateTimeImmutable();

        $this->dao->shouldReceive('searchDocumentOngoingUploadByParentIDTitleAndExpirationDate')->andReturns([
            ['user_id' => 102, 'filename' => 'filename1', 'filesize' => 123456]
        ]);

        $creator->create(
            $parent_item,
            $user,
            $current_time,
            'title',
            'description',
            'filename2',
            789
        );
    }

    /**
     * @expectedException \Tuleap\Docman\Upload\DocumentToUploadMaxSizeExceededException
     */
    public function testCreationIsRejectedIfTheFileIsBiggerThanTheConfigurationLimit()
    {
        $creator = new DocumentToUploadCreator($this->dao);

        \ForgeConfig::set('sys_max_size_upload', '1');
        $parent_item = \Mockery::mock(\Docman_Item::class);
        $user = \Mockery::mock(\PFUser::class);
        $current_time = new \DateTimeImmutable();

        $creator->create(
            $parent_item,
            $user,
            $current_time,
            'title',
            'description',
            'filename',
            2
        );
    }
}
