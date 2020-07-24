<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\BrowserDetection;

/**
 * @psalm-immutable
 */
final class DetectedBrowser
{
    private const INTERNET_EXPLORER = 'Internet Explorer';
    private const EDGE_LEGACY       = 'Edge Legacy';
    // Based on https://github.com/matomo-org/device-detector/blob/3.12.6/regexes/client/browsers.yml
    private const BROWSER_REGEXES = [
        self::INTERNET_EXPLORER => [
            'MSIE.*Trident/4.0'  => 8.0,
            'MSIE.*Trident/5.0'  => 9.0,
            'MSIE.*Trident/6.0'  => 10.0,
            'Trident/[78].0'     => 11.0,
            'MSIE (\d+[\.\d]+)'  => null,
            'IE[ /](\d+[\.\d]+)' => null,
        ],
        self::EDGE_LEGACY => [
            'Edge[ /](\d+[\.\d]+)' => null,
        ],
        'Edge' => [
            'Edg[ /](\d+[\.\d]+)' => null,
        ],
        'Firefox' => [
            '.*Servo.*Firefox(?:/(\d+[\.\d]+))?' => null,
            'Firefox(?:/(\d+[\.\d]+))?'          => null,
        ],
        'Chrome' => [
            'Chromium(?:/(\d+[\.\d]+))?'       => null,
            'Chrome(?!book)(?:/(\d+[\.\d]+))?' => null
        ]
    ];

    /**
     * @var string|null
     * @psalm-var key-of<self::BROWSER_REGEXES>|null
     */
    private $name;
    /**
     * @var float|null
     */
    private $version;

    private function __construct(string $user_agent)
    {
        foreach (self::BROWSER_REGEXES as $name => $specific_browser_regexes) {
            foreach ($specific_browser_regexes as $specific_browser_regex => $known_version) {
                if (preg_match('@' . $specific_browser_regex . '@', $user_agent, $matches) === 1) {
                    $this->name = $name;
                    if ($known_version === null && isset($matches[1])) {
                        $this->version = (float) $matches[1];
                    } else {
                        $this->version = $known_version;
                    }
                    return;
                }
            }
        }
    }

    public static function detectFromTuleapHTTPRequest(\HTTPRequest $request): self
    {
        return new self($request->getFromServer('HTTP_USER_AGENT') ?: '');
    }

    public function isIE11(): bool
    {
        return $this->name === self::INTERNET_EXPLORER && $this->version !== null && $this->version >= 11.0;
    }

    public function isIEBefore11(): bool
    {
        return $this->name === self::INTERNET_EXPLORER && ! $this->isIE11();
    }

    public function isEdgeLegacy(): bool
    {
        return $this->name === self::EDGE_LEGACY;
    }

    /**
     * @psalm-return key-of<self::BROWSER_REGEXES>|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }
}
