<?php

namespace Nilead\LoaderBundle\Handler;


class CssHandler extends Handler
{
    protected $filePattern = "<link rel=\"stylesheet\" type=\"text/css\" media=\"%s\" href=\"%s\" />\n";
    protected $extension = 'css';

    /**
     * (non-PHPdoc)
     * @see Nilead\LoaderBundle.Handler::load()
     */
    public function load(&$files, $file, $location, $options)
    {

        if (!isset($options['media'])) {
            $options['media'] = 'screen';
        }

        $files[$options['ext']][$location][$options['media']][$file] = $options;
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

        foreach ($files as $media => $_files) {

            $to_load = array();

            foreach ($_files as $file => $options) {
                switch ($options['src']) {
                    case FileSource::EXTERNAL:
                        // if the inject content is not empty, we should push it into 1 file to cache
                        $this->filterFiles($to_load, $media, $filters);

                        printf($this->filePattern, $media, $file);
                        break;
                    case FileSource::INLINE:
                        // if we encounter inline, we must first print out the other local files requested before it
                        $this->filterFiles($to_load, $media, $filters);

                        echo $options['inline'];
                        break;
                    default:
                        $to_load[] = $file;

                }
            }

            $this->filterFiles($to_load, $media, $filters);
        }

        $result = ob_get_clean();

        return $result;
    }

    /**
     * @param $to_load
     * @param $media
     * @param $filters
     */
    protected function filterFiles($to_load, $media, $filters)
    {
        if (!empty($to_load) && ($filteredFiles = $this->filter($to_load, $filters)) !== false) {
            foreach ($filteredFiles as $filteredFile) {
                printf($this->filePattern, $media, $filteredFile);
            }
        }
    }
}