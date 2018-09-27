<?php

namespace NodeLink\RememberMe\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Utility\Security;
use DateTime;

/**
 * Class CookieAuthenticate for Remember-me authentication.
 *
 * @package NodeLink\RememberMe\Auth
 */
class CookieAuthenticate extends BaseAuthenticate
{

    /**
     * Default config for this object.
     *
     * - `fields` The fields to use to identify a user by.
     * - `userModel` The alias for users table, defaults to Users.
     * - `finder` The finder method to use to fetch user record. Defaults to 'all'.
     *   You can set finder name as string or an array where key is finder name and value
     *   is an array passed to `Table::find()` options.
     *   E.g. ['finderName' => ['some_finder_option' => 'some_value']]
     * - Options `scope` and `contain` have been deprecated since 3.1. Use custom
     *   finder instead to modify the query to fetch user record.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'fields' => [
            'token' => 'remember_token',
        ],
        'userModel' => 'Users',
        'finder' => 'all',
        'scope' => [],
        'contain' => null,
    ];

    /**
     * Get table object for manipulating user from database.
     *
     * @return \Cake\ORM\Table
     */
    protected function getUsersTable()
    {
        return $this->getTableLocator()->get($this->getConfig('userModel'));
    }

    /**
     * Create cookie value from user and token.
     *
     * @param array $user
     * @param $token
     * @return string|array
     */
    protected function createCookieValue(array $user, $token)
    {
        return [$user[$this->getUsersTable()->getPrimaryKey()], $token];
    }

    /**
     * Convert the value of the cookie into the condition for fetching user from database.
     *
     * @param string|array $cookie
     * @return array
     */
    protected function convertCookieValueToConditions($cookie)
    {
        $table = $this->getUsersTable();

        list($primaryKey, $token) = $cookie;

        return [
            $table->aliasField($table->aliasField($table->getPrimaryKey())) => $primaryKey,
            $table->aliasField($this->getConfig('fields.token')) => $token,
        ];
    }

    /**
     * Find user information from database based on cookie and config.
     *
     * @param string|array $cookie
     * @return array
     */
    protected function findUser($cookie)
    {
        $table = $this->getUsersTable();

        $options = [
            'conditions' => $this->convertCookieValueToConditions($cookie),
        ];

        if ($this->getConfig('scope')) {
            $options['conditions'] = array_merge($options['conditions'], $this->getConfig('scope'));
        }

        if ($this->getConfig('contain')) {
            $options['contain'] = $this->getConfig('contain');
        }

        $finder = $this->getConfig('finder');
        if (is_array($finder)) {
            $options += current($finder);
            $finder = key($finder);
        }

        return $table->find($finder, $options)->first()->toArray();
    }

    /**
     * Update user's token in database.
     *
     * @param array $user
     * @param $token
     * @return \Cake\Database\StatementInterface
     */
    protected function updateUserToken(array $user, $token)
    {
        $table = $this->getUsersTable();

        $primaryKey = $table->getPrimaryKey();

        return $table->query()
            ->update()
            ->set([$this->getConfig('fields.token') => $token])
            ->where([$primaryKey => $user[$primaryKey]])
            ->execute();
    }

    /**
     * {@inheritDoc}
     */
    public function getUser(ServerRequest $request)
    {
        $cookie = $request->getCookie(Configure::read('RememberMe.cookie.name'));

        if (!$cookie) {
            return false;
        }

        $user = $this->findUser($cookie);

        if (!$user) {
            return false;
        }

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate(ServerRequest $request, Response $response)
    {
        return $this->getUser($request);
    }

    /**
     * Generate random string for token.
     *
     * @param int $length
     * @return bool|string
     */
    protected function createRandomString($length = 64)
    {
        if (method_exists(Security::class, 'randomString')) {
            return Security::randomString($length);
        }

        return substr(bin2hex(Security::randomBytes(ceil($length / 2))), 0, $length);
    }

    /**
     * Executes a series of processes for generating and saving token.
     *
     * @param Controller $controller
     * @param array $user
     */
    protected function createToken(Controller $controller, array $user)
    {
        if (!$controller->request->getData(Configure::read('RememberMe.field'))) {
            return;
        }

        if (isset($user['remember_token']) && $user['remember_token']) {
            $token = $user['remember_token'];
        } else {
            $token = $this->createRandomString();

            $this->updateUserToken($user, $token);
        }

        $cookie = new Cookie(
            Configure::read('RememberMe.cookie.name'),
            $this->createCookieValue($user, $token),
            new DateTime(Configure::read('RememberMe.cookie.expires')),
            Configure::read('RememberMe.cookie.path'),
            Configure::read('RememberMe.cookie.domain'),
            Configure::read('RememberMe.cookie.secure'),
            Configure::read('RememberMe.cookie.httpOnly')
        );

        $controller->response = $controller->response->withCookie($cookie);
    }

    /**
     * Executes a series of processes for deleting token.
     *
     * @param Controller $controller
     * @param array $user
     */
    protected function removeToken(Controller $controller, array $user)
    {
        $cookieName = Configure::read('RememberMe.cookie.name');

        $this->updateUserToken($user, null);

        if ($controller->request->getCookie($cookieName)) {
            $controller->response = $controller->response->withExpiredCookie($cookieName);
        }
    }

    /**
     * Fired after a user has been identified using one of configured authenticate class.
     *
     * @param Event $event
     * @param array $user
     */
    public function handleAfterIdentify(Event $event, array $user)
    {
        $this->createToken($event->getSubject()->getController(), $user);
    }

    /**
     * Fired when AuthComponent::logout() is called.
     *
     * @param Event $event
     * @param array $user
     */
    public function handleLogout(Event $event, array $user)
    {
        $this->removeToken($event->getSubject()->getController(), $user);
    }

    /**
     * {@inheritDoc}
     */
    public function implementedEvents()
    {
        return [
            'Auth.afterIdentify' => 'handleAfterIdentify',
            'Auth.logout' => 'handleLogout',
        ];
    }

}
