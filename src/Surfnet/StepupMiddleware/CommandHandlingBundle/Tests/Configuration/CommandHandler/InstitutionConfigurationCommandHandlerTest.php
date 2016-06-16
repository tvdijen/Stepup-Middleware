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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Configuration\CommandHandler;

use Broadway\CommandHandling\CommandHandlerInterface;
use Broadway\EventHandling\EventBusInterface;
use Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory;
use Broadway\EventStore\EventStoreInterface;
use Surfnet\Stepup\Configuration\Event\NewInstitutionConfigurationCreatedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationAddedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationContactInformationChangedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRelocatedEvent;
use Surfnet\Stepup\Configuration\Event\RaLocationRenamedEvent;
use Surfnet\Stepup\Configuration\EventSourcing\InstitutionConfigurationRepository;
use Surfnet\Stepup\Configuration\Value\Institution;
use Surfnet\Stepup\Configuration\Value\InstitutionConfigurationId;
use Surfnet\Stepup\Configuration\Value\RaLocationId;
use Surfnet\Stepup\Configuration\Value\RaLocationName;
use Surfnet\Stepup\Configuration\Value\ContactInformation;
use Surfnet\Stepup\Configuration\Value\Location;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\AddRaLocationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\Command\ChangeRaLocationCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Configuration\CommandHandler\InstitutionConfigurationCommandHandler;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\CommandHandlerTest;

