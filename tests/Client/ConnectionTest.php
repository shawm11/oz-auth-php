<?php

namespace Shawm11\Oz\Tests;

use PHPUnit\Framework\TestCase;
use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Shawm11\Oz\Client\Connection;
use Shawm11\Oz\Client\Client;
use Shawm11\Oz\Client\ClientException;
use Shawm11\Oz\Server\Endpoints;

class ConnectionTest extends TestCase
{
    use \Codeception\Specify;
    use \Codeception\AssertThrows;
    use MockeryPHPUnitIntegration;

    protected $app = [
        'id' => 'social',
        'scope' => ['a', 'b', 'c'],
        'key' => 'werxhqb98rpaxn39848xrunpaw3489ruxnpa98w4rxn',
        'algorithm' => 'sha256'
    ];
    protected $settings;
    protected $httpRequestMock;

    public function setUp() {
        $this->settings = [
            'encryptionPassword' => 'passwordpasswordpasswordpasswordpasswordpasswordpasswordpasswordpasswordpassword',
            'loadAppFunc' => function ($id) {
                return $this->app;
            },
            'ticket' => [
                'ttl' => 10 * 60 * 1000
            ]
        ];
    }

    public function testConnection()
    {
        $this->describe('Connection Class', function () {
            $this->beforeSpecify(function() {
            	$this->httpRequestMock = \Mockery::mock('overload:' . \Httpful\Request::class);
            });

            $this->afterSpecify(function() {
            	\Mockery::close();
            });

            $this->it('obtains an application ticket and requests resource', function () {
                $this->mockRequest($this->fakeOzResponse('app', '/oz/app'));

                $options = ['uri' => 'https://example.com', 'credentials' => $this->app];
                $appResponse = (new Connection($options))->app('/');

                expect($appResponse['result']['id'])->notEmpty();
                expect($appResponse['code'])->equals(200);
                expect($appResponse['ticket'])->notEmpty();
            });

            $this->it('errors on invalid app response', function () {
                $this->assertThrowsWithMessage(
                    ClientException::class,
                    'Client registration failed with unexpected response',
                    function () {
                        $this->mockRequest($this->fakeOzResponse('app', '/oz/app', 400));

                        $options = ['uri' => 'https://example.com', 'credentials' => $this->app];
                        $appResponse = (new Connection($options))->app('/');
                    }
                );
            });
        });
    }

    protected function mockRequest($httpResponse = null)
    {
        $this->httpRequestMock->shouldReceive('init')->andReturn($this->httpRequestMock);
        $this->httpRequestMock->shouldReceive('method')->andReturn($this->httpRequestMock);
        $this->httpRequestMock->shouldReceive('uri')->andReturn($this->httpRequestMock);
        $this->httpRequestMock->shouldReceive('addHeaders')->andReturn($this->httpRequestMock);
        $this->httpRequestMock->shouldReceive('body')->andReturn($this->httpRequestMock);
        $this->httpRequestMock->shouldReceive('autoParse')->andReturn($this->httpRequestMock);
        $this->httpRequestMock->shouldReceive('send')->andReturn($httpResponse);
    }

    protected function fakeOzResponse($endpoint, $path, $statusCode = 200, $credentials = null)
    {
        $credentials = $credentials ? $credentials : $this->app;

        $req = [
            'method' => 'POST',
            'url' => $path,
            'host' => 'example.com',
            'port' => 443,
            'authorization' => (new Client)->header(
                "https://example.com$path",
                'POST',
                $credentials
            )['header']
        ];

        if ($endpoint === 'app') {
            $payload = (new Endpoints)->$endpoint($req, $this->settings);
        } else {
            $payload = (new Endpoints)->$endpoint($req, null, $this->settings);
        }

        $requestObj = new \Httpful\Request;
        $requestObj->auto_parse = false;

        $fakeOzResponse = new \Httpful\Response('', "HTTP/1.1 $statusCode", $requestObj);

        $fakeOzResponse->code = $statusCode;
        $fakeOzResponse->body = json_encode($payload);

        return $fakeOzResponse;
    }
}
