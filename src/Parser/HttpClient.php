<?php

namespace Drupal\ParseComposer\Parser;

use Guzzle\Http\Client as BaseClient;
use Guzzle\Plugin\Backoff\BackoffPlugin;

class HttpClient extends BaseClient
{
    private static $client;

    static function goGet($url)
    {
        if (null === static::$client) {
            static::$client = new parent();
            static::$client
              ->addSubscriber(BackoffPlugin::getExponentialBackoff());
        }
        return static::$client->get($url)
          ->send()
          ->getBody(TRUE);
    }
}

