<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nilead\LoaderBundler\Tests\Locator;

use Nilead\LoaderBundle\Locator\FileLocator;
use Liip\ThemeBundle\ActiveTheme;

class FileLocatorFake extends FileLocator
{
    public $lastTheme;
}

class FileLocatorTest extends \PHPUnit_Framework_TestCase
{
    protected function getKernelMock($includeDerivedBundle = false)
    {
        $data = debug_backtrace();
        $bundleName = substr($data[1]['function'], 4);

        $bundles = array();
        $prefixes = array('');
        if ($includeDerivedBundle) {
            array_unshift($prefixes, 'Derived');
        }
        foreach ($prefixes as $prefix) {
            $bundle = $this->getMockBuilder('Symfony\Component\HttpKernel\Bundle\Bundle')
            	->setMockClassName($prefix . 'LiipMock' . $bundleName)
            	->disableOriginalConstructor()
            	->getMock();
            $bundle->expects($this->any())
	            ->method('getPath')
    	        ->will($this->returnValue($this->getFixturePath()));
            $bundles[] = $bundle;
        }

        $kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\KernelInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $kernel->expects($this->any())
            ->method('getBundle')
            ->will($this->returnValue($bundles));

        return $kernel;
    }

    protected function getFixturePath()
    {
        return strtr(__DIR__ . '/../Fixtures', '\\', '/');
    }

