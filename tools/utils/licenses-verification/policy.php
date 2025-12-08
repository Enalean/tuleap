#! /usr/bin/env php
<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

use CuyZ\Valinor\Mapper\MappingError;
use Tuleap\LicenseCheckPolicy\SBOM;
use Tuleap\LicenseCheckPolicy\SBOMComponent;
use Tuleap\LicenseCheckPolicy\SBOMComponentLicense;
use Tuleap\LicenseCheckPolicy\SBOMComponentLicenseExpression;
use Tuleap\LicenseCheckPolicy\SBOMComponentLicenseID;
use Tuleap\LicenseCheckPolicy\SBOMComponentLicenseName;

require_once __DIR__ . '/../../../src/vendor/autoload.php';
require_once __DIR__ . '/SBOM.php';

// This is part of the legal obligations of Enalean
// This MUST NOT be changed without confirmation of the legal team
const ALLOWED_LICENCES = [
    '0BSD', // Similar to ISC https://www.gnu.org/licenses/license-list.en.html#ISC
    // Tuleap is GPLv2.0+, making Apache-2.0 acceptable based on https://www.gnu.org/licenses/license-list.en.html#apache2
    // This has also been confirmed by the relicensing of Mercurial to be able to use Apache-2.0 code, see https://wiki.mercurial-scm.org/Relicensing
    'Apache-2.0',
    'Apache-2.0 WITH LLVM-exception', // Same as Apache-2.0 with some lifted restrictions
    'BlueOak-1.0.0', // Permissive license close to MIT, their own FAQ considers it acceptable https://blueoakcouncil.org/license-faq
    'BSD-2-Clause', // Permissive license https://www.gnu.org/licenses/license-list.en.html#FreeBSD
    'BSD-3-Clause', // Permissive license https://www.gnu.org/licenses/license-list.en.html#ModifiedBSD
    'BSD-Source-Code', // BSD-3-Clause
    'CC0-1.0', // Public domain https://www.gnu.org/licenses/license-list.en.html#CC0
    'GPL-2.0', // Same base license than Tuleap
    'GPL-2.0-only', // Same base license than Tuleap
    'GPL-2.0-or-later', // Same base license than Tuleap
    'GPL-3.0-only', // Compatible with GPLv2+ according to GPL compatibility matrix https://www.gnu.org/licenses/gpl-faq.en.html#gpl-compat-matrix
    'GPL-3.0-or-later', // Compatible with GPLv2+ according to GPL compatibility matrix https://www.gnu.org/licenses/gpl-faq.en.html#gpl-compat-matrix
    'Unicode-3.0', // Permissive license  https://www.gnu.org/licenses/license-list.en.html#Unicodev3
    'ISC', // Permissive license https://www.gnu.org/licenses/license-list.en.html#ISC
    'LGPL-2.1', // Compatible with GPLv2+ according to GPL compatibility matrix https://www.gnu.org/licenses/gpl-faq.en.html#gpl-compat-matrix
    'LGPL-2.1-or-later', // Compatible with GPLv2+ according to GPL compatibility matrix https://www.gnu.org/licenses/gpl-faq.en.html#gpl-compat-matrix
    'LGPL-3.0', // Compatible with GPLv2+ according to GPL compatibility matrix https://www.gnu.org/licenses/gpl-faq.en.html#gpl-compat-matrix
    'MIT', // Permissive license https://www.gnu.org/licenses/license-list.en.html#Expat
    'MIT-0', // Permissive license, MIT without attribution https://www.gnu.org/licenses/license-list.en.html#Expat0
    'Unlicense', // Public domain https://www.gnu.org/licenses/license-list.en.html#Unlicense
    'Zlib', // Compatible with GPL https://www.gnu.org/licenses/license-list.en.html#ZLib
];

