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

namespace Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\Identity\CommandHandler;

use Broadway\EventHandling\EventBusInterface;
use Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory;
use Broadway\EventStore\EventStoreInterface;
use DateTime as CoreDateTime;
use Mockery as m;
use Mockery\MockInterface;
use Surfnet\Stepup\DateTime\DateTime;
use Surfnet\Stepup\Identity\Entity\ConfigurableSettings;
use Surfnet\Stepup\Identity\Event\EmailVerifiedEvent;
use Surfnet\Stepup\Identity\Event\GssfPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\IdentityCreatedEvent;
use Surfnet\Stepup\Identity\Event\IdentityEmailChangedEvent;
use Surfnet\Stepup\Identity\Event\IdentityRenamedEvent;
use Surfnet\Stepup\Identity\Event\PhonePossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\UnverifiedSecondFactorRevokedEvent;
use Surfnet\Stepup\Identity\Event\YubikeyPossessionProvenEvent;
use Surfnet\Stepup\Identity\Event\YubikeySecondFactorBootstrappedEvent;
use Surfnet\Stepup\Identity\EventSourcing\IdentityRepository;
use Surfnet\Stepup\Identity\Value\EmailVerificationWindow;
use Surfnet\Stepup\Identity\Value\GssfId;
use Surfnet\Stepup\Identity\Value\IdentityId;
use Surfnet\Stepup\Identity\Value\Institution;
use Surfnet\Stepup\Identity\Value\NameId;
use Surfnet\Stepup\Identity\Value\PhoneNumber;
use Surfnet\Stepup\Identity\Value\SecondFactorId;
use Surfnet\Stepup\Identity\Value\StepupProvider;
use Surfnet\Stepup\Identity\Value\TimeFrame;
use Surfnet\Stepup\Identity\Value\YubikeyPublicId;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\BootstrapIdentityWithYubikeySecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\CreateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveGssfPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProvePhonePossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\ProveYubikeyPossessionCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\RevokeOwnSecondFactorCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\UpdateIdentityCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\VerifyEmailCommand;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\CommandHandler\IdentityCommandHandler;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Tests\DateTimeHelper;

/**
 * @runTestsInSeparateProcesses
 */
class IdentityCommandHandlerTest extends CommandHandlerTest
{
    private static $window = 3600;

    protected function createCommandHandler(EventStoreInterface $eventStore, EventBusInterface $eventBus)
    {
        $aggregateFactory = new PublicConstructorAggregateFactory();

        return new IdentityCommandHandler(
            new IdentityRepository($eventStore, $eventBus, $aggregateFactory),
            ConfigurableSettings::create(self::$window)
        );
    }

