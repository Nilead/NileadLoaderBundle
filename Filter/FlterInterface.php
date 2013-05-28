<?php
/**
 * Created by Rubikin Team.
 * Date: 5/24/13
 * Time: 2:06 PM
 * Question? Come to our website at http://rubikin.com
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nilead\LoaderBundle\Filter;


interface FlterInterface {

    public function filter($sources, $extension, $options);
}