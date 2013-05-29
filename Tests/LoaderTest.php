<?php
/**
 * Created by Rubikin Team.
 * Date: 5/24/13
 * Time: 4:59 PM
 * Question? Come to our website at http://rubikin.com
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nilead\LoaderBundle\Tests;


use Nilead\LoaderBundle\Loader;

class LoaderTest extends \PHPUnit_Framework_TestCase
{
    protected $loader;

    public function setUp()
    {
        $processor = $this->getMockBuilder('Nilead\LoaderBundle\Processor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->loader = new Loader(array(), $processor);
    }


    public function testLoad()
    {
        ob_start();
        $this->loader->load(array('abc.css', 'xyz.js' => array('min' => 1, 'max' => 2)));
        $echoed = ob_get_clean();

        $this->assertEquals($this->loader->getFiles(), array(
            '1' =>array(
                'abc.css' => array('ext' => 'css'),
                'xyz.js' => array('ext' => 'js', 'min' => 1, 'max' => 2)
            )
        ));

        $this->assertEquals('<!-- loader: 1 -->', $echoed);
    }

}