const ALLOWED_LICENCES_SPECIFIC_USE_CASES = [
    'BSL-1.0' => [ // Permissive license https://www.gnu.org/licenses/license-list.en.html#boost
        'ryu', // Dual licensed with Apache-2.0 https://github.com/dtolnay/ryu/blob/master/LICENSE-APACHE
    ],
    'BSD-like' => [
        'css-select', // Old version use the improper name BSD-like instead of BSD-2-Clause https://github.com/fb55/css-select/blob/master/LICENSE
    ],
    'MPL-2.0' => [ // https://www.gnu.org/licenses/license-list.en.html#MPL-2.0
        'dompurify', // Dual licensed with Apache-2.0 https://github.com/cure53/DOMPurify/blob/main/LICENSE
        'cbindgen', // Tool used during the build phase, not part of the production builds
        'github.com/hashicorp/errwrap', // Tuleap Vault Plugin, not delivered with the rest of Tuleap
        'github.com/hashicorp/go-cleanhttp', // Tuleap Vault Plugin, not delivered with the rest of Tuleap
        'github.com/hashicorp/go-cleanhttp', // Tuleap Vault Plugin, not delivered with the rest of Tuleap
        'github.com/hashicorp/go-immutable-radix', // Tuleap Vault Plugin, not delivered with the rest of Tuleap
        'github.com/hashicorp/go-kms-wrapping/entropy/v2', // Tuleap Vault Plugin, not delivered with the rest of Tuleap
        'github.com/hashicorp/go-kms-wrapping/v2', // Tuleap Vault Plugin, not delivered with the rest of Tuleap
        'github.com/hashicorp/go-multierror', // Tuleap Vault Plugin, not delivered with the rest of Tuleap
        'github.com/hashicorp/go-plugin', // Tuleap Vault Plugin, not delivered with the rest of Tuleap
        'github.com/hashicorp/go-retryablehttp', // Tuleap Vault Plugin, not delivered with the rest of Tuleap
        'github.com/hashicorp/go-rootcerts', // Tuleap Vault Plugin, not delivered with the rest of Tuleap
        'github.com/hashicorp/go-secure-stdlib/mlock', // Tuleap Vault Plugin, not delivered with the rest of Tuleap
        'github.com/hashicorp/go-secure-stdlib/parseutil', // Tuleap Vault Plugin, not delivered with the rest of Tuleap
        'github.com/hashicorp/go-secure-stdlib/plugincontainer', // Tuleap Vault Plugin, not delivered with the rest of Tuleap
        'github.com/hashicorp/go-secure-stdlib/strutil', // Tuleap Vault Plugin, not delivered with the rest of Tuleap
        'github.com/hashicorp/go-sockaddr', // Tuleap Vault Plugin, not delivered with the rest of Tuleap
        'github.com/hashicorp/go-uuid', // Tuleap Vault Plugin, not delivered with the rest of Tuleap
        'github.com/hashicorp/go-version', // Tuleap Vault Plugin, not delivered with the rest of Tuleap
        'github.com/hashicorp/golang-lru', // Tuleap Vault Plugin, not delivered with the rest of Tuleap
        'github.com/hashicorp/hcl', // Tuleap Vault Plugin, not delivered with the rest of Tuleap
        'github.com/hashicorp/vault/api', // Tuleap Vault Plugin, not delivered with the rest of Tuleap
        'github.com/hashicorp/vault/sdk', // Tuleap Vault Plugin, not delivered with the rest of Tuleap
        'github.com/hashicorp/yamux', // Tuleap Vault Plugin, not delivered with the rest of Tuleap
        'eslint-plugin-no-unsanitized', // Dev tool, not shipped in production builds
    ],
    'CC-BY-4.0' => [ // Compatible, not for code license https://www.gnu.org/licenses/license-list.en.html#ccby
        '@fortawesome/free-regular-svg-icons', // Font, dual licenced under OFL-1.1
        '@fortawesome/free-solid-svg-icons', // Font, dual licenced under OFL-1.1
        '@fortawesome/fontawesome-free', // Font, dual licenced under OFL-1.1
    ],
    'CC-BY-SA-4.0' => [
        'github.com/opencontainers/go-digest', // Code is licensed under Apache-2.0, this was incorrectly set in the repo https://github.com/opencontainers/go-digest/commit/b22736afcd4ef34cfbdb8f0009a03566e620d1e4
    ],
    'OFL-1.1' => [ // Compatible when redistributed bundled with the rest of the code, https://www.gnu.org/licenses/license-list.en.html#SILOFL
        '@fontsource-variable/public-sans', // Font, only distributed bundled with the frontend code
        '@fortawesome/free-regular-svg-icons', // Font, only distributed bundled with the frontend code
        '@fortawesome/free-solid-svg-icons', // Font, only distributed bundled with the frontend code
        '@fortawesome/fontawesome-free', // Font, only distributed bundled with the frontend code
    ],
    'MPL-1.1' => [ // https://www.gnu.org/licenses/license-list.en.html#MPL
        'ckeditor4', // Allow to be licensed under GPL-2.0-or-later https://github.com/ckeditor/ckeditor4/blob/4.22.1/LICENSE.md#software-license-agreement-for-ckeditor-422-and-below
    ],
    'MIT)' => [ // Extraction issues of Rust crates
        'encoding_rs', // (Apache-2.0 OR MIT) AND BSD-3 https://github.com/hsivonen/encoding_rs/blob/main/Cargo.toml#L7
    ],
    'Apache-2.0)' => [ // Extraction issues of Rust crates
        'unicode-ident', // (MIT OR Apache-2.0) AND Unicode-3.0 https://github.com/dtolnay/unicode-ident/blob/master/Cargo.toml#L10
    ],
    'Apache-2.0/MIT' => [  // Dual licensing (MIT or Apache-2.0) of Rust crates, extraction issues
        'leb128',
    ],
    'MIT/Apache-2.0' => [ // Dual licensing (MIT or Apache-2.0) of Rust crates, extraction issues
        'android_system_properties',
        'bitflags',
        'fallible-iterator',
        'version_check',
        'zstd-sys',
        'id-arena',
        'winapi',
        'winapi-i686-pc-windows-gnu',
        'winapi-x86_64-pc-windows-gnu',
    ],
];

