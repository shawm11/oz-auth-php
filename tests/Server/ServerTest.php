<?php

namespace Shawm11\Oz\Tests;

use PHPUnit\Framework\TestCase;
use Shawm11\Oz\Server\Server;
use Shawm11\Oz\Server\Ticket;
use Shawm11\Oz\Server\ServerException;
use Shawm11\Oz\Server\UnauthorizedException;
use Shawm11\Oz\Client\Client;
use Shawm11\Hawk\Utils\Utils as HawkUtils;

class ServerTest extends TestCase
{
    use \Codeception\Specify;
    use \Codeception\AssertThrows;

    protected $encryptionPassword = 'a_password_that_is_not_too_short_and_also_not_very_random_but_is_good_enough';
    protected $app = ['id' => '123'];

    public function testAuthenticate()
    {
        $this->describe('Server::authenticate()', function () {

            $this->it('throws an error on missing password', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid encryption password',
                    function() {
        	           (new Server)->authenticate(null, null);
                    }
                );
            });

            $this->it('authenticates a request', function () {
                $grant = [
                    'id' => 's81u29n1812',
                    'user' => '456',
                    'exp' => (new HawkUtils)->now() + 5000,
                    'scope' => ['a', 'b']
                ];
                $envelope = (new Ticket($this->encryptionPassword))->issue($this->app, $grant);
                $req = [
                    'host' => 'example.com',
                    'port' => 443,
                    'method' => 'POST',
                    'url' => '/oz/rsvp',
                    'authorization' => (new Client)->header(
                        'https://example.com/oz/rsvp',
                        'POST',
                        $envelope
                    )['header']
                ];

                expect((new Server)->authenticate($req, $this->encryptionPassword))->notEmpty();
            });

            $this->it('authenticates a request (hawk options)', function () {
                $grant = [
                    'id' => 's81u29n1812',
                    'user' => '456',
                    'exp' => (new HawkUtils)->now() + 5000,
                    'scope' => ['a', 'b']
                ];
                $envelope = (new Ticket($this->encryptionPassword))->issue($this->app, $grant);
                $req = [
                    'host' => 'example.com',
                    'port' => 443,
                    'method' => 'POST',
                    'url' => '/oz/rsvp',
                    'authorization' => (new Client)->header(
                        'https://example.com/oz/rsvp',
                        'POST',
                        $envelope
                    )['header']
                ];
                $options = [
                    'hawk' => [
                        'hostHeaderName' => 'hostx1'
                    ]
                ];

                expect((new Server)->authenticate($req, $this->encryptionPassword, true, $options))->notEmpty();
            });

            $this->it('fails to authenticate a request with bad password', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Bad HMAC value',
                    function() {
                        $grant = [
                            'id' => 's81u29n1812',
                            'user' => '456',
                            'exp' => (new HawkUtils)->now() + 5000,
                            'scope' => ['a', 'b']
                        ];
                        $envelope = (new Ticket($this->encryptionPassword))->issue($this->app, $grant);
                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $envelope
                            )['header']
                        ];
                        $options = [
                            'hawk' => [
                                'hostHeaderName' => 'hostx1'
                            ]
                        ];

                        (new Server)->authenticate(
                            $req,
                            'a_password_that_is_not_too_short_and_also_not_very_random_but_is_good_enough_x',
                            true,
                            $options
                        );
                    }
                );
            });

            $this->it('fails to authenticate a request with expired ticket', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Expired ticket',
                    function() {
                        $grant = [
                            'id' => 's81u29n1812',
                            'user' => '456',
                            'exp' => (new HawkUtils)->now() - 5000,
                            'scope' => ['a', 'b']
                        ];
                        $envelope = (new Ticket($this->encryptionPassword))->issue($this->app, $grant);
                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $envelope
                            )['header']
                        ];
                        $options = [
                            'hawk' => [
                                'hostHeaderName' => 'hostx1'
                            ]
                        ];

                        (new Server)->authenticate($req, $this->encryptionPassword, true, $options);
                    }
                );
            });

            $this->it('fails to authenticate a request with mismatching app ID', function () {
                $this->assertThrowsWithMessage(
                    UnauthorizedException::class,
                    'Mismatching application ID',
                    function() {
                        $grant = [
                            'id' => 's81u29n1812',
                            'user' => '456',
                            'exp' => (new HawkUtils)->now() + 5000,
                            'scope' => ['a', 'b']
                        ];

                        $envelope = (new Ticket($this->encryptionPassword))->issue($this->app, $grant);
                        $envelope['app'] = '567';

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $envelope
                            )['header']
                        ];
                        $options = [
                            'hawk' => [
                                'hostHeaderName' => 'hostx1'
                            ]
                        ];

                        (new Server)->authenticate($req, $this->encryptionPassword, true, $options);
                    }
                );
            });

            $this->it('fails to authenticate a request with mismatching dlg ID', function () {
                $this->assertThrowsWithMessage(
                    UnauthorizedException::class,
                    'Mismatching delegated application ID',
                    function() {
                        $grant = [
                            'id' => 's81u29n1812',
                            'user' => '456',
                            'exp' => (new HawkUtils)->now() + 5000,
                            'scope' => ['a', 'b']
                        ];

                        $envelope = (new Ticket($this->encryptionPassword))->issue($this->app, $grant);
                        $envelope['dlg'] = '567';

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $envelope
                            )['header']
                        ];
                        $options = [
                            'hawk' => [
                                'hostHeaderName' => 'hostx1'
                            ]
                        ];

                        (new Server)->authenticate($req, $this->encryptionPassword, true, $options);
                    }
                );
            });
        });
    }
}
