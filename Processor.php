<?php
/**
 * Created by Rubikin Team.
 * Date: 5/24/13
 * Time: 9:48 PM
 * Question? Come to our website at http://rubikin.com
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nilead\LoaderBundle;


use Nilead\LoaderBundle\FileSource;
use Nilead\LoaderBundle\Locator\FileLocator;
use Nilead\UtilityBundle\Utility\Collection;
use Nilead\UtilityBundle\Utility\String;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;

class Processor
{

    /**
     * @var
     */
    protected $libs = array();

    /**
     * @var \Nilead\UtilityBundle\Utility\Collection
     */
    protected $collectionUtility;

    /**
     * @var \Nilead\UtilityBundle\Utility\String
     */
    protected $stringUtility;

    /**
     * @var Locator\FileLocator
     */
    protected $fileLocator;

    public function __construct(Collection $collectionUtility, String $stringUtility, FileLocator $fileLocator)
    {
        $this->collectionUtility = $collectionUtility;
        $this->stringUtility = $stringUtility;
        $this->fileLocator = $fileLocator;

        $files = Finder::create()
            ->in(__DIR__ . '/Resources/config/libs/')
            ->name('*.yml');

        foreach ($files as $file) {
            $lib = Yaml::parse($file->getPath() . '/' . $file->getFileName());
            $this->libs[$file->getBaseName('.yml')] = $lib;
        }
    }

    /**
     * for backward compatibility
     *
     * @param $libs
     */
    public function addLibs($libs)
    {
        foreach ($libs as $lib => $versions) {
            foreach ($versions as $version => $options) {
                if (!isset($this->libs[$lib]))
                    $this->libs[$lib][$version] = $options;
            }
        }
    }

    /**
     * Sort the files according to the location of the loader
     *
     * @param array $files
     * @param array $loaders
     * @return array
     */
    public function orderFiles($files, $loaders)
    {
        // load the files
        $ordered_files = array();

        foreach ($loaders as $val) {
            $val[2] = trim($val[2]);
            if (isset($files[$val[2]])) {
                $ordered_files[$val[2]] = $files[$val[2]];
            }
        }

        if (isset($files['header'])) {
            $ordered_files['header'] = $files['header'];
        }

        if (isset($files['footer'])) {
            $ordered_files['footer'] = $files['footer'];
        }

        return $ordered_files;
    }

    /**
     * @param $ordered_files
     * @return array
     */
    public function processFiles($ordered_files, $useCDN = true, $useSSL = false)
    {
        $to_load = $this->removeDuplicates($ordered_files);

        $processedFiles = array();

        // now we will have to process the list of files to put them in their real type to process later
        foreach ($to_load as $location => $files) {
            foreach ($files as $file => $options) {
                switch ($options['ext']) {
                    // lib? load the library
                    case 'lib':
                        // we need to try loading the config file
                        $lib = str_replace('.lib', '', $file);

                        if (isset($this->libs[$lib])) {
                            $lib_versions = array_keys($this->libs[$lib]);

                            // if options are passed in
                            if (is_array($options)) {
                                if (isset($options['min']) && (($pos = array_search($options['min'], $lib_versions)) != 0)) {
                                    $lib_versions = array_slice($lib_versions, $pos);
                                }

                                if (isset($options['max']) && (($pos = array_search($options['max'], $lib_versions)) < count($lib_versions) - 1)) {
                                    array_splice($lib_versions, $pos + 1);
                                }
                            }

                            if (empty($lib_versions)) {
                                // houston we have a problem
                                // TODO: we need to somehow print out the error in this case
                            } else {
                                // we prefer the latest version
                                $lib_version = end($lib_versions);

                                // use the default file set if necessary
                                if (empty($this->libs[$lib][$lib_version])) {
                                    $this->libs[$lib][$lib_version] = $this->libs[$lib]['default'];
                                } else {
                                    if (!isset($this->libs[$lib][$lib_version]['local']) && isset($this->libs[$lib]['default']['local'])) {
                                        $this->libs[$lib][$lib_version]['local'] = $this->libs[$lib]['default']['local'];
                                    }

                                    if (!isset($this->libs[$lib][$lib_version]['cdn']) && isset($this->libs[$lib]['default']['cdn'])) {
                                        $this->libs[$lib][$lib_version]['cdn'] = $this->libs[$lib]['default']['cdn'];
                                    }
                                }
                                $this->libs[$lib][$lib_version] = $this->stringUtility->strReplaceDeep('{version}', $lib_version, $this->libs[$lib][$lib_version]);

                                // add the files
                                foreach ($this->libs[$lib][$lib_version] as $ext => $files) {
                                    foreach ($files as $_file => $_file_options) {
                                        if ($useCDN && isset($_file_options['cdn'])) {
                                            $file = $useSSL ? $_file_options['cdn']['https'] : $_file_options['cdn']['http'];
                                            $processedFiles[$ext][$location][] = array('file' => $file, 'options' => array('ext' => $ext, 'src' => FileSource::EXTERNAL));
                                        } else {
                                            if (strpos($_file_options['local'], ":") !== false) {
                                                $local = explode(":", $_file_options['local']);

                                                if (empty($local[2])) {
                                                    $local[2] = $_file;
                                                }

                                                $file = $local[0] . ':' . $local[1] . ':' . $lib . '/' . $lib_version . '/' . $local[2];
                                            } else {
                                                $file = '@NileadLoaderBundle/Resources/public/libs/' . $lib . '/' . $lib_version . '/' . (!empty($_file_options['local']) ? $_file_options['local'] : $_file);
                                            }

                                            if(!isset($options['src'])) {
                                                $options['src'] = FileSource::LOCAL;
                                            }
                                            $processedFiles[$ext][$location][] = array('file' => $file, 'options' => $options);
                                        }
                                    }
                                }
                            }
                        }
                        break;
                    default:
                        if(!isset($options['src'])) {
                            $options['src'] = FileSource::LOCAL;
                        }
                        $processedFiles[$options['ext']][$location][] = array('file' => $file, 'options' => $options);
                        break;
                }
            }
        }

        return $processedFiles;
    }

    /**
     * Look for the files (local)
     *
     * @param array $processedFiles
     * @return array
     */
    public function findFiles($processedFiles)
    {
        foreach ($processedFiles as $type => $locations)
        {
            foreach ($locations as $location => $files) {
                foreach ($files as $index => $file) {
                    if(FileSource::LOCAL == $file['options']['src']) {
                        $processedFiles[$type][$location][$index]['file'] = $this->fileLocator->locate($file['file']);
                    }
                }
            }
        }
        return $processedFiles;
    }

    protected function removeDuplicates($ordered_files)
    {
        // now we loop thru the $ordered_files to make sure each file is loaded only once
        $loaded_files = $to_load = array();
        foreach ($ordered_files as $location => $files) {
            $location_loaded_files = array();

            foreach ($files as $file => $options) {
                if (!array_key_exists($file, $loaded_files)) {
                    $loaded_files[$file] = $location;
                    $to_load[$location][$file] = $options;
                    $location_loaded_files[$file] = $options;
                } // if we encounter this file in the loaded list, it means that we will have to take all the loaded
                // files in this same location and put it IN FRONT OF this file location which is $loaded_files[$file]
                elseif (!empty($location_loaded_files)) {

                    $to_load[$location] = array_diff($to_load[$location], $location_loaded_files);

                    $this->collectionUtility->kSplice2($to_load[$loaded_files[$file]], $file, 0, $location_loaded_files);

                    $location_loaded_files = array();
                }
            }
        }

        return $to_load;
    }
}