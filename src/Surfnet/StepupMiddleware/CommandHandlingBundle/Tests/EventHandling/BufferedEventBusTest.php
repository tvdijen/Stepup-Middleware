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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\EventHandling;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Mockery as m;
use Surfnet\StepupMiddleware\CommandHandlingBundle\EventHandling\BufferedEventBus;

class BufferedEventBusTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @group event-handling
     */
    public function it_buffers_events()
    {
        $event = $this->createDummyDomainMessage();
        $listener = m::mock('Broadway\EventHandling\EventListenerInterface')
            ->shouldReceive('handle')->never()
            ->getMock();

        $bus = new BufferedEventBus();
        $bus->subscribe($listener);

        // Currently buses typehint against the concrete DomainEventStream.
        $bus->publish(new DomainEventStream([$event]));
    }

    /**
     * @test
     * @group event-handling
     */
    public function it_flushes_events()
    {
        $event = $this->createDummyDomainMessage();
        $listener = m::mock('Broadway\EventHandling\EventListenerInterface')
            ->shouldReceive('handle')->once()->with($event)
            ->getMock();

        $bus = new BufferedEventBus();
        $bus->subscribe($listener);

        // Currently buses typehint against the concrete DomainEventStream.
        $bus->publish(new DomainEventStream([$event]));
        $bus->flush();
    }

    /**
     * This is tested by checking that the mock event listener is invoked once, while flushing twice.
     * @test
     * @group event-handling
     */
    public function flushing_succesfully_empties_the_buffer_to_prevent_flushing_the_same_event_twice()
    {
        $event    = $this->createDummyDomainMessage();
        $listener = m::mock('Broadway\EventHandling\EventListenerInterface')
            ->shouldReceive('handle')->once()->with($event)
            ->getMock();

        $bus = new BufferedEventBus();
        $bus->subscribe($listener);

        $bus->publish(new DomainEventStream([$event]));
        $bus->flush();
        $bus->flush();
    }

    /**
     * @test
     * @group event-handling
     */
    public function an_event_caused_by_an_event_in_the_current_buffer_being_flushed_is_buffered_and_flushed_after_events_in_the_current_buffer()
    {
        $bus = new BufferedEventBus();

        $firstEventInCurrentBuffer = $this->createDummyDomainMessage(null, 'First event in current buffer');
        $eventCausedByFirstEvent = $this->createDummyDomainMessage(
            null,
            'Event caused by first event in current buffer'
        );
        $secondEventInCurrentBuffer = $this->createDummyDomainMessage(null, 'Second event in current buffer');

        $expectedEventSequence = [$firstEventInCurrentBuffer, $secondEventInCurrentBuffer, $eventCausedByFirstEvent];

        $listener = new RecordEventsAndPublishToBusOnFirstCallEventListener(
            $bus,
            new DomainEventStream([$eventCausedByFirstEvent])
        );

        $bus->subscribe($listener);
        $bus->publish(new DomainEventStream([$firstEventInCurrentBuffer, $secondEventInCurrentBuffer]));
        $bus->flush();

        $actualEventSequence = $listener->getRecordedEvents();

        $this->assertEquals($expectedEventSequence, $actualEventSequence);
    }

    /**
     * @param string $id
     * @param null $payload
     * @return DomainMessage
     */
    private function createDummyDomainMessage($id = '1', $payload = null)
    {
        return new DomainMessage($id, 0, new Metadata(), $payload, DateTime::fromString('1970-01-01H00:00:00.000'));
    }
}

