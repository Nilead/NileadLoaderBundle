<?php

namespace Nilead\LoaderBundle\Filter;


class MinifyFilter implements FilterInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter($sources, $type, $destinationDir, $options)
    {
        $files = array();

        // handle request
        if (!isset($options['combine']) || !$options['combine']) {
            foreach ($sources as $file) {
                $cacheFilename = basename($file) . '.' . md5($file) . '.' . $type;

                $destination_file = $destinationDir . $cacheFilename;
                if (!file_exists($destination_file)) {
                    @file_put_contents($destination_file, \Minify::combine($file, array('minifiers' => array('application/x-javascript' => ''))));
                }

                if (file_exists($destination_file)) {
                    $files[] = $cacheFilename;
                }
            }
        } else {
            $cacheFilename = md5(serialize($sources)) . '.' . $type;

            $destination_file = $destinationDir . $cacheFilename;
            if (!file_exists($destination_file)) {
                // Todo: what to do if we do not turn on the minify?
                @file_put_contents($destination_file, \Minify::combine($sources, $options));
            }

            if (file_exists($destination_file)) {
                $files[] = $cacheFilename;
            }
        }

        return $files;
    }
}