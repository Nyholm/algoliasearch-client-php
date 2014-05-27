<?php

include __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../algoliasearch.php';
require_once __DIR__ . '/helper.php';

class SecurityTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->client = new \AlgoliaSearch\Client(getenv('ALGOLIA_APPLICATION_ID'), getenv('ALGOLIA_API_KEY'));
        $this->index = $this->client->initIndex(safe_name('àlgol?à-php'));
        try {
            $this->index->clearIndex();
        } catch (AlgoliaSearch\AlgoliaException $e) {
            // not fatal
        }
    }

    public function tearDown()
    {
        try {
            $this->client->deleteIndex(safe_name('àlgol?à-php'));
        } catch (AlgoliaSearch\AlgoliaException $e) {
            // not fatal
        }

    }

    public function testSecurityIndex()
    {
        $res = $this->index->addObject(array("firstname" => "Robin"));
        $this->index->waitTask($res['taskID']);
        $res = $this->index->listUserKeys();
        $newKey = $this->index->addUserKey(['search']);
        sleep(1);
        $this->assertTrue($newKey['key'] != "");
        $resAfter = $this->index->listUserKeys();
        $this->assertTrue(containsValue($resAfter["keys"], "value", $newKey['key']));
        $this->assertFalse(containsValue($res["keys"], "value", $newKey['key']));
        $key = $this->index->getUserKeyACL($newKey['key']);
        $this->assertEquals($key['acl'][0], 'search');
        $task = $this->index->deleteUserKey($newKey['key']);
        sleep(1);
        $resEnd = $this->index->listUserKeys();
        $this->assertFalse(containsValue($resEnd["keys"], "value", $newKey['key']));

        $res = $this->client->listUserKeys();
        $newKey = $this->client->addUserKey(['search']);
        sleep(1);
        $this->assertTrue($newKey['key'] != "");
        $resAfter = $this->client->listUserKeys();
        $this->assertTrue(containsValue($resAfter["keys"], "value", $newKey['key']));
        $this->assertFalse(containsValue($res["keys"], "value", $newKey['key']));
        $key = $this->client->getUserKeyACL($newKey['key']);
        $this->assertEquals($key['acl'][0], 'search');
        $task = $this->client->deleteUserKey($newKey['key']);
        sleep(1);
        $resEnd = $this->client->listUserKeys();
        $this->assertFalse(containsValue($resEnd["keys"], "value", $newKey['key']));
    }

    public function testSecuredApiKeys()
    {
        $this->assertEquals('1fd74b206c64fb49fdcd7a5f3004356cd3bdc9d9aba8733656443e64daafc417', hash_hmac('sha256', '(public,user1)', 'my_api_key'));
        $key = $this->client->generateSecuredApiKey('my_api_key', '(public,user1)');
        $this->assertEquals($key, hash_hmac('sha256', '(public,user1)', 'my_api_key'));
        $key = $this->client->generateSecuredApiKey('my_api_key', '(public,user1)', 42);
        $this->assertEquals($key, hash_hmac('sha256', '(public,user1)42', 'my_api_key'));
        $key = $this->client->generateSecuredApiKey('my_api_key', array('public'));
        $this->assertEquals($key, hash_hmac('sha256', 'public', 'my_api_key'));
        $key = $this->client->generateSecuredApiKey('my_api_key', array('public', array('premium','vip')));
        $this->assertEquals($key, hash_hmac('sha256', 'public,(premium,vip)', 'my_api_key'));
    }

    private $client;
    private $index;
}
