<?php

require_once __DIR__.'/../vendor/autoload.php';

if (!file_exists('../config.php')) {
    echo 'Missing "config.php" file';
    die;
}

const ACCESS_TOKEN = 'fb_access_token';

$app = new Silex\Application();
$app['config'] = require_once __DIR__ . '/../config.php';

$app['debug'] = true;

$app['session.storage.options'] = ['name' => 'PHPSESSID'];

$app->register(new Silex\Provider\SessionServiceProvider());

$app['session']->start();

$app['fb'] = new Facebook\Facebook($app['config']['facebook']);

$app['user_list'] = new UsersList($app['fb']);

$app->get('/', function() use($app) {
    /** @var Facebook\Facebook $fb */
    $fb = $app['fb'];

    $accessToken = $app['session']->get(ACCESS_TOKEN);
    if (!$accessToken) {
        return sprintf(
            'You have to <a href="%s">login</a> first',
            $fb->getRedirectLoginHelper()->getLoginUrl('http://localhost:8080/login', ['user_events'])
        );
    }

    return implode('<br />', $app['user_list']->get($app['config']['event_id'], $accessToken));
});

$app->get('/login', function() use ($app) {
    /** @var Facebook\Facebook $fb */
    $fb = $app['fb'];

    $helper = $fb->getRedirectLoginHelper();
    try {
        $accessToken = $helper->getAccessToken();
        if ($accessToken) {
            $app['session']->set(ACCESS_TOKEN, (string) $accessToken);

            return $app->redirect('/');
        }

        return 'You probably deny application permissions';
    } catch(Facebook\Exceptions\FacebookSDKException $e) {
        return $e->getMessage();
    }
});

$app->run();
