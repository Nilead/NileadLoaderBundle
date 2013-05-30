<?php

namespace Nilead\LoaderBundle\Handler;

class JsHandler extends Handler{
    protected $filePattern = "<script type=\"text/javascript\" src=\"%s\"></script>\n";

    protected $extension = 'js';

    /**
     * @param $to_load
     * @param $filters
     */
    protected function filterFiles($to_load, $filters)
    {
        if (!empty($to_load) && ($filteredFiles = $this->filter($to_load, $filters)) !== false) {
            foreach ($filteredFiles as $filteredFile) {
                printf($this->filePattern, $filteredFile);
            }
        }
    }
}