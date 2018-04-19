<?php

/**
 * Copyright 2018 SURFnet B.V.
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

namespace Surfnet\Stepup\Configuration\Event;

use Broadway\Serializer\SerializableInterface;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\NumberOfTokensPerIdentityOption;

final class NumberOfTokensPerIdentityOptionChangedEvent implements SerializableInterface
{
    /**
     * @var InstitutionConfigurationId
     */
    public $institutionConfigurationId;

    /**
     * @var Institution
     */
    public $institution;

    /**
     * @var NumberOfTokensPerIdentityOption
     */
    public $numberOfTokensPerIdentityOption;

    public function __construct(
        InstitutionConfigurationId $institutionConfigurationId,
        Institution $institution,
        NumberOfTokensPerIdentityOption $numberOfTokensPerIdentityOption
    ) {
        $this->institutionConfigurationId = $institutionConfigurationId;
        $this->institution = $institution;
        $this->numberOfTokensPerIdentityOption = $numberOfTokensPerIdentityOption;
    }

    public static function deserialize(array $data)
    {
        return new self(
            new InstitutionConfigurationId($data['institution_configuration_id']),
            new Institution($data['institution']),
            new NumberOfTokensPerIdentityOption($data['number_of_tokens_per_identity_option'])
        );
    }

    public function serialize()
    {
        return [
            'institution_configuration_id' => $this->institutionConfigurationId->getInstitutionConfigurationId(),
            'institution'                  => $this->institution->getInstitution(),
            'number_of_tokens_per_identity_option' => $this->numberOfTokensPerIdentityOption->getNumberOfTokensPerIdentity(),
        ];
    }
}
