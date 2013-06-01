<?php
/**
 * Created by Rubikin Team.
 * Date: 5/29/13
 * Time: 5:16 PM
 * Question? Come to our website at http://rubikin.com
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nilead\LoaderBundle\Tests\Filter;


use Nilead\LoaderBundle\Filter\MinifyFilter;

class MinifyFilterTest extends \PHPUnit_Framework_TestCase{

    public function testFilter()
    {
        $expectedFiles = array(
           'a.css.c78798efbf37288cacef29511915987e.css',
           'b.css.d20bf815d07589a988eb269f48729fba.css'
        );

        foreach ($expectedFiles as $expectedFile) {
            @unlink(__DIR__ . '/../Fixtures/FilterFixtures/cache/' . $expectedFile);
        }


        $filter = new MinifyFilter();
        $filteredFiles = $filter->filter(
            array(__DIR__ . '/../Fixtures/FilterFixtures/a.css', __DIR__ . '/../Fixtures/FilterFixtures/b.css'),
            'css',
            __DIR__ . '/../Fixtures/FilterFixtures/cache/',
            array()
            );

        $this->assertEquals($filteredFiles, $expectedFiles);

        foreach ($expectedFiles as $expectedFile) {
            $this->assertTrue(file_exists(__DIR__ . '/../Fixtures/FilterFixtures/cache/' . $expectedFile));
        }
    }

    public function testFilterCombine()
    {
        $expectedFiles = array(
            '11865343b5d6b7510232a8861615d2be.css'
        );

        foreach ($expectedFiles as $expectedFile) {
            @unlink(__DIR__ . '/../Fixtures/FilterFixtures/cache/' . $expectedFile);
        }


        $filter = new MinifyFilter();
        $filteredFiles = $filter->filter(
            array(__DIR__ . '/../Fixtures/FilterFixtures/a.css', __DIR__ . '/../Fixtures/FilterFixtures/b.css'),
            'css',
            __DIR__ . '/../Fixtures/FilterFixtures/cache/',
            array('combine' => true)
        );

        $this->assertEquals($filteredFiles, $expectedFiles);

        foreach ($expectedFiles as $expectedFile) {
            $this->assertTrue(file_exists(__DIR__ . '/../Fixtures/FilterFixtures/cache/' . $expectedFile));
        }
    }
}