<?php
/**
 * Created by RubikIntegration Team.
 * Date: 2/1/13
 * Time: 3:32 PM
 * Question? Come to our website at http://rubikintegration.com
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nilead\LoaderBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class OnResponseListener implements EventSubscriberInterface
{
    protected $loader;

    public function __construct($loader)
    {
        $this->loader = $loader;
    }

    public function onResponse(KernelEvent $event)
    {
        $event->getResponse()->setContent($this->loader->injectAssets($event->getResponse()->getContent()));
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::RESPONSE => array('onResponse', -9999),
        );
    }
}