class InstitutionConfigurationCommandHandlerTest extends CommandHandlerTest
{
    /**
     * @test
     * @group command-handler
     */
    public function an_ra_location_can_be_added_to_an_existing_institution_configuration()
    {
        $command                     = new AddRaLocationCommand();
        $command->raLocationId       = self::uuid();
        $command->institution        = 'An institution';
        $command->raLocationName     = 'An RA location name';
        $command->location           = 'A location';
        $command->contactInformation = 'Some contact information';

        $institution                = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::from($institution);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution
                ),
            ])
            ->when($command)
            ->then([
                new RaLocationAddedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($command->raLocationId),
                    new RaLocationName($command->raLocationName),
                    new Location($command->location),
                    new ContactInformation($command->contactInformation)
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function an_ra_location_is_added_to_a_newly_created_an_institution_configuration_is_created_when_there_is_none()
    {
        $command                     = new AddRaLocationCommand();
        $command->raLocationId       = self::uuid();
        $command->institution        = 'An institution';
        $command->raLocationName     = 'An RA location name';
        $command->location           = 'A location';
        $command->contactInformation = 'Some contact information';

        $institution                = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::from($institution);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->when($command)
            ->then([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution
                ),
                new RaLocationAddedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($command->raLocationId),
                    new RaLocationName($command->raLocationName),
                    new Location($command->location),
                    new ContactInformation($command->contactInformation)
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function the_same_ra_location_cannot_be_added_twice()
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\DomainException', 'already present');

        $command                     = new AddRaLocationCommand();
        $command->raLocationId       = self::uuid();
        $command->institution        = 'An institution';
        $command->raLocationName     = 'An RA location name';
        $command->location           = 'A location';
        $command->contactInformation = 'Some contact information';

        $institution                = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::from($institution);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution
                ),
                new RaLocationAddedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($command->raLocationId),
                    new RaLocationName($command->raLocationName),
                    new Location($command->location),
                    new ContactInformation($command->contactInformation)
                )
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     */
    public function an_ra_location_can_be_renamed()
    {
        $originalRaLocationName = new RaLocationName('An old RA location name');

        $command                     = new ChangeRaLocationCommand();
        $command->raLocationId       = self::uuid();
        $command->institution        = 'An institution';
        $command->raLocationName     = 'An RA location name';
        $command->location           = 'A location';
        $command->contactInformation = 'Some contact information';

        $institution                = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::from($institution);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution
                ),
                new RaLocationAddedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($command->raLocationId),
                    $originalRaLocationName,
                    new Location($command->location),
                    new ContactInformation($command->contactInformation)
                )
            ])
            ->when($command)
            ->then([
                new RaLocationRenamedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($command->raLocationId),
                    new RaLocationName($command->raLocationName)
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function an_ra_location_cannot_be_changed_if_it_is_not_present_within_an_institution_configuration()
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\DomainException', 'not present');

        $command                     = new ChangeRaLocationCommand();
        $command->raLocationId       = self::uuid();
        $command->institution        = 'An institution';
        $command->raLocationName     = 'An RA location name';
        $command->location           = 'A location';
        $command->contactInformation = 'Some contact information';

        $institution                = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::from($institution);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution
                )
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     */
    public function an_ra_location_cannot_be_changed_if_its_institution_configuration_cannot_be_found()
    {
        $this->setExpectedException(
            'Surfnet\StepupMiddleware\CommandHandlingBundle\Exception\InstitutionConfigurationNotFoundException',
            'not found'
        );

        $command                     = new ChangeRaLocationCommand();
        $command->raLocationId       = self::uuid();
        $command->institution        = 'An institution';
        $command->raLocationName     = 'An RA location name';
        $command->location           = 'A location';
        $command->contactInformation = 'Some contact information';

        $institution                = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::from($institution);

        $this->scenario
            ->withAggregateId(self::uuid())
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution
                )
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     * @group institution-configuration
     */
    public function an_ra_location_can_be_relocated()
    {
        $originalLocation= new Location('An old location');

        $command                     = new ChangeRaLocationCommand();
        $command->raLocationId       = self::uuid();
        $command->institution        = 'An institution';
        $command->raLocationName     = 'An RA location name';
        $command->location           = 'A location';
        $command->contactInformation = 'Some contact information';

        $institution                = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::from($institution);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution
                ),
                new RaLocationAddedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($command->raLocationId),
                    new RaLocationName($command->raLocationName),
                    $originalLocation,
                    new ContactInformation($command->contactInformation)
                )
            ])
            ->when($command)
            ->then([
                new RaLocationRelocatedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($command->raLocationId),
                    new Location($command->location)
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     * @group institution-configuration
     */
    public function an_ra_locations_contact_information_can_be_changed()
    {
        $originalContactInformation= new ContactInformation('Old contact information');

        $command                     = new ChangeRaLocationCommand();
        $command->raLocationId       = self::uuid();
        $command->institution        = 'An institution';
        $command->raLocationName     = 'An RA location name';
        $command->location           = 'A location';
        $command->contactInformation = 'Some contact information';

        $institution                = new Institution($command->institution);
        $institutionConfigurationId = InstitutionConfigurationId::from($institution);

        $this->scenario
            ->withAggregateId($institutionConfigurationId)
            ->given([
                new NewInstitutionConfigurationCreatedEvent(
                    $institutionConfigurationId,
                    $institution
                ),
                new RaLocationAddedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($command->raLocationId),
                    new RaLocationName($command->raLocationName),
                    new Location($command->location),
                    $originalContactInformation
                )
            ])
            ->when($command)
            ->then([
                new RaLocationContactInformationChangedEvent(
                    $institutionConfigurationId,
                    new RaLocationId($command->raLocationId),
                    new ContactInformation($command->contactInformation)
                )
            ]);
    }

    /**
     * Create a command handler for the given scenario test case.
     *
     * @param EventStoreInterface $eventStore
     * @param EventBusInterface $eventBus
     *
     * @return CommandHandlerInterface
     */
    protected function createCommandHandler(EventStoreInterface $eventStore, EventBusInterface $eventBus)
    {
        $aggregateFactory = new PublicConstructorAggregateFactory();

        return new InstitutionConfigurationCommandHandler(
            new InstitutionConfigurationRepository($eventStore, $eventBus, $aggregateFactory)
        );
    }
}
