<?php
/**
 * Created by RubikIntegration Team.
 *
 * Date: 10/20/12
 * Time: 9:07 AM
 * Question? Come to our website at http://rubikin.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or refer to the LICENSE
 * file of ZePLUF framework
 */

namespace Nilead\LoaderBundle;

use Nilead\LoaderBundle\DependencyInjection\Compiler\FilterPass;
use Nilead\LoaderBundle\DependencyInjection\Compiler\HandlerPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Bundle.
 *
 */
class NileadLoaderBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new FilterPass());
        $container->addCompilerPass(new HandlerPass());
    }
}
