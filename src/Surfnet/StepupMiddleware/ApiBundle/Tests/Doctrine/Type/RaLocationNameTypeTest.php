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

namespace Surfnet\StepupMiddleware\ApiBundle\Tests\Doctrine\Type;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Types\Type;
use PHPUnit_Framework_TestCase as UnitTest;
use Surfnet\Stepup\Configuration\Value\RaLocationName;
use Surfnet\StepupMiddleware\ApiBundle\Doctrine\Type\RaLocationNameType;

class RaLocationNameTypeTest extends UnitTest
{
    /**
     * @var \Doctrine\DBAL\Platforms\MySqlPlatform
     */
    private $platform;

    /**
     * Register the type, since we're forced to use the factory method.
     */
    public static function setUpBeforeClass()
    {
        Type::addType(
            RaLocationNameType::NAME,
            'Surfnet\StepupMiddleware\ApiBundle\Doctrine\Type\RaLocationNameType'
        );
    }

    public function setUp()
    {
        $this->platform = new MySqlPlatform();
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_null_value_remains_null_in_to_sql_conversion()
    {
        $raLocationName = Type::getType(RaLocationNameType::NAME);

        $value = $raLocationName->convertToDatabaseValue(null, $this->platform);

        $this->assertNull($value);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_non_null_value_is_converted_to_the_correct_format()
    {
        $raLocationName = Type::getType(RaLocationNameType::NAME);

        $expected = 'An RA Location Name';
        $input    = new RaLocationName($expected);
        $output   = $raLocationName->convertToDatabaseValue($input, $this->platform);

        $this->assertTrue(is_string($output));
        $this->assertEquals($expected, $output);
    }

    /**
     * @test
     * @group doctrine
     *
     * @dataProvider \Surfnet\StepupMiddleware\ApiBundle\Tests\TestDataProvider::notNull
     * @param $incorrectValue
     */
    public function a_value_can_only_be_converted_to_sql_if_it_is_an_ra_location_or_null($incorrectValue)
    {
        $this->setExpectedException('Doctrine\DBAL\Types\ConversionException');

        $configurationContactInformation = Type::getType(RaLocationNameType::NAME);
        $configurationContactInformation->convertToDatabaseValue($incorrectValue, $this->platform);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_null_value_remains_null_when_converting_from_db_to_php_value()
    {
        $raLocationName = Type::getType(RaLocationNameType::NAME);

        $value = $raLocationName->convertToPHPValue(null, $this->platform);

        $this->assertNull($value);
    }

    /**
     * @test
     * @group doctrine
     */
    public function a_non_null_value_is_converted_to_a_ra_location_name_value_object()
    {
        $raLocationName = Type::getType(RaLocationNameType::NAME);

        $input = 'An RA location name';

        $output = $raLocationName->convertToPHPValue($input, $this->platform);

        $this->assertInstanceOf('Surfnet\Stepup\Configuration\Value\RaLocationName', $output);
        $this->assertEquals(new RaLocationName($input), $output);
    }

    /**
     * @test
     * @group doctrine
     * @expectedException \Doctrine\DBAL\Types\ConversionException
     */
    public function an_invalid_database_value_causes_an_exception_upon_conversion()
    {
        $raLocationName = Type::getType(RaLocationNameType::NAME);

        $raLocationName->convertToPHPValue(false, $this->platform);
    }
}
