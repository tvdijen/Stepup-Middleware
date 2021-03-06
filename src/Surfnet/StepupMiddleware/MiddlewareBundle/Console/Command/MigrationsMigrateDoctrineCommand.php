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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationsMigrateDoctrineCommand extends Command
{
    protected function configure()
    {
        $this->setName('middleware:migrations:migrate');
        $this->setDescription('Performs database migrations using the correct entity manager');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getApplication()->doRun(new StringInput('doc:mig:mig --em=deploy'), $output);
    }
}
