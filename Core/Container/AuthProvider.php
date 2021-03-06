<?php

namespace Core\Container;

use Core\Component\Auth\Auth;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class AuthProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container['auth'] = function ($c) {
            return new Auth($c['config']);
        };
    }
} 