    /**
     * @test
     * @group command-handler
     */
    public function an_identity_can_be_bootstrapped_with_a_yubikey_second_factor()
    {
        $command = new BootstrapIdentityWithYubikeySecondFactorCommand();
        $command->identityId = 'ID-ID';
        $command->nameId = 'N-ID';
        $command->institution = 'Institution';
        $command->commonName = 'Enrique';
        $command->email = 'foo@bar.baz';
        $command->secondFactorId = 'SF-ID';
        $command->yubikeyPublicId = 'Y-ID';

        $this->scenario
            ->withAggregateId('ID-ID')
            ->when($command)
            ->then([
                new IdentityCreatedEvent(
                    new IdentityId('ID-ID'),
                    new Institution('Institution'),
                    new NameId('N-ID'),
                    'foo@bar.baz',
                    'Enrique'
                ),
                new YubikeySecondFactorBootstrappedEvent(
                    new IdentityId('ID-ID'),
                    new NameId('N-ID'),
                    new Institution('Institution'),
                    new SecondFactorId('SF-ID'),
                    new YubikeyPublicId('Y-ID')
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     * @runInSeparateProcess
     */
    public function a_yubikey_possession_can_be_proven()
    {
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@12345')));

        m::mock('alias:Surfnet\Stepup\Token\TokenGenerator')
            ->shouldReceive('generateHumanReadableToken')->once()->andReturn('code')
            ->shouldReceive('generateNonce')->once()->andReturn('nonce');

        $id = new IdentityId(self::uuid());
        $nameId = new NameId(md5(__METHOD__));
        $institution = new Institution('A Corp.');
        $email = 'a@b.c';
        $commonName = 'Foo bar';
        $secFacId = new SecondFactorId(self::uuid());
        $pubId = new YubikeyPublicId('ccccvfeghijk');

        $command = new ProveYubikeyPossessionCommand();
        $command->identityId = (string) $id;
        $command->secondFactorId = (string) $secFacId;
        $command->yubikeyPublicId = (string) $pubId;

        $this->scenario
            ->withAggregateId($id)
            ->given([new IdentityCreatedEvent($id, $institution, $nameId, $email, $commonName)])
            ->when($command)
            ->then([
                new YubikeyPossessionProvenEvent(
                    $id,
                    $secFacId,
                    $pubId,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(TimeFrame::ofSeconds(static::$window), DateTime::now()),
                    'nonce',
                    'Foo bar',
                    'a@b.c',
                    'en_GB'
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function yubikey_possession_cannot_be_proven_twice()
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\DomainException', 'more than one token');

        $id = new IdentityId(self::uuid());
        $nameId = new NameId(md5(__METHOD__));
        $institution = new Institution('A Corp.');
        $email = 'a@b.c';
        $commonName = 'Foo bar';
        $secFacId1 = new SecondFactorId(self::uuid());
        $pubId1 = new YubikeyPublicId('ccccvfeghijk');

        $command = new ProveYubikeyPossessionCommand();
        $command->identityId = (string) $id;
        $command->secondFactorId = (string) $secFacId1;
        $command->yubikeyPublicId = (string) $pubId1;

        $this->scenario
            ->withAggregateId($id)
            ->given([
                new IdentityCreatedEvent($id, $institution, $nameId, $email, $commonName),
                new YubikeyPossessionProvenEvent(
                    $id,
                    $secFacId1,
                    $pubId1,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    'nonce',
                    'Foo bar',
                    'a@b.c',
                    'en_GB'
                )
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     * @runInSeparateProcess
     */
    public function a_phone_possession_can_be_proven()
    {
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@12345')));

        m::mock('alias:Surfnet\Stepup\Token\TokenGenerator')
            ->shouldReceive('generateHumanReadableToken')->once()->andReturn('code')
            ->shouldReceive('generateNonce')->once()->andReturn('nonce');

        $id = new IdentityId(self::uuid());
        $nameId = new NameId(md5(__METHOD__));
        $institution = new Institution('A Corp.');
        $email = 'a@b.c';
        $commonName = 'Foo bar';
        $secFacId = new SecondFactorId(self::uuid());
        $pubId = new PhoneNumber('31612345678');

        $command = new ProvePhonePossessionCommand();
        $command->identityId = (string) $id;
        $command->secondFactorId = (string) $secFacId;
        $command->phoneNumber = (string) $pubId;

        $this->scenario
            ->withAggregateId($id)
            ->given([new IdentityCreatedEvent($id, $institution, $nameId, $email, $commonName)])
            ->when($command)
            ->then([
                new PhonePossessionProvenEvent(
                    $id,
                    $secFacId,
                    $pubId,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    'nonce',
                    'Foo bar',
                    'a@b.c',
                    'en_GB'
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     * @runInSeparateProcess
     */
    public function a_gssf_possession_can_be_proven()
    {
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@12345')));

        $nonce = 'nonce';
        m::mock('alias:Surfnet\Stepup\Token\TokenGenerator')
            ->shouldReceive('generateHumanReadableToken')->once()->andReturn('code')
            ->shouldReceive('generateNonce')->once()->andReturn($nonce);

        $identityId     = new IdentityId(self::uuid());
        $nameId         = new NameId(md5(__METHOD__));
        $institution    = new Institution('Surfnet');
        $email          = 'arthur@example.org';
        $commonName     = 'Arthur Dent';
        $secondFactorId = new SecondFactorId(self::uuid());
        $stepupProvider = new StepupProvider('Surfnet');
        $gssfId         = new GssfId('_' . md5('Surfnet'));

        $command                 = new ProveGssfPossessionCommand();
        $command->identityId     = (string) $identityId;
        $command->secondFactorId = (string) $secondFactorId;
        $command->stepupProvider = (string) $stepupProvider;
        $command->gssfId         = (string) $gssfId;

        $this->scenario
            ->withAggregateId($identityId)
            ->given([new IdentityCreatedEvent($identityId, $institution, $nameId, $email, $commonName)])
            ->when($command)
            ->then([
                new GssfPossessionProvenEvent(
                    $identityId,
                    $secondFactorId,
                    $stepupProvider,
                    $gssfId,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    $nonce,
                    $commonName,
                    $email,
                    'en_GB'
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function phone_possession_cannot_be_proven_twice()
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\DomainException', 'more than one token');

        $id = new IdentityId(self::uuid());
        $nameId = new NameId(md5(__METHOD__));
        $institution = new Institution('A Corp.');
        $email = 'a@b.c';
        $commonName = 'Foo bar';
        $secFacId1 = new SecondFactorId(self::uuid());
        $phoneNumber1 = new PhoneNumber('31612345678');

        $command = new ProvePhonePossessionCommand();
        $command->identityId = (string) $id;
        $command->secondFactorId = (string) $secFacId1;
        $command->phoneNumber = (string) $phoneNumber1;

        $this->scenario
            ->withAggregateId($id)
            ->given([
                new IdentityCreatedEvent($id, $institution, $nameId, $email, $commonName),
                new PhonePossessionProvenEvent(
                    $id,
                    $secFacId1,
                    $phoneNumber1,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    'nonce',
                    'Foo bar',
                    'a@b.c',
                    'en_GB'
                )
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     */
    public function cannot_prove_possession_of_arbitrary_second_factor_type_twice()
    {
        $this->setExpectedException('Surfnet\Stepup\Exception\DomainException', 'more than one token');

        $id = new IdentityId(self::uuid());
        $nameId = new NameId(md5(__METHOD__));
        $institution = new Institution('A Corp.');
        $email = 'a@b.c';
        $commonName = 'Foo bar';
        $secFacId1 = new SecondFactorId(self::uuid());
        $publicId = new YubikeyPublicId('ccccvfeghijk');
        $phoneNumber = new PhoneNumber('31676543210');

        $command = new ProvePhonePossessionCommand();
        $command->identityId = (string) $id;
        $command->secondFactorId = (string) $secFacId1;
        $command->phoneNumber = (string) $phoneNumber;

        $this->scenario
            ->withAggregateId($id)
            ->given([
                new IdentityCreatedEvent($id, $institution, $nameId, $email, $commonName),
                new YubikeyPossessionProvenEvent(
                    $id,
                    $secFacId1,
                    $publicId,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    'nonce',
                    'Foo bar',
                    'a@b.c',
                    'en_GB'
                )
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     * @runInSeparateProcess
     */
    public function an_unverified_second_factors_email_can_be_verified()
    {
        DateTimeHelper::setCurrentTime(new DateTime(new CoreDateTime('@12345')));

        m::mock('alias:Surfnet\Stepup\Token\TokenGenerator')
            ->shouldReceive('generateHumanReadableToken')->once()->andReturn('regcode');

        $id = new IdentityId(self::uuid());
        $nameId = new NameId(md5(__METHOD__));
        $institution = new Institution('A Corp.');
        $email = 'a@b.c';
        $commonName = 'Foo bar';
        $secondFactorId = new SecondFactorId(self::uuid());
        $publicId = new YubikeyPublicId('ccccvfeghijk');

        $command = new VerifyEmailCommand();
        $command->identityId = (string) $id;
        $command->verificationNonce = 'nonce';

        $this->scenario
            ->withAggregateId($id)
            ->given([
                new IdentityCreatedEvent($id, $institution, $nameId, $email, $commonName),
                new YubikeyPossessionProvenEvent(
                    $id,
                    $secondFactorId,
                    $publicId,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    'nonce',
                    'Foo bar',
                    'a@b.c',
                    'en_GB'
                )
            ])
            ->when($command)
            ->then([
                new EmailVerifiedEvent(
                    $id,
                    $institution,
                    $secondFactorId,
                    DateTime::now(),
                    'regcode',
                    $commonName,
                    $email,
                    'en_GB'
                )
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function a_verified_second_factors_email_cannot_be_verified()
    {
        $this->setExpectedException(
            'Surfnet\Stepup\Exception\DomainException',
            'Cannot verify second factor, no unverified second factor can be verified using the given nonce'
        );

        $id = new IdentityId(self::uuid());
        $nameId = new NameId(md5(__METHOD__));
        $institution = new Institution('A Corp.');
        $email = 'a@b.c';
        $commonName = 'Foo bar';
        $secondFactorId = new SecondFactorId(self::uuid());
        $publicId = new YubikeyPublicId('ccccvfeghijk');

        $command = new VerifyEmailCommand();
        $command->identityId = (string) $id;
        $command->verificationNonce = 'nonce';

        $this->scenario
            ->withAggregateId($id)
            ->given([
                new IdentityCreatedEvent($id, $institution, $nameId, $email, $commonName),
                new YubikeyPossessionProvenEvent(
                    $id,
                    $secondFactorId,
                    $publicId,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    'nonce',
                    'Foo bar',
                    'a@b.c',
                    'en_GB'
                ),
                new EmailVerifiedEvent(
                    $id,
                    $institution,
                    $secondFactorId,
                    DateTime::now(),
                    'regcode',
                    $commonName,
                    $email,
                    'en_GB'
                )
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     */
    public function cannot_verify_an_email_after_the_verification_window_has_closed()
    {
        $this->setExpectedException(
            'Surfnet\Stepup\Exception\DomainException',
            'Cannot verify second factor, the verification window is closed.'
        );

        $id = new IdentityId(self::uuid());
        $secondFactorId = new SecondFactorId(self::uuid());
        $publicId = new YubikeyPublicId('ccccvfeghijk');
        $institution = new Institution('A Corp.');
        $nameId = new NameId(md5(__METHOD__));
        $email = 'a@b.c';
        $commonName = 'Foo bar';

        $command = new VerifyEmailCommand();
        $command->identityId = (string) $id;
        $command->verificationNonce = 'nonce';

        $this->scenario
            ->withAggregateId($id)
            ->given([
                new IdentityCreatedEvent($id, $institution, $nameId, $email, $commonName),
                new YubikeyPossessionProvenEvent(
                    $id,
                    $secondFactorId,
                    $publicId,
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        new DateTime(new CoreDateTime('-2 days'))
                    ),
                    'nonce',
                    'Foo bar',
                    'a@b.c',
                    'en_GB'
                )
            ])
            ->when($command);
    }

    /**
     * @test
     * @group command-handler
     */
    public function it_can_create_a_new_identity()
    {
        $createCommand = new CreateIdentityCommand();
        $createCommand->UUID = '1';
        $createCommand->id = '2';
        $createCommand->institution = 'A Corp.';
        $createCommand->nameId = '3';
        $createCommand->email = 'a@b.c';
        $createCommand->commonName = 'foobar';

        $createdEvent = new IdentityCreatedEvent(
            new IdentityId('2'),
            new Institution('A Corp.'),
            new NameId('3'),
            'a@b.c',
            'foobar'
        );

        $this->scenario
            ->given([])
            ->when($createCommand)
            ->then([$createdEvent]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function an_identity_can_be_updated()
    {
        $id = new IdentityId('42');
        $createdEvent = new IdentityCreatedEvent(
            $id,
            new Institution('A Corp.'),
            new NameId('3'),
            'a@b.c',
            'foobar'
        );

        $updateCommand = new UpdateIdentityCommand();
        $updateCommand->id = $id;
        $updateCommand->email = 'new@email.com';
        $updateCommand->commonName = 'Henk';

        $this->scenario
            ->withAggregateId($id)
            ->given([$createdEvent])
            ->when($updateCommand)
            ->then([
                new IdentityRenamedEvent($id, 'foobar', 'Henk'),
                new IdentityEmailChangedEvent($id, 'a@b.c', 'new@email.com')
            ]);
    }

    /**
     * @test
     * @group command-handler
     */
    public function an_unverified_second_factor_can_be_revoked()
    {
        $command = new RevokeOwnSecondFactorCommand();
        $command->identityId = '42';
        $command->secondFactorId = self::uuid();

        $this->scenario
            ->withAggregateId($id = new IdentityId($command->identityId))
            ->given([
                new IdentityCreatedEvent(
                    $id,
                    new Institution('A Corp.'),
                    new NameId('3'),
                    'a@b.c',
                    'foobar'
                ),
                new YubikeyPossessionProvenEvent(
                    $id,
                    $secFacId = new SecondFactorId($command->secondFactorId),
                    $pubId = new YubikeyPublicId('ccccvfeghijk'),
                    EmailVerificationWindow::createFromTimeFrameStartingAt(
                        TimeFrame::ofSeconds(static::$window),
                        DateTime::now()
                    ),
                    'nonce',
                    'Foo bar',
                    'a@b.c',
                    'en_GB'
                )
            ])
            ->when($command)
            ->then([
                new UnverifiedSecondFactorRevokedEvent($id, $secFacId)
            ]);
    }
}
