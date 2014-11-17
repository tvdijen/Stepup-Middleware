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

namespace Surfnet\StepupMiddleware\ApiBundle\Controller;

use Surfnet\StepupMiddleware\ApiBundle\Identity\Service\SecondFactorService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SecondFactorController extends Controller
{
    /**
     * Lists the second factors belonging to the given Identity.
     *
     * @param string $identityId
     * @return Response
     */
    public function findByIdentityAction($identityId)
    {
        if (!$this->isGranted('ROLE_RA') && !$this->isGranted('ROLE_SS')) {
            throw new AccessDeniedHttpException('Client is not authorised to access resource');
        }

        $secondFactors = $this->getService()->findByIdentity($identityId);

        return new JsonResponse($secondFactors);
    }

    /**
     * Lists the unverified second factors belonging to the given Identity.
     *
     * @param string $identityId
     * @return Response
     */
    public function findUnverifiedByIdentityAction($identityId)
    {
        if (!$this->isGranted('ROLE_RA') && !$this->isGranted('ROLE_SS')) {
            throw new AccessDeniedHttpException('Client is not authorised to access resource');
        }

        $secondFactors = $this->getService()->findUnverifiedByIdentity($identityId); // @TODO Collection structure

        return new JsonResponse($secondFactors);
    }

    /**
     * @return SecondFactorService
     */
    private function getService()
    {
        return $this->get('surfnet_stepup_middleware_api.service.second_factor');
    }
}