    /**
     * @covers Nilead\ThemingBundle\Locator\FileLocator::__construct
     * @covers Nilead\ThemingBundle\Locator\FileLocator::setCurrentTheme
     */
    public function testConstructor()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('bar', array('foo', 'bar', 'foobar'));
        new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');
    }

    /**
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locate
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locateBundleResource
     */
    public function testLocate()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('foo', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $file = $fileLocator->locate('@ThemeBundle/Resources/public/template', $this->getFixturePath(), true);
        $this->assertEquals($this->getFixturePath().'/Resources/public/themes/foo/template', $file);
    }

    /**
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locate
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locateBundleResource
     */
    public function testLocateWithOverridenPathPattern()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('foo', array('foo', 'bar', 'foobar'));

        $pathPatterns = array(
            'bundle_resource' => array(
                '%bundle_path%/Resources/themes2/%current_theme%/%file%',
            )
        );

        $fileLocator = new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources', array(), $pathPatterns);

        $file = $fileLocator->locate('@ThemeBundle/Resources/public/template2', $this->getFixturePath(), true);
        $this->assertEquals($this->getFixturePath().'/Resources/themes2/foo/template2', $file);
    }

    /**
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locate
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locateBundleResource
     */
    public function testLocateWebThemeOverridesAll()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('foo', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $file = $fileLocator->locate('@ThemeBundle/Resources/public/foo', $this->getFixturePath(), true);
        $this->assertEquals($this->getFixturePath().'/rootdir/Resources/themes/foo/LiipMockLocateWebThemeOverridesAll/foo', $file);
    }

    /**
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locate
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locateWebResource
     */
    public function testLocateWeb()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('foo', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $file = $fileLocator->locate('public/template2', $this->getFixturePath().'/rootdir', true);
        $this->assertEquals($this->getFixturePath().'/rootdir/Resources/themes/foo/template2', $file);
    }

    /**
     * @contain Liip\ThemeBundle\Locator\FileLocator::locate
     */
    public function testLocateActiveThemeUpdate()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('foo', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocatorFake($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $this->assertEquals('foo', $fileLocator->lastTheme);
        $activeTheme->setName('bar');
        $fileLocator->locate('Resources/public/themes/foo/template', $this->getFixturePath(), true);
        $this->assertEquals('bar', $fileLocator->lastTheme);
    }

    /**
     * This verifies that the default view gets used if the currently active
     * one doesn't contain a matching file.
     *
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locate
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locateBundleResource
     */
    public function testLocateViewFallback()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('bar', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $file = $fileLocator->locate('@ThemeBundle/Resources/public/defaultTemplate', $this->getFixturePath(), true);
        $this->assertEquals($this->getFixturePath().'/Resources/public/defaultTemplate', $file);
    }

    /**
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locate
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locateBundleResource
     */
    public function testLocateAllFiles()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('foobar', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $expectedFiles = array(
            $this->getFixturePath().'/Resources/public/themes/foobar/template',
            $this->getFixturePath().'/Resources/public/template',
        );

        $files = $fileLocator->locate('@ThemeBundle/Resources/public/template', $this->getFixturePath(), false);
        $this->assertEquals($expectedFiles, $files);
    }

    /**
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locate
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locateWebResource
     */
    public function testLocateAllFilesWeb()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('foo', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $expectedFiles = array(
            $this->getFixturePath().'/rootdir/Resources/themes/foo/template2',
            $this->getFixturePath().'/rootdir/Resources/template2',
        );

        $files = $fileLocator->locate('public/template2', null, false);
        $this->assertEquals($expectedFiles, $files);
    }

    /**
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locate
     */
    public function testLocateParentDelegation()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('bar', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $file = $fileLocator->locate('Resources/public/themes/foo/template', $this->getFixturePath(), true);
        $this->assertEquals($this->getFixturePath().DIRECTORY_SEPARATOR.'Resources/public/themes/foo/template', $file);
    }

    /**
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locate
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locateBundleResource
     */
    public function testLocateRootDirectory()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('foo', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $file = $fileLocator->locate('@ThemeBundle/Resources/public/rootTemplate', $this->getFixturePath(), true);
        $this->assertEquals($this->getFixturePath().'/rootdir/Resources/themes/foo/LiipMockLocateRootDirectory/rootTemplate', $file);
    }

    /**
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locate
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locateBundleResource
     */
    public function testLocateOverrideDirectory()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('bar', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $file = $fileLocator->locate('@ThemeBundle/Resources/public/override', $this->getFixturePath(), true);
        $this->assertEquals($this->getFixturePath().'/rootdir/Resources/LiipMockLocateOverrideDirectory/public/override', $file);
    }

    /**
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locate
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locateBundleResource
     * @expectedException RuntimeException
     */
    public function testLocateInvalidCharacter()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('bar', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $file = $fileLocator->locate('@ThemeBundle/Resources/../public/template', $this->getFixturePath(), true);
    }

    /**
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locate
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locateBundleResource
     * @expectedException RuntimeException
     */
    public function testLocateNoResource()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('bar', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $file = $fileLocator->locate('@ThemeBundle/bogus', $this->getFixturePath(), true);
    }

    /**
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locate
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locateBundleResource
     * @expectedException InvalidArgumentException
     */
    public function testLocateNotFound()
    {
        $kernel =  $this->getKernelMock();
        $activeTheme = new ActiveTheme('bar', array('foo', 'bar', 'foobar'));
        $fileLocator = new FileLocator($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources');

        $file = $fileLocator->locate('@ThemeBundle/Resources/nonExistant', $this->getFixturePath(), true);
    }

    /**
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locate
     * @covers Nilead\ThemingBundle\Locator\FileLocator::locateBundleResource
     * @expectedException InvalidArgumentException
     */
    public function testLocateBundleInheritance()
    {
        $kernel =  $this->getKernelMock(true);
        $activeTheme = new ActiveTheme('bar', array('foo', 'bar', 'foobar'));

        $fileLocator = $this->getMock(
            'Liip\ThemeBundle\Locator\FileLocator',
            array('getPathsForBundleResource'),
            array($kernel, $activeTheme, $this->getFixturePath() . '/rootdir/Resources')
        );

        $fileLocator->expects($this->at(0))
        ->method('getPathsForBundleResource')
        ->with($this->callback(function($parameters) {
            return 'DerivedLiipMockLocateBundleInheritance' == $parameters['%bundle_name%'];
        }))
        ->will($this->returnValue(array()));

        $fileLocator->expects($this->at(1))
        ->method('getPathsForBundleResource')
        ->with($this->callback(function($parameters) {
            return 'LiipMockLocateBundleInheritance' == $parameters['%bundle_name%'];
        }))
        ->will($this->returnValue(array()));

        $file = $fileLocator->locate('@ThemeBundle/Resources/nonExistant', $this->getFixturePath(), true);
    }
}
