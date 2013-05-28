<?php

namespace Nilead\LoaderBundle\Filter;


class MinifyFilter
{

    /**
     * @var
     */
    protected $storeRootDir;

    /**
     * @var
     */
    protected $fileUtility;

    /**
     * @param $cache
     * @param $cachePath
     * @param $fileUtility
     */
    public function __construct($storeRootDir, $fileUtility)
    {
        $this->storeRootDir = $storeRootDir;
        $this->fileUtility = $fileUtility;
    }

    /**
     * Filter the resources
     *
     * @param array $sources
     * @param string $extension
     * @param array $options
     * @return array
     */
    public function filter($sources, $extension, $options)
    {
        $files = array();

        // handle request
        if (!$options['combine']) {
            foreach ($sources as $file) {
                $cacheFilename = basename($file) . '.' . md5($file) . '.' . $extension;

                $destination_file = $this->cacheDir . $cacheFilename;
                if (!file_exists($destination_file)) {
                    @file_put_contents($destination_file, \Minify::combine($file, array('minifiers' => array('application/x-javascript' => ''))));
                }

                if (file_exists($destination_file)) {
                    $files[] = $this->fileUtility->getRelativePath($this->storeRootDir, $destination_file);
                }
            }
        } else {
            $cacheFilename = md5(serialize($sources)) . '.' . $extension;

            $destination_file = $this->cacheDir . $cacheFilename;
            if (!file_exists($destination_file)) {
                // Todo: what to do if we do not turn on the minify?
                @file_put_contents($destination_file, \Minify::combine($sources, $options));
            }

            if (file_exists($destination_file)) {
                $files[] = $this->fileUtility->getRelativePath($this->storeRootDir, $destination_file);
            }
        }

        return $files;

    }
}