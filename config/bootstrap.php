<?php

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventManager;
use Cake\Http\Middleware\EncryptedCookieMiddleware;
use Cake\Http\MiddlewareQueue;

try {
    Configure::load('NodeLink/RememberMe.app', 'default', true);
} catch (\Exception $e) {
    exit($e->getMessage() . "\n");
}

EventManager::instance()->on(
    'Server.buildMiddleware',
    function (Event $event, MiddlewareQueue $middlewareQueue) {
        $middlewareQueue->add(new EncryptedCookieMiddleware(
            [Configure::read('RememberMe.cookie.name')],
            Configure::read('Security.cookieKey')
        ));
    });
