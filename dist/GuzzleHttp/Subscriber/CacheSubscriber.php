<?php

namespace Csa\Bundle\GuzzleBundle\GuzzleHttp\Subscriber;

use Csa\Bundle\GuzzleBundle\Cache\CacheStorage;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Event\ErrorEvent;
use GuzzleHttp\Event\RequestEvents;
use GuzzleHttp\Event\SubscriberInterface;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

/**
 * Csa Guzzle Stopwatch integration
 *
 * @author Charles Sarrazin <charles@sarraz.in>
 */
class CacheSubscriber implements SubscriberInterface
{
    /**
     * @var CacheStorage
     */
    protected $storage;

    /**
     * @var boolean
     */
    protected $enabled;

    public function __construct(CacheStorage $storage, $enabled = true)
    {
        $this->storage = $storage;
        $this->enabled = (bool) $enabled;
    }

    public function getEvents()
    {
        return [
            'before'   => ['onBefore', RequestEvents::LATE],
            'complete' => ['onComplete', RequestEvents::EARLY],
            'error'    => ['onError', RequestEvents::EARLY],
        ];
    }

    public function onBefore(BeforeEvent $event)
    {
        $request = $event->getRequest();

        if (!$this->enabled) {
            $request->getConfig()->set('cache.disable', true);
        }

        if (!$this->canCacheRequest($request)) {
            $this->cacheMiss($request);
            return;
        }

        if (!($response = $this->storage->fetch($request))) {
            $this->cacheMiss($request);
            return;
        }

        $request->getConfig()->set('cache_lookup', 'HIT');
        $request->getConfig()->set('cache_hit', true);
        $event->intercept($response);
    }

    public function onComplete(CompleteEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($request->getConfig()->get('cache_lookup') === 'MISS'
            && $this->canCacheRequest($request)
            && $this->canCacheResponse($response)
        ) {
            $this->storage->save($request, $response);
        }
    }

    public function onError(ErrorEvent $event)
    {
        $request = $event->getRequest();
        if (!$this->canCacheRequest($request)) {
            return;
        }

        $response = $this->storage->fetch($request);
        if ($response) {
            $request->getConfig()->set('cache_hit', 'ERROR');
            $event->intercept($response);
        }
    }

    protected function canCacheRequest(RequestInterface $request)
    {
        return !$request->getConfig()->get('cache.disable') && in_array($request->getMethod(), array('GET', 'HEAD', 'OPTIONS'));
    }

    protected function canCacheResponse(ResponseInterface $response)
    {
        $body = $response->getBody();

        return in_array($response->getStatusCode(), array(200, 203, 300, 301, 410)) &&
        $body && ($body->isReadable() || $body->isSeekable());
    }


    protected function cacheMiss(RequestInterface $request)
    {
        $request->getConfig()->set('cache_lookup', 'MISS');
    }

}
