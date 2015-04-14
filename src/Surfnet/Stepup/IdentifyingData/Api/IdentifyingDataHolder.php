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

namespace Surfnet\Stepup\IdentifyingData\Api;

use Surfnet\Stepup\IdentifyingData\Entity\IdentifyingData;
use Surfnet\Stepup\IdentifyingData\Value\IdentifyingDataId;

interface IdentifyingDataHolder
{
    /**
     * @return IdentifyingDataId UUID
     */
    public function getIdentifyingDataId();

    /**
     * @param IdentifyingData $identifyingData
     */
    public function setIdentifyingData(IdentifyingData $identifyingData);

    /**
     * @return IdentifyingData
     */
    public function exposeIdentifyingData();
}
