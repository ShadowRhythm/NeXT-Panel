<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Controllers\ServerAPI\UserController;
use App\Models\Node;
use App\Models\OnlineLog;
use App\Models\User;
use App\Services\GlobalIpLimit;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Database\Capsule\Manager;
use PHPUnit\Framework\TestCase;
use Slim\Http\Factory\DecoratedResponseFactory;
use Slim\Http\Factory\DecoratedServerRequestFactory;
use function array_key_exists;
use function json_decode;

final class GlobalIpLimitTest extends TestCase
{
    private Manager $database;

    protected function setUp(): void
    {
        $this->database = new Manager();
        $this->database->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $this->database->setAsGlobal();
        $this->database->bootEloquent();

        $schema = $this->database->schema();

        $schema->create('config', static function ($table): void {
            $table->increments('id');
            $table->string('item');
            $table->text('value');
            $table->string('type')->default('string');
        });

        $schema->create('user', static function ($table): void {
            $table->increments('id');
            $table->integer('class')->default(0);
            $table->integer('node_group')->default(0);
            $table->integer('is_banned')->default(0);
            $table->integer('is_admin')->default(0);
            $table->integer('u')->default(0);
            $table->integer('d')->default(0);
            $table->integer('transfer_enable')->default(10000);
            $table->integer('node_speedlimit')->default(0);
            $table->integer('node_iplimit')->default(0);
            $table->string('passwd')->default('pass');
            $table->string('uuid')->default('uuid-1');
        });

        $schema->create('online_log', static function ($table): void {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('ip');
            $table->integer('node_id');
            $table->integer('first_time');
            $table->integer('session_time')->default(0);
            $table->integer('last_time');
            $table->integer('kicked_at')->default(0);
            $table->unique(['user_id', 'ip']);
        });
    }

    public function testEnforceEvictsOldestGlobalIPQueuesKickAndPreventsBounce(): void
    {
        User::query()->insert([
            'id' => 10,
            'node_iplimit' => 1,
        ]);

        $redis = new InMemoryRedisStub();
        $now = 1_700_000_100;

        // Node 1 reports IP1 first.
        GlobalIpLimit::recordAndEnforce(1, [(object) ['user_id' => 10, 'ip' => '1.1.1.1']], $redis, $now);

        self::assertSame([], GlobalIpLimit::drainKickIps(1, $redis));
        self::assertSame(1, OnlineLog::where('user_id', 10)->where('kicked_at', 0)->count());

        // 10s later, Node 2 reports IP2: global limit=1 marks IP1 kicked_at (without deleting row) and queues kick for Node 1.
        GlobalIpLimit::recordAndEnforce(2, [(object) ['user_id' => 10, 'ip' => '2.2.2.2']], $redis, $now + 10);

        $remaining = OnlineLog::where('user_id', 10)->where('kicked_at', 0)->get();
        self::assertCount(1, $remaining);
        self::assertSame('2.2.2.2', $remaining[0]->ip());

        $kickedRow = OnlineLog::where('user_id', 10)->where('ip', '::ffff:1.1.1.1')->first();
        self::assertNotNull($kickedRow);
        self::assertSame($now + 10, (int) $kickedRow->kicked_at);

        $node1Kicks = GlobalIpLimit::drainKickIps(1, $redis);
        self::assertSame([['user_id' => 10, 'ip' => '1.1.1.1']], $node1Kicks);
        // Draining clears the queue.
        self::assertSame([], GlobalIpLimit::drainKickIps(1, $redis));

        // Stale in-flight report from Node 1 during cooldown must not re-insert IP1, kick IP2, or re-queue Node 1.
        GlobalIpLimit::recordAndEnforce(1, [(object) ['user_id' => 10, 'ip' => '1.1.1.1']], $redis, $now + 15);
        self::assertSame([], GlobalIpLimit::drainKickIps(1, $redis));
        self::assertSame([], GlobalIpLimit::drainKickIps(2, $redis));
        $remainingAfterStale = OnlineLog::where('user_id', 10)->where('kicked_at', 0)->get();
        self::assertCount(1, $remainingAfterStale);
        self::assertSame('2.2.2.2', $remainingAfterStale[0]->ip());

        // Hopping cooled-down IP1 onto Node 3 must not enter online_log and must queue a kick on Node 3.
        GlobalIpLimit::recordAndEnforce(3, [(object) ['user_id' => 10, 'ip' => '1.1.1.1']], $redis, $now + 20);
        self::assertSame([['user_id' => 10, 'ip' => '1.1.1.1']], GlobalIpLimit::drainKickIps(3, $redis));
        self::assertSame(1, OnlineLog::where('user_id', 10)->where('kicked_at', 0)->count());
    }

