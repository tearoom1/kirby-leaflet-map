<?php

namespace TearoomOne\LeafletMap\Tests;

/**
 * Runs tests/fixtures/mock-server.php with PHP's built-in server.
 */
trait MockServer
{
    private static $server;
    protected static string $mockUrl = '';
    protected static string $mockLog = '';

    public static function setUpBeforeClass(): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int)substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);

        self::$mockLog = sys_get_temp_dir() . '/leaflet-map-mock-' . $port . '.log';
        self::$mockUrl = 'http://127.0.0.1:' . $port;

        self::$server = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . $port, __DIR__ . '/fixtures/mock-server.php'],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
            null,
            ['MOCK_LOG' => self::$mockLog]
        );

        for ($i = 0; $i < 50; $i++) {
            if ($connection = @fsockopen('127.0.0.1', $port)) {
                fclose($connection);
                return;
            }
            usleep(100000);
        }

        self::fail('Mock server did not start');
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }

        @unlink(self::$mockLog);
    }

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(self::$mockLog, '');
    }

    /**
     * @return string[] requested URIs since the test started
     */
    protected function mockRequests(): array
    {
        return array_values(array_filter(explode("\n", (string)file_get_contents(self::$mockLog))));
    }
}
