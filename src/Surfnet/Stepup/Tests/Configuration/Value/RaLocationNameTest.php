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

namespace Surfnet\Stepup\Tests\Configuration\Value;

use PHPUnit_Framework_TestCase as TestCase;
use Surfnet\Stepup\Configuration\Value\RaLocationName;

class RaLocationNameTest extends TestCase
{
    /**
     * @test
     * @group        domain
     * @dataProvider nonStringOrEmptyStringProvider
     * @expectedException \Surfnet\Stepup\Exception\InvalidArgumentException
     *
     * @param mixed $nonStringOrEmptyString
     */
    public function an_ra_location_name_cannot_be_created_with_anything_but_a_nonempty_string($nonStringOrEmptyString)
    {
        new RaLocationName($nonStringOrEmptyString);
    }

    /**
     * @test
     * @group domain
     */
    public function two_ra_location_names_with_the_same_values_are_equal()
    {
        $raLocationName = new RaLocationName('a');
        $theSame      = new RaLocationName('a');

        $this->assertTrue($raLocationName->equals($theSame));
    }

    /**
     * @test
     * @group domain
     */
    public function two_ra_location_names_with_different_values_are_not_equal()
    {
        $raLocationName = new RaLocationName('a');
        $different    = new RaLocationName('A');

        $this->assertFalse($raLocationName->equals($different));
    }

    public function nonStringOrEmptyStringProvider()
    {
        return [
            'empty string' => [''],
            'blank string' => ['   '],
            'array'        => [[]],
            'integer'      => [1],
            'float'        => [1.2],
            'object'       => [new \StdClass()],
        ];
    }
}
