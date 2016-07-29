<?php

/**
 * Copyright 2016 SURFnet B.V.
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

use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\StepupMiddleware\ApiBundle\Service\InstitutionConfigurationOptionsService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

final class InstitutionConfigurationOptionsController extends Controller
{
    public function getForInstitutionAction($institution)
    {
        $this->denyAccessUnlessGranted(['ROLE_SS', 'ROLE_RA']);

        $institutionConfigurationOptions = $this
            ->getInstitutionConfigurationOptionsService()
            ->findInstitutionConfigurationOptionsFor(new Institution($institution));


        return new JsonResponse($institutionConfigurationOptions);
    }

    /**
     * @return InstitutionConfigurationOptionsService
     */
    private function getInstitutionConfigurationOptionsService()
    {
        return $this->get('surfnet_stepup_middleware_api.service.institution_configuration_options');
    }
}
