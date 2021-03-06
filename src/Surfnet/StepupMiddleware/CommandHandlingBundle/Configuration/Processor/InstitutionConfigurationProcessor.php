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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Processor;

use Broadway\Processor\Processor;
use Rhumsaa\Uuid\Uuid;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\InstitutionsAddedToWhitelistEvent;
use Surfnet\Stepup\Identity\Event\WhitelistCreatedEvent;
use Surfnet\Stepup\Identity\Event\WhitelistReplacedEvent;
use Surfnet\StepupMiddleware\ApiBundle\Configuration\Repository\ConfiguredInstitutionRepository;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\CreateInstitutionConfigurationCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class InstitutionConfigurationProcessor extends Processor
{
    /**
     * @var ConfiguredInstitutionRepository
     */
    private $configuredInstitutionRepository;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * The container needs to be called during runtime in order to prevent a circular reference
     * during container compilation.
     *
     * @param ConfiguredInstitutionRepository $configuredInstitutionRepository
     * @param ContainerInterface $container
     */
    public function __construct(
        ConfiguredInstitutionRepository $configuredInstitutionRepository,
        ContainerInterface $container
    ) {
        $this->configuredInstitutionRepository = $configuredInstitutionRepository;
        $this->container                       = $container;
    }

    public function handleIdentityCreatedEvent(IdentityCreatedEvent $event)
    {
        $institution = new Institution($event->identityInstitution->getInstitution());

        if ($this->configuredInstitutionRepository->hasConfigurationFor($institution)) {
            return;
        }

        $this->createConfigurationFor($institution);
    }

    public function handleWhitelistCreatedEvent(WhitelistCreatedEvent $event)
    {
        foreach ($event->whitelistedInstitutions as $whitelistedInstitution) {
            $institution = new Institution($whitelistedInstitution->getInstitution());

            if ($this->configuredInstitutionRepository->hasConfigurationFor($institution)) {
                continue;
            }

            $this->createConfigurationFor($institution);
        }
    }

    public function handleWhitelistReplacedEvent(WhitelistReplacedEvent $event)
    {
        foreach ($event->whitelistedInstitutions as $whitelistedInstitution) {
            $institution = new Institution($whitelistedInstitution->getInstitution());

            if ($this->configuredInstitutionRepository->hasConfigurationFor($institution)) {
                continue;
            }

            $this->createConfigurationFor($institution);
        }
    }

    public function handleInstitutionsAddedToWhitelistEvent(InstitutionsAddedToWhitelistEvent $event)
    {
        foreach ($event->addedInstitutions as $addedInstitution) {
            $institution = new Institution($addedInstitution->getInstitution());

            if ($this->configuredInstitutionRepository->hasConfigurationFor($institution)) {
                continue;
            }

            $this->createConfigurationFor($institution);
        }
    }

    /**
     * @param Institution $institution
     */
    private function createConfigurationFor(Institution $institution)
    {
        $command              = new CreateInstitutionConfigurationCommand();
        $command->UUID        = (string) Uuid::uuid4();
        $command->institution = $institution->getInstitution();

        $this->container->get('pipeline')->process($command);
    }
}
