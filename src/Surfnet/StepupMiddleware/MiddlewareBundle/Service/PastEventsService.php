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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Service;

use Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing\DBALEventHydrator;
use Surfnet\StepupMiddleware\MiddlewareBundle\EventSourcing\EventCollection;

final class PastEventsService
{
    /**
     * @var DBALEventHydrator
     */
    private $eventHydrator;

    public function __construct(DBALEventHydrator $eventHydrator)
    {
        $this->eventHydrator = $eventHydrator;
    }

    /**
     * @param EventCollection $events
     * @return \Broadway\Domain\DomainEventStream
     */
    public function findEventsBy(EventCollection $events)
    {
        return $this->eventHydrator->fetchByEventTypes($events->formatAsEventStreamTypes());
    }
}
