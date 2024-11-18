<?php

namespace Shawm11\Oz\Tests\Client;

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

    /** @var array */
    protected $app = [
        'id' => 'social',
        'scope' => ['a', 'b', 'c'],
        'key' => 'werxhqb98rpaxn39848xrunpaw3489ruxnpa98w4rxn',
        'algorithm' => 'sha256'
    ];
    /** @var array */
    protected $user = [
        'username' => 'user',
        'password' => 'password'
    ];
    /** @var array */
    protected $endpointSettings;
    /** @var \Mockery\LegacyMockInterface */
    protected $httpRequestMock;

    public function setUp(): void {
        $this->endpointSettings = [
            'encryptionPassword' => 'passwordpasswordpasswordpasswordpasswordpasswordpasswordpasswordpasswordpassword',
            'loadAppFunc' => function ($id) {
                return $this->app;
            },
            'verifyUserFunc' => function ($id) {
                return $this->user['username'];
            },
            'ticket' => [
                'ttl' => 10 * 60 * 1000
            ],
            'grant' => [
                'exp' => (new \Shawm11\Hawk\Utils\Utils)->now() + 60000
            ],
            'storeGrantFunc' => function ($grantObj) {
                return 'some_unique_grant_id_generated_by_the_server';
            }
        ];
    }

    public function testRequest(): void
    {
        $this->describe('Connection::request()', function () {
            $this->beforeSpecify(function() {
            	$this->httpRequestMock = \Mockery::mock('overload:' . \Httpful\Request::class);
            });

            $this->afterSpecify(function() {
            	\Mockery::close();
            });

            $this->it('requests resource using ticket', function () {
                $this->mockRequest($this->fakeHttpResponse(['foo' => 'bar']));
                $appTicket = (new Endpoints)->app( // Create app ticket
                    [
                        'method' => 'POST',
                        'url' => '/oz/reissue',
                        'host' => 'example.com',
                        'port' => 443,
                        'authorization' => (new Client)->header(
                            "https://example.com/oz/reissue",
                            'POST',
                            $this->app
                        )['header']
                    ],
                    $this->endpointSettings
                );
                $connectionSettings = ['uri' => 'https://example.com', 'credentials' => $this->app];
                $response = (new Connection($connectionSettings))->request('/test', $appTicket);

                expect($response['result']['foo'])->toEqual('bar');
                expect($response['code'])->toEqual(200);
                expect($response['ticket'])->toEqual($appTicket);
            });
        });
    }

    public function testApp(): void
    {
        $this->describe('Connection::app()', function () {
            $this->beforeSpecify(function() {
            	$this->httpRequestMock = \Mockery::mock('overload:' . \Httpful\Request::class);
            });

            $this->afterSpecify(function() {
            	\Mockery::close();
            });

            $this->it('obtains an application ticket and requests resource', function () {
                $this->mockRequest($this->fakeOzResponse('/oz/app'));

                $connectionSettings = ['uri' => 'https://example.com', 'credentials' => $this->app];
                $response = (new Connection($connectionSettings))->app('/');

                expect($response['result']['id'])->notToBeEmpty();
                expect($response['code'])->toEqual(200);
                expect($response['ticket'])->notToBeEmpty();
            });

            $this->it('errors on invalid app response', function () {
                $this->assertThrowsWithMessage(
                    ClientException::class,
                    'Client registration failed with unexpected response',
                    function () {
                        $this->mockRequest($this->fakeOzResponse('/oz/app', 400));

                        $connectionSettings = ['uri' => 'https://example.com', 'credentials' => $this->app];
                        (new Connection($connectionSettings))->app('/');
                    }
                );
            });
        });
    }

    public function testReissue(): void
    {
        $this->describe('Connection::reissue()', function () {
            $this->beforeSpecify(function() {
            	$this->httpRequestMock = \Mockery::mock('overload:' . \Httpful\Request::class);
            });

            $this->afterSpecify(function() {
            	\Mockery::close();
            });

            // Create app ticket
            $appTicket = (new Endpoints)->app(
                [
                    'method' => 'POST',
                    'url' => '/oz/reissue',
                    'host' => 'example.com',
                    'port' => 443,
                    'authorization' => (new Client)->header(
                        "https://example.com/oz/reissue",
                        'POST',
                        $this->app
                    )['header']
                ],
                $this->endpointSettings
            );

            $this->it('obtains the reissued ticket', function () use ($appTicket) {
                $this->mockRequest($this->fakeOzResponse('/oz/reissue', 200, $appTicket));

                $connectionSettings = ['uri' => 'https://example.com', 'credentials' => $this->app];
                $response = (new Connection($connectionSettings))->reissue($appTicket);

                expect($response['id'])->notToBeEmpty();
            });

            $this->it('errors on invalid reissue response', function () use ($appTicket) {
                $this->assertThrowsWithMessage(ClientException::class, 'some error', function () use ($appTicket) {
                        $this->mockRequest($this->fakeOzResponse('/oz/reissue', 400, $appTicket));

                        $connectionSettings = ['uri' => 'https://example.com', 'credentials' => $this->app];
                        (new Connection($connectionSettings))->reissue($appTicket);
                    }
                );
            });
        });
    }

    public function testRequestAppTicket(): void
    {
        $this->describe('Connection::requestAppTicket()', function () {
            $this->beforeSpecify(function() {
            	$this->httpRequestMock = \Mockery::mock('overload:' . \Httpful\Request::class);
            });

            $this->afterSpecify(function() {
            	\Mockery::close();
            });

            $this->it('obtains an application ticket', function () {
                $this->mockRequest($this->fakeOzResponse('/oz/app'));

                $connectionSettings = ['uri' => 'https://example.com', 'credentials' => $this->app];
                $response = (new Connection($connectionSettings))->requestAppTicket();

                expect($response['result']['id'])->notToBeEmpty();
                expect($response['code'])->toEqual(200);
            });

            $this->it('errors on invalid app response', function () {
                $this->mockRequest($this->fakeOzResponse('/oz/app', 400));

                $connectionSettings = ['uri' => 'https://example.com', 'credentials' => $this->app];
                $response = (new Connection($connectionSettings))->requestAppTicket();

                expect($response['result']['message'])->toEqual('some error');
                expect($response['code'])->toEqual(400);
            });
        });
    }

    public function testRequestUserTicket(): void
    {
        $this->describe('Connection::requestUserTicket()', function () {
            $this->beforeSpecify(function() {
            	$this->httpRequestMock = \Mockery::mock('overload:' . \Httpful\Request::class);
            });

            $this->afterSpecify(function() {
            	\Mockery::close();
            });

            // Create app ticket
            $appTicket = (new Endpoints)->app(
                [
                    'method' => 'POST',
                    'url' => '/oz/user',
                    'host' => 'example.com',
                    'port' => 443,
                    'authorization' => (new Client)->header(
                        "https://example.com/oz/user",
                        'POST',
                        $this->app
                    )['header']
                ],
                $this->endpointSettings
            );

            $this->it('obtains an user ticket using User Credentials workflow', function () use ($appTicket) {
                $this->mockRequest($this->fakeOzResponse('/oz/user', 200, $appTicket, $this->user));

                $connectionSettings = ['uri' => 'https://example.com', 'credentials' => $this->app];
                $response = (new Connection($connectionSettings))->requestUserTicket($this->user);

                expect($response['result']['id'])->notToBeEmpty();
                expect($response['code'])->toEqual(200);
            });

            $this->it('obtains an user ticket using Implicit workflow', function () {
                $this->mockRequest($this->fakeOzResponse('/oz/user', 200, [], $this->user, true));

                $connectionSettings = ['uri' => 'https://example.com'];
                $response = (new Connection($connectionSettings))->requestUserTicket($this->user);

                expect($response['result']['id'])->notToBeEmpty();
                expect($response['code'])->toEqual(200);
            });

            $this->it('errors on invalid user response', function () {
                $this->mockRequest($this->fakeOzResponse('/oz/user', 400, [], $this->user, true));

                $connectionSettings = ['uri' => 'https://example.com'];
                $response = (new Connection($connectionSettings))->requestUserTicket($this->user);

                expect($response['result']['message'])->toEqual('some error');
                expect($response['code'])->toEqual(400);
            });
        });
    }

    /**
     * @param  \Httpful\Response|null  $httpResponse
     * @return void
     */
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

    /**
     * @param  string  $path
     * @param  integer  $statusCode
     * @param  array|null  $appCredentials
     * @param  array|null  $userCredentials
     * @param  boolean  $forceImplicitFlow
     *
     * @return \Httpful\Response
     */
    protected function fakeOzResponse(
        $path,
        $statusCode = 200,
        $appCredentials = null,
        $userCredentials = [],
        $forceImplicitFlow = false
    ) {
        $appCredentials = $appCredentials ? $appCredentials : $this->app;

        $req = [
            'method' => 'POST',
            'url' => $path,
            'host' => 'example.com',
            'port' => 443,
            'authorization' => (new Client)->header(
                "https://example.com{$path}",
                'POST',
                $appCredentials
            )['header']
        ];

        $responseBody = null;

        switch ($path) {
            case '/oz/app':
                $responseBody = (new Endpoints)->app($req, $this->endpointSettings);
                break;
            case '/oz/reissue':
                // @phpstan-ignore argument.type
                $responseBody = (new Endpoints)->reissue($req, null, $this->endpointSettings);
                break;
            case '/oz/user':
                if ($forceImplicitFlow) {
                    unset($req['authorization']);
                }

                $responseBody = (new Endpoints)->user($req, ['user' => $userCredentials], $this->endpointSettings);
                break;
            default:
                break;
        }


        return $this->fakeHttpResponse(
            $statusCode === 200 ? $responseBody : ['message' => 'some error'],
            $statusCode
        );
    }


    /**
     * @param  mixed  $responseBody
     * @param  integer  $statusCode
     *
     * @return \Httpful\Response
     */
    protected function fakeHttpResponse($responseBody, $statusCode = 200)
    {
        // @phpstan-ignore new.protectedConstructor
        $requestObj = new \Httpful\Request; // Class is replaced by mock in tests
        $requestObj->auto_parse = false;

        $fakeOzResponse = new \Httpful\Response('', "HTTP/1.1 {$statusCode}", $requestObj);

        $fakeOzResponse->code = $statusCode;
        $fakeOzResponse->body = $responseBody;

        return $fakeOzResponse;
    }
}
