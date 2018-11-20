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
      "oid": "12345678",
      "size": 123
    },
    {
      "oid": "98765432",
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
        $this->assertSame('12345678', $objects[0]->getOID());
        $this->assertSame(123, $objects[0]->getSize());
        $this->assertSame('98765432', $objects[1]->getOID());
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
     * @expectedException \Tuleap\GitLFS\Batch\Request\IncorrectlyFormattedBatchRequestException
     */
    public function testParsingIncorrectBatchRequest($json_string)
    {
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
            ['{"operation": "download", "objects": ["myoid1", "myoid2"]}'],
            ['{"operation": "download", "objects": {}, "ref": {}}'],
            ['{"operation": "download", "objects": {"size": 123}}'],
            ['{"operation": "download", "objects": [{"size": 123}]}'],
            ['{"operation": "download", "objects": [{"size": "aaaaaaa"}]}'],
            ['{"operation": "download", "objects": [{"oid": "myoid", "size": -123}]}'],
            ['{"operation": "download", "objects": [], "transfers": "basic"}'],
            ['{"operation": "download", "objects": [], "transfers": [1]}'],
            ['{"operation": "download", "objects": [], "ref": {"is_branch": false}}'],
        ];
    }
}
