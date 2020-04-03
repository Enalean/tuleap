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

namespace Tuleap\Git\GitPHP;

require_once __DIR__ . '/../bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\TestCase;

class PackTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public const SHA1_PACK = 'e9760fd950093eb1efd59a6200469e8c2d8c9632';

    public const V1_PACK_INDEX_PATH                     = __DIR__ . '/fixtures/pack/v1.idx';
    public const V2_PACK_INDEX_PATH                     = __DIR__ . '/fixtures/pack/v2.idx';
    public const V2_PACK_INDEX_64BIT_INDEX_ENTRIES_PATH = __DIR__ . '/fixtures/pack/v2-64bit-index-entries.idx';

    public const ALL_PACK_INDEX_PATHS = [self::V1_PACK_INDEX_PATH, self::V2_PACK_INDEX_PATH, self::V2_PACK_INDEX_64BIT_INDEX_ENTRIES_PATH];

    /**
     * @var \Mockery\MockInterface
     */
    private $project;
    /**
     * @var vfsStreamFile
     */
    private $pack_archive_file;
    /**
     * @var vfsStreamFile
     */
    private $pack_index_file;

    /*
     * fixtures/pack/ contains a packed archive with 3 pack index: one in the V1 format, one in the V2 format and one
     * in V2 format with 64-bit index entries
     *
     * The packed archive has the following content:
     *
     *  SHA-1                                    type   size size-in-packfile offset-in-packfile  SHA-256 content
     *  ----------------------------------------+------+----+----------------+------------------+-----------------------
     *  60bcae14911e8f4ec8949936ce5f3f4162abca0a commit 245  159              12                 db098907ae9b166cb9c8078858ba284024649c971c37fb89c21d112d416264d2
     *  b8595a91510cdbe9ff5dffe462ddd6dd9b891e70 commit 263  172              171                3689668329eeeaa7c768a063a0014d0316e62a86dbb3c6096e70a7d392a8084c
     *  f06b139a88a0ed88ac9ced5d4430c3cb40a11355 commit 248  162              343                4bad269f501bfb86fc0bfdbd793dde9ac845b7b30ffd23bdb7b47d12e4ee72c1
     *  469f89045dbaeb0579d2fe3e49ad135191539860 commit 252  165              505                5fb6305e2837bb86e6af72a72750929e380dbf5028982b6d6c2f20df56fc9b4d
     *  8aa552b73e90cb3d06a2cc3301833361e3daa48a commit 197  127              670                2e4571b2170cb18272d87e0804e48e907d4b7ea15a3399240c9aca4938bd0782
     *  19278b82b9d36068eba33746cc8601acf097cc60 tree   75   82               797                a76851e6271882f9a3a9d176e922c04af49dfe5fdc26317c915118c424c9c9e5
     *  b8b179b679c18fa4f112b34427125b35cefddd8a tree   75   82               879                33f227c6424a709901695138feca33765c31ef4512feb5de2059d31f03c53394
     *  45e4d9873aca93dfaefae43da5a080c82527a45a tree   75   82               961                55a267fd657fe8f419107e83a5ff9c6196fed103f51d37b18d181da632993160
     *  9d68bb847fde2fe5ea3605dce0e8214b1390ee11 tree   75   83               1043               7ea49df8304325c15f4f450e0fad52a0af3bec53d9d36b39bdfb6801d2def890
     *  ad123a385f6c7d11ca711427e575d1530239caad tree   37   47               1126               09a9c73f1ebe73d202da303f835b429583d306643ba1b269893033eef64f428d
     *  15f8b4788fc689400c9513bb98c732810d085089 blob   39   47               1173               db502260d8e7337fa5f06c56ad3513a98f61b6f20e173a82e540d35e7520a065
     *  309773b6731ef5318f1e724b4d283bbcc9159749 blob   41   51               1220               d4fb5368b02856c70603bff7bdf478be6c51c8c4398e2108934f2b9102cceadf
     *  980a0d5f19a64b4b30a87d4206aade58726b60e3 blob   13   22               1271               03ba204e50d126e4674c005e04d82e84c21366780af1f43bd54a37816b6ab340
     *  33c5178eeefe840662ab8a9df40042378beb29bb blob   34   44               1293               b226536c29caa3b348a93e5b27defb9cc327458995950e2297a7d8eefb94b51b
     *  74b6c7ad1241339bfcc8b00a1caadf444eca141a blob   33   43               1337               707489da83ed56dc38a7e7a33c30a1a443ed495ba8f1155aa8583c4cf70f6f70
     *
     * In the V2 pack index with 64-bit index entries, all Git objects located after the offset 1200
     * use 64-bit index entries
     */
    protected function setUp(): void
    {
        $git_repository = vfsStream::setup(
            'git_repo',
            null,
            [
                'objects' => [
                    'pack' => []
                ]
            ]
        );
        $this->pack_archive_file = vfsStream::newFile('pack-' . self::SHA1_PACK . '.pack')->at(
            $git_repository->getChild('objects/pack')
        )->withContent(file_get_contents(__DIR__ . '/fixtures/pack/archive.pack'));
        $this->pack_index_file = vfsStream::newFile('pack-' . self::SHA1_PACK . '.idx')->at(
            $git_repository->getChild('objects/pack')
        );

        $this->project = \Mockery::mock(Project::class);
        $this->project->shouldReceive('GetPath')->andReturns($git_repository->url());
    }

    /**
     * @@dataProvider objectReferenceProvider
     */
    public function testContainsObject($object_reference, $expected, $pack_index_path)
    {
        $this->pack_index_file->withContent(file_get_contents($pack_index_path));

        $pack = new Pack($this->project, self::SHA1_PACK);
        $this->assertSame($expected, $pack->ContainsObject($object_reference));
    }

    public function objectReferenceProvider()
    {
        $reference_tests = [
            ['60bcae14911e8f4ec8949936ce5f3f4162abca0a', true],
            ['469f89045dbaeb0579d2fe3e49ad135191539860', true],
            ['15f8b4788fc689400c9513bb98c732810d085089', true],
            ['74b6c7ad1241339bfcc8b00a1caadf444eca141a', true],
            ['aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', false],
            ['invalid_reference_format', false],
        ];

        $full_reference_tests = [];
        foreach ($reference_tests as $reference_test) {
            foreach (self::ALL_PACK_INDEX_PATHS as $pack_index_path) {
                $reference_test[] = $pack_index_path;
                $full_reference_tests[] = $reference_test;
            }
        }

        return $full_reference_tests;
    }

    /**
     * @dataProvider objectProvider
     */
    public function testGetObject($object_reference, $sha256_expected_content, $expected_type, $pack_index_path)
    {
        $this->pack_index_file->withContent(file_get_contents($pack_index_path));

        $pack           = new Pack($this->project, self::SHA1_PACK);
        $object_content = $pack->GetObject($object_reference, $type);

        if ($sha256_expected_content === null) {
            $this->assertFalse($object_content);
            return;
        }
        $this->assertSame($sha256_expected_content, hash('sha256', $object_content));
        $this->assertSame($type, $expected_type);
    }

    public function objectProvider()
    {
        $object_tests = [
            ['60bcae14911e8f4ec8949936ce5f3f4162abca0a', 'db098907ae9b166cb9c8078858ba284024649c971c37fb89c21d112d416264d2', Pack::OBJ_COMMIT],
            ['45e4d9873aca93dfaefae43da5a080c82527a45a', '55a267fd657fe8f419107e83a5ff9c6196fed103f51d37b18d181da632993160', Pack::OBJ_TREE],
            ['74b6c7ad1241339bfcc8b00a1caadf444eca141a', '707489da83ed56dc38a7e7a33c30a1a443ed495ba8f1155aa8583c4cf70f6f70', Pack::OBJ_BLOB],
            ['bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', null, null],
            ['invalid_reference_format', null, null],
        ];

        $full_object_tests = [];
        foreach ($object_tests as $object_test) {
            foreach (self::ALL_PACK_INDEX_PATHS as $pack_index_path) {
                $object_test[] = $pack_index_path;
                $full_object_tests[] = $object_test;
            }
        }

        return $full_object_tests;
    }

    public function testGetObjectRejectsPackWithInvalidSignature()
    {
        $this->pack_index_file->withContent(file_get_contents(self::V2_PACK_INDEX_PATH));
        $this->pack_archive_file->withContent('NOTAPACK');

        $pack           = new Pack($this->project, self::SHA1_PACK);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unsupported pack format');

        $pack->GetObject('60bcae14911e8f4ec8949936ce5f3f4162abca0a', $type);
    }

    public function testGetObjectRejectsPackWithUnsupportedVersion()
    {
        $this->pack_index_file->withContent(file_get_contents(self::V2_PACK_INDEX_PATH));
        $this->pack_archive_file->withContent('PACK' . pack('N', 9));

        $pack           = new Pack($this->project, self::SHA1_PACK);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unsupported pack format');

        $pack->GetObject('60bcae14911e8f4ec8949936ce5f3f4162abca0a', $type);
    }
}
