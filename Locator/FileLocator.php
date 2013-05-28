<?php
/**
 * Created by Rubikin Team.
 * Date: 5/25/13
 * Time: 11:09 PM
 * Question? Come to our website at http://rubikin.com
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nilead\LoaderBundle\Locator;


use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Config\FileLocator as BaseFileLocator;
use Liip\ThemeBundle\ActiveTheme;

/**
 * Class FileLocator
 * @package Nilead\LoaderBundle\Locator
 *
 * Part of the code in this file is taken from Liip Theme Bundle
 * Our sincere thanks to all the contributors of Liip
 */
class FileLocator extends BaseFileLocator
{
    protected $kernel;
    protected $path;
    protected $basePaths = array();
    protected $pathPatterns;

    /**
     * @var ActiveTheme
     */
    protected $activeTheme;

    /**
     * @var string
     */
    protected $lastTheme;

    /**
     * Constructor.
     *
     * @param KernelInterface $kernel A KernelInterface instance
     * @param string $path The path the global resource directory
     *
     * @throws \InvalidArgumentException if the active theme is not in the themes list
     */
    public function __construct(KernelInterface $kernel, ActiveTheme $activeTheme, $path = null, array $paths = array(),
                                array $pathPatterns = array())
    {
        $this->kernel = $kernel;
        $this->activeTheme = $activeTheme;
        $this->path = $path;
        $this->basePaths = $paths;

        $defaultPathPatterns = array(
            'web_resource' => array(
                '%web_path%/themes/%current_theme%/%file%',
                '%web_path%/%file%',
            ),
            'bundle_resource' => array(
                '%bundle_path%/Resources/public/themes/%current_theme%/%file%',
            ),
            'bundle_resource_dir' => array(
                '%dir%/themes/%current_theme%/%bundle_name%/%file%',
                '%dir%/%bundle_name%/%override_path%',
            ),
        );

        $this->pathPatterns = array_replace($defaultPathPatterns, array_filter($pathPatterns));

        $this->setCurrentTheme($this->activeTheme->getName());
    }

    /**
     * Set the active theme.
     *
     * @param string $theme
     */
    public function setCurrentTheme($theme)
    {
        $this->lastTheme = $theme;

        $paths = $this->basePaths;

        // add active theme as Resources/themes/public folder as well.
        $paths[] = $this->path . '/themes/' . $theme;
        $paths[] = $this->path;

        $this->paths = $paths;
    }

    /**
     * Returns the file path for a given resource for the first directory it
     * has a match.
     *
     * The resource name must follow the following pattern:
     *
     *     "@BundleName/path/to/a/file.something"
     *
     * where BundleName is the name of the bundle
     * and the remaining part is the relative path in the bundle.
     *
     * @param string  $name  A resource name to locate
     * @param string  $dir   A directory where to look for the resource first
     * @param Boolean $first Whether to return the first path or paths for all matching bundles
     *
     * @return string|array The absolute path of the resource or an array if $first is false
     *
     * @throws \InvalidArgumentException if the file cannot be found or the name is not valid
     * @throws \RuntimeException         if the name contains invalid/unsafe characters
     */
    public function locate($name, $dir = null, $first = true)
    {
        // update the paths if the theme changed since the last lookup
        $theme = $this->activeTheme->getName();
        if ($this->lastTheme !== $theme) {
            $this->setCurrentTheme($theme);
        }

        if ('@' === $name[0]) {
            return $this->locateBundleResource($name, $this->path, $first);
        }

        if (0 === strpos($name, 'public/')) {
            if ($res = $this->locateWebResource($name, $this->path, $first)) {
                return $res;
            }
        }

        return parent::locate($name, $dir, $first);
    }

    /**
     * Locate Resource Theme aware. Only working for bundle resources!
     *
     * Method inlined from Symfony\Component\Http\Kernel
     *
     * @param string $name
     * @param string $dir
     * @param bool $first
     * @return string
     */
    public function locateBundleResource($name, $dir = null, $first = true)
    {
        if (false !== strpos($name, '..')) {
            throw new \RuntimeException(sprintf('File name "%s" contains invalid characters (..).', $name));
        }

        $bundleName = substr($name, 1);
        $path = '';
        if (false !== strpos($bundleName, '/')) {
            list($bundleName, $path) = explode('/', $bundleName, 2);
        }

        if (0 !== strpos($path, 'Resources')) {
            throw new \RuntimeException('Resource files have to be in Resources.');
        }

        $resourceBundle = null;
        $bundles = $this->kernel->getBundle($bundleName, false);
        $files = array();

        $parameters = array(
            '%web_path%'      => $this->path,
            '%dir%'           => $dir,
            '%override_path%' => substr($path, strlen('Resources/')),
            '%current_theme%' => $this->lastTheme,
            '%file%'      => substr($path, strlen('Resources/public/')),
        );

        foreach ($bundles as $bundle) {
            $parameters = array_merge($parameters, array(
                '%bundle_path%' => $bundle->getPath(),
                '%bundle_name%' => $bundle->getName(),
            ));

            $checkPaths = $this->getPathsForBundleResource($parameters);

            foreach ($checkPaths as $checkPath) {
                if (file_exists($checkPath)) {
                    if (null !== $resourceBundle) {
                        throw new \RuntimeException(sprintf('"%s" resource is hidden by a resource from the "%s" derived bundle. Create a "%s" file to override the bundle resource.',
                            $path,
                            $resourceBundle,
                            $checkPath
                        ));
                    }

                    if ($first) {
                        return $checkPath;
                    }
                    $files[] = $checkPath;
                }
            }

            $file = $bundle->getPath().'/'.$path;
            if (file_exists($file)) {
                if ($first) {
                    return $file;
                }
                $files[] = $file;
                $resourceBundle = $bundle->getName();
            }
        }

        if (count($files) > 0) {
            return $first ? $files[0] : $files;
        }

        throw new \InvalidArgumentException(sprintf('Unable to find file "%s".', $name));
    }

    /**
     * Locate Resource Theme aware. Only working for web/Resources
     *
     * @param string $name
     * @param string $dir
     * @param bool $first
     * @return string|array
     */
    public function locateWebResource($name, $dir = null, $first = true)
    {
        if (false !== strpos($name, '..')) {
            throw new \RuntimeException(sprintf('File name "%s" contains invalid characters (..).', $name));
        }

        $files = array();
        $parameters = array(
            '%web_path%'      => $this->path,
            '%current_theme%' => $this->lastTheme,
            '%file%'      => substr($name, strlen('public/')),
        );

        foreach ($this->getPathsForWebResource($parameters) as $checkPaths) {
            if (file_exists($checkPaths)) {
                if ($first) {
                    return $checkPaths;
                }
                $files[] = $checkPaths;
            }
        }

        return $files;
    }

    protected function getPathsForBundleResource($parameters)
    {
        $pathPatterns = array();
        $paths = array();

        if (!empty($parameters['%dir%'])) {
            $pathPatterns = array_merge($pathPatterns, $this->pathPatterns['bundle_resource_dir']);
        }

        $pathPatterns = array_merge($pathPatterns, $this->pathPatterns['bundle_resource']);

        foreach ($pathPatterns as $pattern) {
            $paths[] = strtr($pattern, $parameters);
        }

        return $paths;
    }

    protected function getPathsForWebResource($parameters)
    {
        $paths = array();

        foreach ($this->pathPatterns['web_resource'] as $pattern) {
            $paths[] = strtr($pattern, $parameters);
        }

        return $paths;
    }
}
