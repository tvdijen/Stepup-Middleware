<?php

/**
 * Copyright 2017 SURFnet B.V.
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

namespace Surfnet\StepupMiddleware\ApiBundle\Configuration\Service;

use Surfnet\Stepup\Configuration\Value\AllowedSecondFactorList;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Entity\AllowedSecondFactor;

final class AllowedSecondFactorMap
{
    /**
     * @var AllowedSecondFactor[]
     */
    private $mappedAllowedSecondFactors = [];

    private function __construct()
    {
    }

    /**
     * @param AllowedSecondFactor[] $allowedSecondFactors
     * @return AllowedSecondFactorMap
     */
    public static function from($allowedSecondFactors)
    {
        $allowedSecondFactorMap = new self();
        foreach ($allowedSecondFactors as $allowedSecondFactor) {
            $allowedSecondFactorMap->add($allowedSecondFactor);
        }

        return $allowedSecondFactorMap;
    }

    /**
     * @param Institution $institution
     * @return AllowedSecondFactorList
     */
    public function getAllowedSecondFactorListFor(Institution $institution)
    {
        $institution = strtolower($institution->getInstitution());
        if (!array_key_exists($institution, $this->mappedAllowedSecondFactors)) {
            return AllowedSecondFactorList::blank();
        }

        return AllowedSecondFactorList::ofTypes($this->mappedAllowedSecondFactors[$institution]);
    }

    /**
     * @param AllowedSecondFactor $allowedSecondFactor
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function add(AllowedSecondFactor $allowedSecondFactor)
    {
        $institution = strtolower($allowedSecondFactor->institution->getInstitution());

        $this->mappedAllowedSecondFactors[$institution][] = $allowedSecondFactor->secondFactorType;
    }
}
