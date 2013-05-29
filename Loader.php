<?php

namespace Nilead\LoaderBundle;

/**
 * Required functions for the CSS/JS Loader
 *
 * @author yellow1912 (rubikin.com)
 * @author John William Robeson, Jr <johnny@localmomentum.net>
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License V2.0
 *
 * NOTES:
 * All .php files can be manipulated by PHP when they're called, and are copied in-full to the browser page
 */

use Doctrine\Common\Cache\Cache;
use Symfony\Component\Templating\Helper\Helper;

class Loader extends Helper
{
    protected $loaders = array();

    protected $files = array();

    protected $handlers = array();

    protected $settings = array(
        'dirs' => array(),
        'loaders' => '*',
        'load_print' => true
    );

    /**
     * @var Processor
     */
    protected $processor;
    /**
     * @var array
     */
    protected $inline = array();

    /**
     * @var int
     */
    protected $location = 0;


    /**
     * @var array
     */
    protected $loaded_libs = array();

    /**
     * @var
     */
    protected $finder;

    /**
     * @var
     */
    protected $filters;

    /**
     * @var \Doctrine\Common\Cache\Cache
     */
    protected $cache;

    public function __construct($settings, Processor $processor, Cache $cache)
    {
        $this->settings = array_merge($this->settings, $settings);
        $this->processor = $processor;
        $this->cache = $cache;
    }

    /**
     * @param $id
     * @param $filter
     */
    public function setFilter($id, $filter)
    {
        if (isset($this->settings['filters'][$id])) {
            $this->filters[$id] = array('filter' => $filter, 'options' => $this->settings['filters'][$id]);
        }
    }

    /**
     * @return mixed
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param $id
     * @param $handler
     */
    public function setHandler($id, $handler)
    {
        if (isset($this->settings['handlers']) && in_array($id, $this->settings['handlers'])) {
            $this->handlers[$id] = $handler;
        }
    }

    /**
     * @param $type
     * @return mixed
     */
    private function getHandler($type)
    {
        return $this->handlers[$type];
    }

    /**
     * returns the name of this helper
     *
     * @return string
     */
    public function getName()
    {
        return 'loader';
    }

    /**
     * @param $options
     */
    function setSettings($settings)
    {
        $this->settings = array_merge($this->settings, $settings);
    }

    /**
     * @param string $key
     * @param bool $default
     * @return array|bool
     */
    public function getSetting($key = '', $default = false)
    {
        if (!empty($key)) {
            return isset($this->settings[$key]) ? $this->settings[$key] : $default;
        } else {
            return $this->settings;
        }
    }

    public function getFiles()
    {
        return $this->files;
    }
    /**
     * Load the file or set of files or libs
     *
     * @param array $files array('path/to/file', 'path/to/file2.lib' => array('min' => 1, 'max' => 2))
     * @param string $location allows loading the file at header/footer or current location
     */
    public function load($files, $location = '', $silent = false)
    {
        $files = (array)$files;

        // rather costly operation here but we need to determine the location
        if (empty($location)) {
            $location = ++$this->location;
            if (!$silent) {
                echo  '<!-- loader: ' . $location . ' -->';
            }
        } // now we will have to echo out the string to be replaced here
        elseif ($location !== 'header' && $location !== 'footer' && $location != $this->location) {
            if (!$silent) {
                echo  '<!-- loader: ' . $location . ' -->';
            }
        }

        foreach ($files as $file => $options) {
            if (!is_array($options)) {
                $file = $options;
                $options = array();
            }

            // only add this file if it has not been requested for the same position
            if (!isset($this->files[$location]) || !in_array($file, $this->files[$location])) {
                $options['ext'] = pathinfo($file, PATHINFO_EXTENSION);

                $this->files[$location][$file] = $options;
            }
        }
        return $location;
    }

    /**
     * Starts inline
     *
     * @param string $type
     * @param string $location
     */
    public function startInline($type = 'js', $location = '')
    {
        if ($location !== 'header' && $location !== 'footer') {
            if (empty($location)) {
                $location = $this->location;
            }
        }

        $this->inline = array('type' => $type,
            'location' => $location);
        ob_start();
    }

    /**
     * End inline
     */
    public function endInline()
    {
        $this->load(
            array('inline.' . $this->inline['type'] => array(
                'inline' => ob_get_clean(),
                'src'   => FileSource::INLINE
            )
        ), $this->inline['location']);
    }

    /**     
     * Inject the assets into the content of the page
     *
     * @param string $content
     */
    public function injectAssets($content)
    {
        $content = $this->parseContent($content);

        // scan the content to find out the real order of the loader
        preg_match_all("/(<!-- loader:)(.*?)(-->)/", $content, $matches, PREG_SET_ORDER);

        // order the files in correct order (according the loader location)
        $orderedFiles = $this->processor->orderFiles($this->files, $matches);

        $id = md5(serialize($orderedFiles));

        if(($files = $this->cache->fetch($id)) === false) {
            // cache if necessary
            $processedFiles = $this->processor->processFiles($orderedFiles);

            $foundFiles = $this->processor->findFiles($processedFiles);

            $files = array();
            foreach ($foundFiles as $type => $locations) {
                foreach ($locations as $location => $files) {
                    $files[] = array(
                        'location' => $location,
                        'inject_content' => $this->getHandler($type)->process($files, $this->filters)
                    );
                }
            }

            $this->cache->save($id, $files);
        }

        foreach ($files as $file) {
            // inject
            switch ($file['location']) {
                case 'header':
                    $content = str_replace('</head>', $file['inject_content'] . '</head>', $content);
                    break;
                case 'footer':
                    $content = str_replace('</body>', $file['inject_content'] . '</body>', $content);
                    break;
                default:
                    $content = str_replace('<!-- loader: ' . $file['location'] . ' -->', $file['inject_content'] . '<!-- loader: ' . $file['location'] . ' -->', $content);
                    break;
            }
        }

        return $content;
    }

    /**
     * @return array
     */
    public function getAssetsArray()
    {
        $processedFiles = $this->processor->processFiles($this->files);

        $result = array();
        foreach ($processedFiles as $type => $locations) {
            foreach ($locations as $location => $files) {

                // we may want to do some caching here
                $result[$location][$type] = $this->getHandler($type)->processArray($files, $type, $this);
            }
        }

        return $result;
    }

    /**
     * @param string $content
     * @return string
     */
    protected function parseContent($content)
    {
        // parse the loads
        // sample: abc.css, template:current:file.php|type:js
        preg_match_all("/(<!-- load:)(.*?)(-->)/", $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $val) {
            $val[2] = str_replace(' ', '', $val[2]);
            $temp = explode(',', $val[2]);
            $load = array();
            foreach ($temp as $v) {

                // check if we have additional parameters
                if (strpos($v, '|') !== false) {
                    $v = explode('|', $v);
                    $v[1] = explode(';', $v[1]);
                    foreach ($v[1] as $u) {
                        $u = explode(':', $u);
                        $load[$v[0]][$u[0]] = $u[1];
                    }
                } else {
                    $load[] = $v;
                }
            }
            $location = $this->load($load, '', true);
            $content = str_replace($val[0], '<!-- loader: ' . $location . ' -->', $content);
        }

        return $content;
    }

    /**
    * @param $files
    * @param $file
    * @param $location
    * @param $options
    */
    protected function _load(&$files, $file, $location, $options)
    {
        // for css, they MUST be loaded at header
        if ($options['ext'] == 'css' && is_integer($location)) {
            $location = 'header';
        }

        $this->getHandler($options['ext'])->load($files, $file, $location, $options);
    }
}