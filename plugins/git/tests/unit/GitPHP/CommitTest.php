<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git\GitPHP;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CommitTest extends \Tuleap\Test\PHPUnit\TestCase
{
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
    public const EXPECTED_PGP_SIGNATURE            = <<<EOF
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

    private const COMMIT_CONTENT_WITH_SSH_SIGNATURE   = <<<EOF
tree 5629642f8af097de357900d8d584ad32a85201a8
author Author 1 <author@example.com> 1637241693 +0100
committer Author 1 <author@example.com> 1637241693 +0100
gpgsig -----BEGIN SSH SIGNATURE-----
 U1NIU0lHAAAAAQAAAH8AAAAic2stZWNkc2Etc2hhMi1uaXN0cDI1NkBvcGVuc3NoLmNvbQ
 AAAAhuaXN0cDI1NgAAAEEEYNYJClXo1hQVe2JySYQNfhykqrARyVnwj3l4qWwtRfaHjxGE
 Y01p11rH22sXp4WgeG99xBoDFP0OJUqXKWlMTgAAAARzc2g6AAAAA2dpdAAAAAAAAAAGc2
 hhNTEyAAAAdwAAACJzay1lY2RzYS1zaGEyLW5pc3RwMjU2QG9wZW5zc2guY29tAAAASAAA
 ACB4cHyGYW9P7vyfiotV8PZEEGawu9U+KIZeXEsTIQD6mQAAACBNZ+EPHf6U03ZnvZBSOX
 UduqpSl0RUv9J1FUKo2kJo9gEAABmi
 -----END SSH SIGNATURE-----

A
EOF;
    private const EXPECTED_SSH_SIGNATURE              = <<<EOF
-----BEGIN SSH SIGNATURE-----
U1NIU0lHAAAAAQAAAH8AAAAic2stZWNkc2Etc2hhMi1uaXN0cDI1NkBvcGVuc3NoLmNvbQ
AAAAhuaXN0cDI1NgAAAEEEYNYJClXo1hQVe2JySYQNfhykqrARyVnwj3l4qWwtRfaHjxGE
Y01p11rH22sXp4WgeG99xBoDFP0OJUqXKWlMTgAAAARzc2g6AAAAA2dpdAAAAAAAAAAGc2
hhNTEyAAAAdwAAACJzay1lY2RzYS1zaGEyLW5pc3RwMjU2QG9wZW5zc2guY29tAAAASAAA
ACB4cHyGYW9P7vyfiotV8PZEEGawu9U+KIZeXEsTIQD6mQAAACBNZ+EPHf6U03ZnvZBSOX
UduqpSl0RUv9J1FUKo2kJo9gEAABmi
-----END SSH SIGNATURE-----
EOF;
    private const COMMIT_CONTENT_WITH_SMIME_SIGNATURE = <<<EOF
tree d2b910d56a8d757357603ef0cd2a5ecb9293f53f
author Author 1 <author@example.com> 1637242994 +0100
committer Author 1 <author@example.com> 1637242994 +0100
gpgsig -----BEGIN SIGNED MESSAGE-----
 MIAGCSqGSIb3DQEHAqCAMIACAQExDzANBglghkgBZQMEAgEFADCABgkqhkiG9w0B
 BwEAADGCAl8wggJbAgEBMB4wEjEQMA4GA1UEAxMHZXhhbXBsZQIIJ9rpuwCvkYIw
 DQYJYIZIAWUDBAIBBQCggZMwGAYJKoZIhvcNAQkDMQsGCSqGSIb3DQEHATAcBgkq
 hkiG9w0BCQUxDxcNMjExMTE4MTM0MzE0WjAoBgkqhkiG9w0BCQ8xGzAZMAsGCWCG
 SAFlAwQBAjAKBggqhkiG9w0DBzAvBgkqhkiG9w0BCQQxIgQgeO44VkFUa7uH8PMb
 /jjX60JKU2wWVlC9nOT64wfRUi4wDQYJKoZIhvcNAQEBBQAEggGAMphsIx19F2MS
 RIcU04Hosby9nPhEgkm2lXfJJ1DCQnuwPWER31iZ9Lkvq01b+U+WVLWufQmt+GLY
 /gdbeIuKmAOYo529tqtONnhiFBBGL7Z9KRas0jny4MqzweE8QBzDJHfyv8c8/Yfk
 WpzbeyegKW9ZZtmK1sKQWbz/mG5MZHM1xaRDP+CtDBXmlydynwT0GLk+fxaACu7b
 rG1AK+lhaFPBQ3BJK9x+GpTZulMHCOXoK9lN/aojLiKro2MGasirIRAxtASoUvfL
 fF1LhL81OzCk+OZe7reEO5gEFX7JHJcn05h7/hiOvg47pTUrufpBpfhg0tNbTf9R
 eJpoJwMtY+4grJhdNfhbJgEt4mU9jm0H6KUtVuTZmQqBwfEDzJJuJRQhGAA0MI9k
 aIhbL/vdvo0eg1hnWICyJnKCR6Fd5ifYllfGh0rDjlIqxCCiZLYxR3Nq8bdrFXFt
 IdFV7BORBPs0tJG5MsN6VKsC4czW822OP2RQ8WOTOPreRxELUnkEAAAAAAAA
 -----END SIGNED MESSAGE-----

A
EOF;
    private const EXPECTED_SMIME_SIGNATURE            = <<<EOF
-----BEGIN SIGNED MESSAGE-----
MIAGCSqGSIb3DQEHAqCAMIACAQExDzANBglghkgBZQMEAgEFADCABgkqhkiG9w0B
BwEAADGCAl8wggJbAgEBMB4wEjEQMA4GA1UEAxMHZXhhbXBsZQIIJ9rpuwCvkYIw
DQYJYIZIAWUDBAIBBQCggZMwGAYJKoZIhvcNAQkDMQsGCSqGSIb3DQEHATAcBgkq
hkiG9w0BCQUxDxcNMjExMTE4MTM0MzE0WjAoBgkqhkiG9w0BCQ8xGzAZMAsGCWCG
SAFlAwQBAjAKBggqhkiG9w0DBzAvBgkqhkiG9w0BCQQxIgQgeO44VkFUa7uH8PMb
/jjX60JKU2wWVlC9nOT64wfRUi4wDQYJKoZIhvcNAQEBBQAEggGAMphsIx19F2MS
RIcU04Hosby9nPhEgkm2lXfJJ1DCQnuwPWER31iZ9Lkvq01b+U+WVLWufQmt+GLY
/gdbeIuKmAOYo529tqtONnhiFBBGL7Z9KRas0jny4MqzweE8QBzDJHfyv8c8/Yfk
WpzbeyegKW9ZZtmK1sKQWbz/mG5MZHM1xaRDP+CtDBXmlydynwT0GLk+fxaACu7b
rG1AK+lhaFPBQ3BJK9x+GpTZulMHCOXoK9lN/aojLiKro2MGasirIRAxtASoUvfL
fF1LhL81OzCk+OZe7reEO5gEFX7JHJcn05h7/hiOvg47pTUrufpBpfhg0tNbTf9R
eJpoJwMtY+4grJhdNfhbJgEt4mU9jm0H6KUtVuTZmQqBwfEDzJJuJRQhGAA0MI9k
aIhbL/vdvo0eg1hnWICyJnKCR6Fd5ifYllfGh0rDjlIqxCCiZLYxR3Nq8bdrFXFt
IdFV7BORBPs0tJG5MsN6VKsC4czW822OP2RQ8WOTOPreRxELUnkEAAAAAAAA
-----END SIGNED MESSAGE-----
EOF;


    #[\PHPUnit\Framework\Attributes\DataProvider('commitsProvider')]
    public function testContentIsRetrievedWhenAPGPSignatureIsPresent(
        $commit_content,
        $author_name,
        $author_email,
        $author_timestamp,
        $commit_message,
        $commit_signature,
    ): void {
        $project = $this->createMock(Project::class);
        $project->method('GetObject')->with('3f4a9ea9a9bcc19fa6f0806058469c5e4c35df82')->willReturn($commit_content);
        $commit = new Commit(new BlobDataReader(), $project, '3f4a9ea9a9bcc19fa6f0806058469c5e4c35df82');

        self::assertSame($author_name, $commit->GetAuthorName());
        self::assertSame($author_email, $commit->getAuthorEmail());
        self::assertSame($author_timestamp, $commit->GetAuthorEpoch());
        self::assertSame($commit_message, $commit->GetComment());
        self::assertSame($commit_signature, $commit->getSignature());
    }

    public static function commitsProvider(): array
    {
        return [
            [self::COMMIT_CONTENT, 'Author 1', 'author@example.com', '1534259619', ['This is Tuleap 10.4'], null],
            [
                self::COMMIT_CONTENT_WITH_PGP_SIGNATURE,
                'Author 1',
                'author@example.com',
                '1534259619',
                ['This is Tuleap 10.4'],
                self::EXPECTED_PGP_SIGNATURE,
            ],
            [
                self::COMMIT_CONTENT_WITH_SSH_SIGNATURE,
                'Author 1',
                'author@example.com',
                '1637241693',
                ['A'],
                self::EXPECTED_SSH_SIGNATURE,
            ],
            [
                self::COMMIT_CONTENT_WITH_SMIME_SIGNATURE,
                'Author 1',
                'author@example.com',
                '1637242994',
                ['A'],
                self::EXPECTED_SMIME_SIGNATURE,
            ],
        ];
    }
}
