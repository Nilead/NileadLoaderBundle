<?php
/**
 * Created by RubikIntegration Team.
 * Date: 12/28/12
 * Time: 12:20 PM
 * Question? Come to our website at http://rubikintegration.com
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nilead\LoaderBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class FilterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('nilead_loader.loader')) {
            $definition = $container->getDefinition('nilead_loader.loader');
            foreach ($container->findTaggedServiceIds('nilead_loader.filter') as $id => $attributes) {
                if (isset($attributes[0]['alias'])) {
                    $definition->addMethodCall('setFilter', array($attributes[0]['alias'], new Reference($id)));
                }
            }
        }
    }
}