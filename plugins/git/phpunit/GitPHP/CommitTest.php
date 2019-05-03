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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CommitTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public const COMMIT_CONTENT = <<<EOF
tree ee6b900783b06b774d401de9c4ef3ddf0d124574
parent 4e6c9adec89c15af454484dda60109ed604efca8
author Author 1 <author@example.com> 1534259619 +0300
committer Committer 1 <committer@example.com> 1534259719 +0200

This is Tuleap 10.4
EOF;

    public const COMMIT_CONTENT_WITH_PGP_SIGNATURE = <<<EOF
tree ee6b900783b06b774d401de9c4ef3ddf0d124574
parent 4e6c9adec89c15af454484dda60109ed604efca8
author Author 1 <author@example.com> 1534259619 +0300
committer Committer 1 <committer@example.com> 1534259719 +0200
gpgsig -----BEGIN PGP SIGNATURE-----
 
 iQEzBAABCgAdFiEEZYSpTRl85FSuRKh0m6S5XYk2HS0FAlty8aMACgkQm6S5XYk2
 HS2i1wgAmG6M4QqONTEHIFU69GhVE834ZLulGSmNZ96/I3WEerMJB/Hb0mk12Vie
 AH+5lly2QefD0BWcWSUY+8H5qdHNQSUauvZsS1K+JH2Kc+GRChKH7k1vzbEBVoe+
 oq5IAdAlsVTIVjoUhDSFHU8NAwBvLBdT0sIe5QF+wq67VnLf3r1ifBsskfloezlo
 QPcRFwAfUoM4Qjj/RlteS4BeAoYaOCaVs+28vRBEmmWd8l0alIIUyW2H9+EqKT2A
 fOcl5xH+qYQaFNI8BVfKAJWyA1u+isgGYrachT3vNF2021Q5YrNewZRlJwhgpi97
 lF2sUB3ZUuMKf4DlZILZL/DrYCbQBA==
 =NzkO
 -----END PGP SIGNATURE-----

This is Tuleap 10.4
EOF;
    public const EXPECTED_PGP_SIGNATURE = <<<EOF
-----BEGIN PGP SIGNATURE-----

iQEzBAABCgAdFiEEZYSpTRl85FSuRKh0m6S5XYk2HS0FAlty8aMACgkQm6S5XYk2
HS2i1wgAmG6M4QqONTEHIFU69GhVE834ZLulGSmNZ96/I3WEerMJB/Hb0mk12Vie
AH+5lly2QefD0BWcWSUY+8H5qdHNQSUauvZsS1K+JH2Kc+GRChKH7k1vzbEBVoe+
oq5IAdAlsVTIVjoUhDSFHU8NAwBvLBdT0sIe5QF+wq67VnLf3r1ifBsskfloezlo
QPcRFwAfUoM4Qjj/RlteS4BeAoYaOCaVs+28vRBEmmWd8l0alIIUyW2H9+EqKT2A
fOcl5xH+qYQaFNI8BVfKAJWyA1u+isgGYrachT3vNF2021Q5YrNewZRlJwhgpi97
lF2sUB3ZUuMKf4DlZILZL/DrYCbQBA==
=NzkO
-----END PGP SIGNATURE-----
EOF;


    /**
     * @dataProvider commitsProvider
     */
    public function testContentIsRetrievedWhenAPGPSignatureIsPresent(
        $commit_content,
        $author_name,
        $author_email,
        $author_timestamp,
        $commit_message,
        $pgp_signature
    ) {
        $project = \Mockery::mock(Project::class);
        $project->shouldReceive('GetObject')->with('3f4a9ea9a9bcc19fa6f0806058469c5e4c35df82')->andReturns($commit_content);
        $commit  = new Commit($project, '3f4a9ea9a9bcc19fa6f0806058469c5e4c35df82');

        $this->assertSame($author_name, $commit->GetAuthorName());
        $this->assertSame($author_email, $commit->getAuthorEmail());
        $this->assertSame($author_timestamp, $commit->GetAuthorEpoch());
        $this->assertSame($commit_message, $commit->GetComment());
        $this->assertSame($pgp_signature, $commit->getPGPSignature());
    }

    public function commitsProvider()
    {
        return [
            [self::COMMIT_CONTENT, 'Author 1', 'author@example.com', '1534259619', ['This is Tuleap 10.4'], null],
            [
                self::COMMIT_CONTENT_WITH_PGP_SIGNATURE,
                'Author 1',
                'author@example.com',
                '1534259619',
                ['This is Tuleap 10.4'],
                self::EXPECTED_PGP_SIGNATURE
            ],
        ];
    }
}