    public function testReturningDormantIpPreservesFirstTimeAndResetsSessionTime(): void
    {
        User::query()->insert([
            'id' => 20,
            'node_iplimit' => 1,
        ]);

        $redis = new InMemoryRedisStub();
        $morning = 1_700_000_000;
        $evening = $morning + 3600;

        // Dormant morning record for 1.1.1.1.
        GlobalIpLimit::recordAndEnforce(1, [(object) ['user_id' => 20, 'ip' => '1.1.1.1']], $redis, $morning);
        // Work IP 2.2.2.2 active recently in evening.
        GlobalIpLimit::recordAndEnforce(2, [(object) ['user_id' => 20, 'ip' => '2.2.2.2']], $redis, $evening - 30);

        // Evening home IP 1.1.1.1 returns after >90s: first_time stays $morning, session_time resets to $evening and kicks 2.2.2.2.
        GlobalIpLimit::recordAndEnforce(1, [(object) ['user_id' => 20, 'ip' => '1.1.1.1']], $redis, $evening);

        $remaining = OnlineLog::where('user_id', 20)->where('kicked_at', 0)->get();
        self::assertCount(1, $remaining);
        self::assertSame('1.1.1.1', $remaining[0]->ip());
        self::assertSame($morning, (int) $remaining[0]->first_time);
        self::assertSame($evening, (int) $remaining[0]->session_time);
        self::assertSame([['user_id' => 20, 'ip' => '2.2.2.2']], GlobalIpLimit::drainKickIps(2, $redis));
    }

    public function testServerApiUsersIncludesNodeIpLimitWithoutDroppingOverLimitUsers(): void
    {
        User::query()->insert([
            'id' => 30,
            'class' => 1,
            'node_group' => 0,
            'is_banned' => 0,
            'is_admin' => 0,
            'u' => 0,
            'd' => 0,
            'transfer_enable' => 10000,
            'node_speedlimit' => 50,
            'node_iplimit' => 2,
            'uuid' => 'uuid-30',
        ]);

        // Even with >2 active online_log rows, /api/server/v1/users still serves user with node_iplimit.
        $now = time();
        OnlineLog::recordBatch(1, [
            (object) ['user_id' => 30, 'ip' => '1.1.1.1'],
            (object) ['user_id' => 30, 'ip' => '1.1.1.2'],
            (object) ['user_id' => 30, 'ip' => '1.1.1.3'],
        ], $now);

        $node = new Node();
        $node->id = 1;
        $node->sort = 14;
        $node->node_class = 1;
        $node->node_group = 0;
        $node->node_bandwidth_limit = 0;
        $node->node_bandwidth = 0;

        $httpFactory = new HttpFactory();
        $request = (new DecoratedServerRequestFactory($httpFactory, $httpFactory))
            ->createServerRequest('GET', '/api/server/v1/users')
            ->withAttribute('node', $node);
        $response = (new DecoratedResponseFactory($httpFactory, $httpFactory))->createResponse();

        $controller = new UserController();
        $result = $controller->index($request, $response, []);
        $payload = json_decode((string) $result->getBody(), true);

        self::assertSame(200, $result->getStatusCode());
        self::assertCount(1, $payload['data']);
        self::assertSame(30, $payload['data'][0]['id']);
        self::assertSame(2, $payload['data'][0]['node_iplimit']);
    }

    public function testExplicitKickNodeIpQueuesHeartbeatAndBlocksStaleReinsert(): void
    {
        User::query()->insert([
            'id' => 40,
            'node_iplimit' => 2,
        ]);

        $redis = new InMemoryRedisStub();
        $now = 1_700_000_500;

        GlobalIpLimit::recordAndEnforce(3, [(object) ['user_id' => 40, 'ip' => '5.5.5.5']], $redis, $now);
        OnlineLog::where('user_id', 40)->update(['kicked_at' => $now]);

        GlobalIpLimit::kickNodeIp(3, 40, '::ffff:5.5.5.5', $redis);
        self::assertSame([['user_id' => 40, 'ip' => '5.5.5.5']], GlobalIpLimit::drainKickIps(3, $redis));

        // Stale report from node 3 right after explicit kick must be ignored during cooldown.
        GlobalIpLimit::recordAndEnforce(3, [(object) ['user_id' => 40, 'ip' => '5.5.5.5']], $redis, $now + 5);
        self::assertSame(0, OnlineLog::where('user_id', 40)->where('kicked_at', 0)->count());
    }
}

final class InMemoryRedisStub
{
    /** @var array<string, string> */
    private array $strings = [];

    /** @var array<string, array<string, string>> */
    private array $hashes = [];

    /**
     * @param array<int, string> $keys
     *
     * @return array<int, string|false>
     */
    public function mGet(array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $out[] = array_key_exists($key, $this->strings) ? $this->strings[$key] : false;
        }
        return $out;
    }

    public function setex(string $key, int $ttl, string $value): bool
    {
        $this->strings[$key] = $value;
        return true;
    }

    /**
     * @param array<string, string> $fields
     */
    public function hMSet(string $key, array $fields): bool
    {
        foreach ($fields as $field => $value) {
            $this->hashes[$key][$field] = $value;
        }
        return true;
    }

    public function expire(string $key, int $ttl): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function hGetAll(string $key): array
    {
        return $this->hashes[$key] ?? [];
    }

    public function del(string $key): int
    {
        unset($this->hashes[$key], $this->strings[$key]);
        return 1;
    }
}
