<?php
namespace Nilead\LoaderBundle\Handler;


use Nilead\LoaderBundle\FileSource;

abstract class Handler
{
    protected $filePattern = '';

    protected $extension = '';

    protected $webDir;

    protected $cacheDir;

    protected $webRelativeDir;

    public function __construct($webDir, $cacheDir, $fileUtility)
    {
        $this->webDir = $webDir;
        $this->cacheDir = $cacheDir;
        $this->webRelativeDir = $fileUtility->getRelativePath($this->webDir, $this->cacheDir);
    }

    /**
     * @param $files
     * @param $file
     * @param $location
     * @param $options
     */
    public function load(&$files, $file, $location, $options)
    {
        $files[$options['type']][$location][$file] = $options;
    }

    /**
     *
     * This function is responsible for outputing the files (and also doing combining, minifying etc if needed)
     *
     * @param array $files
     * @param string $type
     * @param object Loader $finder
     */
    public function process($files, $filters)
    {
        ob_start();

        $to_load = array();

        foreach ($files as $file) {
            switch ($file['options']['src']) {
                case FileSource::EXTERNAL:
                    // if the inject content is not empty, we should push it into 1 file to cache
                    $this->filterFiles($to_load, $filters);
                    printf($this->filePattern, $file['file']);
                    break;
                case FileSource::INLINE:
                    // if we encounter inline, we must first print out the other local files requested before it
                    $this->filterFiles($to_load, $filters);

                    echo $file['options']['inline'];
                    break;
                default:
                    $to_load[] = $file['file'];
                    break;

            }

            $this->filterFiles($to_load, $filters);
        }

        $result = ob_get_clean();

        return $result;
    }

    /**
     * This function assists in caching the loaded content into a file to be able to serve from content different than
     * the file original location
     *
     * @param string $inject_content
     * @param string $filesrcs
     * @param string $type
     */
    protected function filter(&$to_load, $filters)
    {
        $filteredFiles = array();
        if (!empty($to_load)) {
            foreach ($filters as $filter) {
                $filteredFiles = $filter['filter']->filter($to_load, $this->extension, $this->cacheDir, $filter['options']);
            }

            foreach ($filteredFiles as $key => $val) {
                $filteredFiles[$key] = $this->webRelativeDir . '/' . $val;
            }

            $to_load = array();
        }
        return !empty($filteredFiles) ? $filteredFiles : false;
    }
}