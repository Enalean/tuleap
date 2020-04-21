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

namespace Tuleap\GitLFS\Batch\Request;

use PHPUnit\Framework\TestCase;

class BatchRequestTest extends TestCase
{
    public function testParsingRequestWithAllProperties()
    {
        $json = <<<JSON
{
  "operation": "upload",
  "transfers": [ "basic" ],
  "ref": { "name": "refs/heads/contrib" },
  "objects": [
    {
      "oid": "ca978112ca1bbdcafac231b39a23dc4da786eff8147c4e72b9807785afee48bb",
      "size": 123
    },
    {
      "oid": "3e23e8160039594a33894f6564e1b1348bbd7a0088d42c4acb73eeaed59c009d",
      "size": 456
    }
  ]
}
JSON;
        $batch_request = BatchRequest::buildFromJSONString($json);
        $this->assertTrue($batch_request->isWrite());
        $this->assertFalse($batch_request->isRead());
        $objects = $batch_request->getObjects();
        $this->assertCount(2, $objects);
        $this->assertSame('ca978112ca1bbdcafac231b39a23dc4da786eff8147c4e72b9807785afee48bb', $objects[0]->getOID()->getValue());
        $this->assertSame(123, $objects[0]->getSize());
        $this->assertSame('3e23e8160039594a33894f6564e1b1348bbd7a0088d42c4acb73eeaed59c009d', $objects[1]->getOID()->getValue());
        $this->assertSame(456, $objects[1]->getSize());
        $this->assertSame('basic', $batch_request->getTransfers()[0]->getIdentifier());
        $this->assertSame('refs/heads/contrib', $batch_request->getReference()->getName());
    }

    public function testParsingBatchRequestWithMinimalProperties()
    {
        $json          = '{"operation": "download", "objects": []}';
        $batch_request = BatchRequest::buildFromJSONString($json);

        $this->assertFalse($batch_request->isWrite());
        $this->assertTrue($batch_request->isRead());
        $this->assertEmpty($batch_request->getObjects());
        $this->assertSame('basic', $batch_request->getTransfers()[0]->getIdentifier());
        $this->assertNull($batch_request->getReference());
    }

    /**
     * @dataProvider providerIncorrectJSONBatchRequest
     */
    public function testParsingIncorrectBatchRequest($json_string)
    {
        $this->expectException(IncorrectlyFormattedBatchRequestException::class);
        BatchRequest::buildFromJSONString($json_string);
    }

    public function providerIncorrectJSONBatchRequest()
    {
        return [
            ['{bad_json'],
            ['{}'],
            ['{"objects": []}'],
            ['{"operation": 1, "objects": []}'],
            ['{"operation": "download", "objects": {}}'],
            ['{"operation": "download", "objects": ["ca978112ca1bbdcafac231b39a23dc4da786eff8147c4e72b9807785afee48bb", "3e23e8160039594a33894f6564e1b1348bbd7a0088d42c4acb73eeaed59c009d"]}'],
            ['{"operation": "download", "objects": {}, "ref": {}}'],
            ['{"operation": "download", "objects": {"size": 123}}'],
            ['{"operation": "download", "objects": [{"size": 123}]}'],
            ['{"operation": "download", "objects": [{"size": "aaaaaaa"}]}'],
            ['{"operation": "download", "objects": [{"oid": "ca978112ca1bbdcafac231b39a23dc4da786eff8147c4e72b9807785afee48bb", "size": -123}]}'],
            ['{"operation": "download", "objects": [{"oid": "broken_oid", "size": 123}]}'],
            ['{"operation": "download", "objects": [], "transfers": "basic"}'],
            ['{"operation": "download", "objects": [], "transfers": [1]}'],
            ['{"operation": "download", "objects": [], "ref": {"is_branch": false}}'],
        ];
    }
}