const ALLOWED_LICENSES_DEV_ONLY = [
    'CC-BY-4.0' => [ // Compatible, not for code license https://www.gnu.org/licenses/license-list.en.html#ccby
        'caniuse-lite', // Not shipped in production

    ],
    'OSL-3.0' => [ // https://www.gnu.org/licenses/license-list.en.html#OSL
        'netresearch/jsonmapper', // Used by Psalm which we only use as a dev tool, no production usage https://github.com/vimeo/psalm/issues/3303
    ],
    'MPL-2.0' => [ // https://www.gnu.org/licenses/license-list.en.html#MPL-2.0
        'eslint-plugin-no-unsanitized', // Dev tool, not shipped in production builds
    ],
    'AFL-2.1' => [ // https://www.gnu.org/licenses/license-list.en.html#AcademicFreeLicense
        'json-schema', // Development tool, transitive dep of cypress, not shipped in production builds
    ],
    'CC-BY-3.0' => [ // https://www.gnu.org/licenses/license-list.en.html#AcademicFreeLicense
        'spdx-exceptions', // Development tool, transitive dep of eslint-plugin-monorepo-cop, not shipped in production builds
    ],
];

const COMPONENTS_WITH_MISSING_LICENSE_FIELD = [
    'node-gettext' => 'MIT', // https://github.com/alexanderwallin/node-gettext/blob/v3.0.0/LICENSE
    'combine-errors' => 'MIT', // https://github.com/matthewmueller/combine-errors/blob/3.0.3/Readme.md?plain=1#L63 not used see https://github.com/tus/tus-js-client/pull/811
    'mediawiki/mpdf' => 'GPL-2.0-or-later', // https://github.com/wikimedia/mediawiki-extensions-Mpdf/blob/master/extension.json#L7
    'mediawiki/tuleap-integration' => 'GPL-3.0-only', // https://github.com/wikimedia/mediawiki-extensions-TuleapWikiFarm/blob/master/extension.json#L4
    'mediawiki/tuleap-wiki-farm' => 'GPL-3.0-only', // https://github.com/wikimedia/mediawiki-extensions-Mpdf/blob/master/extension.json#L7
    'mediawiki/labeled-section-transclusion' => 'GPL-2.0-or-later', // https://github.com/wikimedia/mediawiki-extensions-LabeledSectionTransclusion/blob/master/extension.json#L7
    'tuleap-wasmtime-wrapper' => 'GPL-2.0-or-later', // Internal lib, see src/additional-packages/wasmtime-wrapper-lib/
    'tuleap-test-wasm-fixtures' => 'GPL-2.0-or-later', // Internal lib, see src/additional-packages/wasmtime-wrapper-lib/
];

