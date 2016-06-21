<?php

/**
 * Copyright 2014 SURFnet bv
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

return [
    'expectedPropertyPath' => 'gateway.service_providers[0].entity_id',
    'configuration' => [
        'gateway' => [
            'identity_providers' => [],
            'service_providers' => [
                [
                    "entity_id"  => 9,
                    "public_key" => "MIIE...",
                    "acs"        => ["https://entity.tld/consume-assertion"],
                    "loa"        => [
                        "__default__" => "https://entity.tld/authentication/loa2",
                    ],
                    "second_factor_only" => false,
                    "second_factor_only_nameid_patterns" => [],
                    "assertion_encryption_enabled"      => false,
                    "blacklisted_encryption_algorithms" => []
                ]
            ],
        ],
        'sraa' => ['20394-4320423-439248324'],
        'email_templates' => [
            'confirm_email'     => ['en_GB' => 'Verify {{ commonName }}'],
            'registration_code' => ['en_GB' => 'Code {{ commonName }}'],
        ],
        'institutions_with_personal_ra_details' => ['institution.test'],
    ]
];
