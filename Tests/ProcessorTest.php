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


use Nilead\LoaderBundle\Processor;

class ProcessorTest extends \PHPUnit_Framework_TestCase
{
    protected $processor;

    public function setUp()
    {
        $collectionUtility = $this->getMock('Nilead\UtilityBundle\Utility\Collection');
        $stringUtility = $this->getMock('Nilead\UtilityBundle\Utility\String');

        $stringUtility->expects($this->any())
            ->method('strReplaceDeep')
            ->will($this->returnValue(array('js' => array(
                'jquery.js' => array(
                    'local' => 'jquery.js',
                    'cdn'   => array(
                        'http' => 'http://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js',
                        'https' => 'https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js'
                    )
                )
            ))));

        $fileLocator = $this->getMockBuilder('Nilead\LoaderBundle\Locator\FileLocator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new Processor($collectionUtility, $stringUtility, $fileLocator);
    }



    public function testOrderFiles()
    {
        $orderedFiles = $this->processor->orderFiles(array(
                '1' => array('abc.css' => array('ext' => 'css')),
                '2' => array('xyz.js' => array('ext' => 'js', 'min' => 1, 'max' => 2))
            )
            , array(
                array('<!-- loader: 2 -->', '<!-- loader:', ' 2', '-->'),
                array('<!-- loader: 1 -->', '<!-- loader:', ' 1', '-->')
            )
        );

        $this->assertEquals($orderedFiles, array(
            '2' => array('xyz.js' => array('ext' => 'js', 'min' => 1, 'max' => 2)),
            '1' => array('abc.css' => array('ext' => 'css'))
        ));
    }

    public function testProcessFiles()
    {
        $processedFiles = $this->processor->processFiles(array(
            '1' =>array(
                'jquery.lib' => array('ext' => 'lib'),
                'xyz.js' => array('ext' => 'js', 'min' => 1, 'max' => 2)
            )
        ));

        $this->assertEquals($processedFiles, array(
            'js' => array(
                '1' => array(
                    array(
                        'file' => 'http://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js',
                        'options' => array(
                            'ext' => 'js',
                            'src' => 'external'
                        )
                    ),
                    array(
                        'file' => 'xyz.js',
                        'options' => array(
                            'ext' => 'js',
                            'min' => 1,
                            'max' => 2,
                            'src' => 'local'
                        )
                    )
                )
            )
        ));
    }
}