$logger = new \Symfony\Component\Console\Logger\ConsoleLogger(new \Symfony\Component\Console\Output\ConsoleOutput(\Symfony\Component\Console\Output\ConsoleOutput::VERBOSITY_VERY_VERBOSE));
$args   = \Psl\Env\args();

if (! isset($args[1])) {
    $logger->error('A path to a CycloneDX SBOM must be provided');
    exit(1);
}

$path = $args[1];
if (! \Psl\Filesystem\is_file($path)) {
    $logger->error('The path to the CycloneDX SBOM does not seem to exist/be a file');
    exit(1);
}

$logger->info('Processing ' . $path);

// phpcs:ignore SlevomatCodingStandard.PHP.ForbiddenClasses.ForbiddenClass
$mapper_builder = new \CuyZ\Valinor\MapperBuilder();
$mapper         = $mapper_builder->allowSuperfluousKeys()->allowPermissiveTypes()->allowUndefinedValues()->mapper();
try {
    $sbom = $mapper->map(SBOM::class, new \CuyZ\Valinor\Mapper\Source\JsonSource(\Psl\File\read($path)));
} catch (MappingError $error) {
    $logger->error($error->getMessage());
    foreach ($error->messages() as $message) {
        $logger->error($message->toString());
    }
    exit(1);
}

$violations = [];

/**
 * @return list<string>
 */
function getLicenseNames(SBOMComponentLicenseID|SBOMComponentLicenseName|SBOMComponentLicenseExpression $license): array
{
    if (isset($license->id)) {
        return [$license->id];
    }
    if (isset($license->name)) {
        return [$license->name];
    }

    $licenses = [];
    foreach (\Psl\Regex\split(\Psl\Str\strip_suffix(\Psl\Str\strip_prefix($license->expression, '('), ')'), '/(OR)|(AND)/') as $name) {
        $licenses[] = \Psl\Str\trim($name);
    }
    return $licenses;
}

function isDevComponent(SBOMComponent $component): bool
{
    if (
        array_any(
            $component->properties ?? [],
            fn($property) => $property->name === 'cdx:composer:package:isDevRequirement' && $property->value === 'true'
        )
    ) {
        return true;
    }
    return $component->scope === 'optional' && \Psl\Str\starts_with($component->purl, 'pkg:npm/');
}

foreach ($sbom->components as $component) {
    $licences            = [...($component->licenses ?? []), ...($component->evidence->licenses ?? [])];
    $component_full_name = \Psl\Str\trim($component->group . '/' . $component->name, '/');
    if (isset(COMPONENTS_WITH_MISSING_LICENSE_FIELD[$component_full_name])) {
        if (count($licences) !== 0) {
            $violations[] = sprintf('License information unexpectedly found for %s, please update COMPONENTS_WITH_MISSING_LICENSE_FIELD', $component->purl);
            continue;
        }
        $licences = [
            new SBOMComponentLicense(
                new SBOMComponentLicenseID(COMPONENTS_WITH_MISSING_LICENSE_FIELD[$component_full_name])
            ),
        ];
    }
    if (count($licences) === 0 && $component->licenses === null && $component->evidence?->licenses === null) {
        $violations[] = sprintf('No license information for package (field missing): %s', $component->purl);
        continue;
    }
    if (count($licences) === 0) {
        $violations[] = sprintf('No license information for package (field not populated): %s', $component->purl);
        continue;
    }

    $is_a_dev_component = isDevComponent($component);

    foreach ($licences as $license) {
        $names = getLicenseNames($license->license);
        foreach ($names as $name) {
            if (
                ! (
                in_array($name, ALLOWED_LICENCES) ||
                (in_array($component_full_name, ALLOWED_LICENCES_SPECIFIC_USE_CASES[$name] ?? [])) ||
                ($is_a_dev_component && in_array($component_full_name, ALLOWED_LICENSES_DEV_ONLY[$name] ?? []))
                )
            ) {
                $violations[] = sprintf('Not allowed license: %s in package %s', $name, $component->purl);
            }
        }
    }
}


if (count($violations) === 0) {
    exit(0);
}

foreach ($violations as $violation) {
    $logger->error($violation);
}
exit(1);
