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

namespace Surfnet\StepupMiddleware\MiddlewareBundle\Console\Command;

use Assert\Assertion;
use DateInterval;
use DateTime;
use InvalidArgumentException;
use Rhumsaa\Uuid\Uuid;
use Surfnet\StepupMiddleware\CommandHandlingBundle\Identity\Command\SendVerifiedSecondFactorRemindersCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;

/**
 * The EmailVerifiedSecondFactorRemindersCommand can be run to send reminders to token registrants.
 *
 * The command utilizes a specific service for this task (VerifiedSecondFactorReminderService). Input validation is
 * performed on the incoming request parameters.
 */
final class EmailVerifiedSecondFactorRemindersCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('middleware:cron:email-reminder')
            ->setDescription('Sends email reminders to identities with verified tokens more than 7 days old.')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Run in dry mode, not sending any email'
            )
            ->addOption(
                'date',
                null,
                InputOption::VALUE_OPTIONAL,
                'The date (Y-m-d) that should be used for sending reminder email messages, defaults to TODAY - 7'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Container $container */
        $container = $this->getApplication()->getKernel()->getContainer();

        $pipeline = $container->get('surfnet_stepup_middleware_command_handling.pipeline.transaction_aware_pipeline');
        $eventBus = $container->get('surfnet_stepup_middleware_command_handling.event_bus.buffered');
        $connection = $container->get('surfnet_stepup_middleware_middleware.dbal_connection_helper');
        $logger = $container->get('logger');

        try {
            $this->validateInput($input);
        } catch (InvalidArgumentException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            $logger->error(sprintf('Invalid arguments passed to the %s', $this->getName()), [$e->getMessage()]);
            return 1;
        }

        $date = new DateTime();
        $date->sub(new DateInterval('P7D'));
        if ($input->hasOption('date') && !is_null($input->getOption('date'))) {
            $date = DateTime::createFromFormat('Y-m-d', $input->getOption('date'));
        }

        $dryRun = false;
        if ($input->hasOption('dry-run') && !is_null($input->getOption('dry-run'))) {
            $dryRun = $input->getOption('dry-run');
        }

        $command = new SendVerifiedSecondFactorRemindersCommand();
        $command->requestedAt = $date;
        $command->dryRun = $dryRun;
        $command->UUID = Uuid::uuid4()->toString();

        $connection->beginTransaction();
        try {
            $pipeline->process($command);
            $eventBus->flush();

            $connection->commit();
        } catch (Exception $e) {
            $output->writeln(sprintf(
                '<error>An Error occurred while sending reminder email messages.</error>',
                $e->getMessage()
            ));
            $connection->rollBack();
            throw $e;
        }
    }

    private function validateInput(InputInterface $input)
    {
        if ($input->hasOption('date')) {
            $date = $input->getOption('date');
            Assertion::nullOrDate($date, 'Y-m-d', 'Expected date to be a string and formatted in the Y-m-d date format');
        }

        if ($input->hasOption('dry-run')) {
            $dryRun = $input->getOption('dry-run');
            Assertion::nullOrBoolean($dryRun, 'Expected dry-run parameter to be a boolean value.');
        }
    }
}
