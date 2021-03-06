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

namespace Surfnet\Stepup\Tests\Identity\Value;

use PHPUnit_Framework_TestCase as UnitTest;
use Surfnet\Stepup\Identity\Value\DocumentNumber;

class DocumentNumberTest extends UnitTest
{
    /**
     * @test
     * @group        domain
     * @dataProvider invalidArgumentProvider
     * @expectedException \Surfnet\Stepup\Exception\InvalidArgumentException
     *
     * @param mixed $invalidValue
     */
    public function the_document_number_must_be_a_non_empty_string($invalidValue)
    {
        new DocumentNumber($invalidValue);
    }

    /**
     * @test
     * @group domain
     */
    public function two_document_numbers_with_the_same_value_are_equal()
    {
        $commonName = new DocumentNumber('John Doe');
        $theSame    = new DocumentNumber('John Doe');
        $different  = new DocumentNumber('Jane Doe');
        $unknown    = DocumentNumber::unknown();

        $this->assertTrue($commonName->equals($theSame));
        $this->assertFalse($commonName->equals($different));
        $this->assertFalse($commonName->equals($unknown));
    }

    /**
     * provider for {@see the_document_number_address_must_be_a_non_empty_string()}
     */
    public function invalidArgumentProvider()
    {
        return [
            'empty string' => [''],
            'array'        => [[]],
            'integer'      => [1],
            'float'        => [1.2],
            'object'       => [new \StdClass()],
        ];
    }
}
