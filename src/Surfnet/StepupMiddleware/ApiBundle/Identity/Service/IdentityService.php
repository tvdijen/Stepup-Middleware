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

namespace Surfnet\StepupMiddleware\ApiBundle\Identity\Service;

use Surfnet\StepupMiddleware\ApiBundle\Identity\Entity\Identity;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Exception\EntityNotFoundException;
use Surfnet\StepupMiddleware\ApiBundle\Identity\Repository\IdentityRepository;

class IdentityService
{
    /**
     * @var IdentityRepository
     */
    private $repository;

    /**
     * @param IdentityRepository $repository
     */
    public function __construct(IdentityRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param string $id
     * @param string $instituteId
     * @return Identity
     * @throws EntityNotFoundException
     */
    public function get($id, $instituteId)
    {
        $identity = $this->repository->find($id);

        if (!$identity instanceof Identity) {
            throw new EntityNotFoundException(
                sprintf("Identity with id '%s' not found within institution '%s'", $id, $instituteId)
            );
        }

        return $identity;
    }
}
