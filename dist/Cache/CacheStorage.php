<?php

namespace Csa\Bundle\GuzzleBundle\Cache;

use Doctrine\Common\Cache\Cache;
use GuzzleHttp\Message\MessageInterface;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\StreamInterface;
use GuzzleHttp\Stream;

class CacheStorage
{
    /** @var string */
    protected  $keyPrefix;

    /** @var Cache */
    protected $cache;
    /**
     * @param Cache  $cache     Cache backend.
     * @param string $keyPrefix Key prefix to add to each key.
     */
    public function __construct(Cache $cache, $keyPrefix = null)
    {
        $this->cache = $cache;
        $this->keyPrefix = $keyPrefix;
    }

    public function save(RequestInterface $request, ResponseInterface $response)
    {
        $ttl = (int) $request->getConfig()->get('cache.ttl');
        $key = $this->getCacheKey($request);
        // Persist the response body if needed
        if ($response->getBody() && $response->getBody()->getSize() > 0) {
            $this->cache->save($key, array(
                'code' => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'body' => (string) $response->getBody(),
            ), $ttl);
            $request->getConfig()->set('cache.key', $key);
        }
    }

    public function fetch(RequestInterface $request)
    {
        $key = $this->getCacheKey($request);
        $entry = $this->cache->fetch($key);
        if (!$entry) {
            return null;
        }

        $response = new Response($entry['code'], $entry['headers'], Stream\Utils::create($entry['body']));
        $request->getConfig()->set('cache.key', $key);

        return $response;
    }

    public function contains(RequestInterface $request)
    {
        $key = $this->getCacheKey($request);
        return $this->cache->contains($key);
    }

    /**
     * get cache key for a request
     *
     */
    protected function getCacheKey(RequestInterface $request)
    {
        return $this->keyPrefix
        .'_'.str_replace([':', '/', '?', '&&'], '_', $request->getUrl()).'_'
        .md5($request->getMethod() . ' ' . $request->getUrl());
    }
} 