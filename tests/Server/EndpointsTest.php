<?php

namespace Shawm11\Oz\Tests\Server;

use PHPUnit\Framework\TestCase;
use Shawm11\Oz\Server\Endpoints;
use Shawm11\Oz\Server\Ticket;
use Shawm11\Oz\Server\ServerException;
use Shawm11\Oz\Server\UnauthorizedException;
use Shawm11\Oz\Server\ForbiddenException;
use Shawm11\Oz\Server\BadRequestException;
use Shawm11\Oz\Client\Client;
use Shawm11\Hawk\Utils\Utils as HawkUtils;
use Shawm11\Iron\IronOptions;

class EndpointsTest extends TestCase
{
    use \Codeception\Specify;
    use \Codeception\AssertThrows;

    /** @var array */
    protected $apps = [
        'social' => [
            'id' => 'social',
            'scope' => ['a', 'b', 'c'],
            'key' => 'werxhqb98rpaxn39848xrunpaw3489ruxnpa98w4rxn',
            'algorithm' => 'sha256'
        ],
        'network' => [
            'id' => 'network',
            'scope' => ['b', 's'],
            'key' => 'witf745itwn7ey4otnw7eyi4t7syeir7bytise7rbyi',
            'algorithm' => 'sha256'
        ]
    ];

    /** @var string */
    protected $encryptionPassword = 'a_password_that_is_not_too_short_and_also_not_very_random_but_is_good_enough';

    /** @var array|null */
    protected $appTicket = null;
    /** @var array */
    protected $req;
    /** @var array */
    protected $options;

    public function setUp(): void
    {
        $this->req = [
            'host' => 'example.com',
            'port' => 443,
            'method' => 'POST',
            'url' => '/oz/app',
            'authorization' => (new Client)->header(
                'https://example.com/oz/app',
                'POST',
                $this->apps['social']
            )['header']
        ];
        $this->options = [
            'encryptionPassword' => $this->encryptionPassword,
            'loadAppFunc' => function ($id) {
                return $this->apps[$id];
            }
        ];

        $this->appTicket = (new Endpoints)->app($this->req, $this->options);
    }

    public function testApp(): void
    {
        $this->describe('Endpoints::app()', function () {

            $this->it('overrides defaults', function () {
                $req = [
                    'host' => 'example.com',
                    'port' => 443,
                    'method' => 'POST',
                    'url' => '/oz/app',
                    'authorization' => (new Client)->header(
                        'https://example.com/oz/app',
                        'POST',
                        $this->apps['social']
                    )['header']
                ];
                $options = [
                    'encryptionPassword' => $this->encryptionPassword,
                    'loadAppFunc' => function () {
                        return $this->apps['social'];
                    },
                    'ticket' => [
                        'ttl' => 10 * 60 * 1000,
                    ]
                ];

                expect((new Endpoints)->app($req, $options))->notToBeEmpty();
            });

            $this->it('fails on invalid app request (bad credentials)', function () {
                $this->assertThrowsWithMessage(
                    UnauthorizedException::class,
                    'Bad MAC',
                    function() {
                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/app',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/app',
                                'POST',
                                $this->apps['social']
                            )['header']
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadAppFunc' => function () {
                                return $this->apps['network'];
                            },
                            'ticket' => [
                                'ttl' => 10 * 60 * 1000,
                            ]
                        ];

                        (new Endpoints)->app($req, $options);
                    }
                );
            });
        });
    }

    public function testReissue(): void
    {
        $this->describe('Endpoints::reissue()', function () {

            $this->it('allows null payload', function () {
                $req = [
                    'host' => 'example.com',
                    'port' => 443,
                    'method' => 'POST',
                    'url' => '/oz/reissue',
                    'authorization' => (new Client)->header(
                        'https://example.com/oz/reissue',
                        'POST',
                        $this->appTicket
                    )['header']
                ];
                $options = [
                    'encryptionPassword' => $this->encryptionPassword,
                    'loadAppFunc' => function () {
                        return $this->apps['social'];
                    }
                ];

                // @phpstan-ignore argument.type
                expect((new Endpoints)->reissue($req, null, $options))->notToBeEmpty();
            });

            $this->it('overrides defaults', function () {
                $req = [
                    'host' => 'example.com',
                    'port' => 443,
                    'method' => 'POST',
                    'url' => '/oz/reissue',
                    'authorization' => (new Client)->header(
                        'https://example.com/oz/reissue',
                        'POST',
                        $this->appTicket
                    )['header']
                ];
                $options = [
                    'encryptionPassword' => $this->encryptionPassword,
                    'loadAppFunc' => function () {
                        return $this->apps['social'];
                    },
                    'ticket' => [
                        'ttl' => 10 * 60 * 1000,
                    ]
                ];

                expect((new Endpoints)->reissue($req, [], $options))->notToBeEmpty();
            });

            $this->it('reissues expired ticket', function () {
                $req = [
                    'host' => 'example.com',
                    'port' => 443,
                    'method' => 'POST',
                    'url' => '/oz/app',
                    'authorization' => (new Client)->header(
                        'https://example.com/oz/app',
                        'POST',
                        $this->apps['social']
                    )['header']
                ];
                $options = [
                    'encryptionPassword' => $this->encryptionPassword,
                    'loadAppFunc' => function () {
                        return $this->apps['social'];
                    },
                    'ticket' => [
                        'ttl' => 1
                    ]
                ];

                $ticket = (new Endpoints)->app($req, $options);

                $req = [
                    'host' => 'example.com',
                    'port' => 443,
                    'method' => 'POST',
                    'url' => '/oz/reissue',
                    'authorization' => (new Client)->header(
                        'https://example.com/oz/reissue',
                        'POST',
                        $ticket
                    )['header']
                ];

                usleep(2000); // Wait 2 millisecond for ticket to expire

                expect((new Endpoints)->reissue($req, [], $options))->notToBeEmpty();
            });

            $this->it('fails on app load error', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Not Found',
                    function() {
                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/reissue',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/reissue',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadAppFunc' => function () {
                                throw new ServerException('Not Found');
                            }
                        ];

                        (new Endpoints)->reissue($req, [], $options);
                    }
                );
            });

            $this->it('fails on missing app delegation rights', function () {
                $this->assertThrowsWithMessage(
                    ForbiddenException::class,
                    'Application has no delegation rights',
                    function() {
                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/reissue',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/reissue',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadAppFunc' => function () {
                                return $this->apps['social'];
                            }
                        ];

                        (new Endpoints)->reissue($req, ['issueTo' => $this->apps['network']['id']], $options);
                    }
                );
            });

            $this->it('fails on invalid reissue (fails auth)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Bad HMAC value',
                    function() {
                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/reissue',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/reissue',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];
                        $options = [
                            'encryptionPassword' => 'a_password_that_is_not_too_short_and_also_not_very_random_but_is_good_enough_x',
                            'loadAppFunc' => function ($id) {
                                return $this->apps[$id];
                            }
                        ];

                        (new Endpoints)->reissue($req, ['issueTo' => null], $options);
                    }
                );
            });

            $this->it('fails on invalid reissue (invalid app)', function () {
                $this->assertThrowsWithMessage(
                    UnauthorizedException::class,
                    'Invalid application',
                    function() {
                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/reissue',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/reissue',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadAppFunc' => function () {
                                return null;
                            }
                        ];

                        (new Endpoints)->reissue($req, [], $options);
                    }
                );
            });

            $this->it('fails on invalid reissue (missing grant)', function () {
                $this->assertThrowsWithMessage(
                    UnauthorizedException::class,
                    'Invalid grant',
                    function() {
                        $endpoints = new Endpoints;

                        $grant = [
                            'id' => 'a1b2c3d4e5f6g7h8i9j0',
                            'app' => $this->appTicket['app'],
                            'user' => 'john',
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadAppFunc' => function ($id) {
                                return $this->apps[$id];
                            },
                            'loadGrantFunc' => function () use ($grant) {
                                return ['grant' => $grant];
                            }
                        ];

                        $rsvp = (new Ticket($this->encryptionPassword))->rsvp($this->apps['social'], $grant);

                        $req1 = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];

                        $ticket = $endpoints->rsvp($req1, ['rsvp' => $rsvp], $options);

                        $req2 = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/reissue',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/reissue',
                                'POST',
                                $ticket
                            )['header']
                        ];

                        $options['loadGrantFunc'] = function () {
                            return ['grant' => null];
                        };

                        $endpoints->reissue($req2, [], $options);
                    }
                );
            });

            $this->it('fails on invalid reissue (grant error)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'What?',
                    function() {
                        $endpoints = new Endpoints;

                        $grant = [
                            'id' => 'a1b2c3d4e5f6g7h8i9j0',
                            'app' => $this->appTicket['app'],
                            'user' => 'john',
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadAppFunc' => function ($id) {
                                return $this->apps[$id];
                            },
                            'loadGrantFunc' => function () use ($grant) {
                                return ['grant' => $grant];
                            }
                        ];

                        $rsvp = (new Ticket($this->encryptionPassword))->rsvp($this->apps['social'], $grant);

                        $req1 = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];

                        $ticket = $endpoints->rsvp($req1, ['rsvp' => $rsvp], $options);

                        $req2 = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/reissue',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/reissue',
                                'POST',
                                $ticket
                            )['header']
                        ];

                        $options['loadGrantFunc'] = function () {
                            throw new ServerException('What?');
                        };

                        $endpoints->reissue($req2, [], $options);
                    }
                );
            });

            $this->it('fails on invalid reissue (grant user mismatch)', function () {
                $this->assertThrowsWithMessage(
                    UnauthorizedException::class,
                    'Invalid grant',
                    function() {
                        $endpoints = new Endpoints;

                        $grant = [
                            'id' => 'a1b2c3d4e5f6g7h8i9j0',
                            'app' => $this->appTicket['app'],
                            'user' => 'john',
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadAppFunc' => function ($id) {
                                return $this->apps[$id];
                            },
                            'loadGrantFunc' => function () use ($grant) {
                                return ['grant' => $grant];
                            }
                        ];

                        $rsvp = (new Ticket($this->encryptionPassword))->rsvp($this->apps['social'], $grant);

                        $req1 = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];

                        $ticket = $endpoints->rsvp($req1, ['rsvp' => $rsvp], $options);

                        $req2 = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/reissue',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/reissue',
                                'POST',
                                $ticket
                            )['header']
                        ];

                        $options['loadGrantFunc'] = function () use ($grant) {
                            $grant['user'] = 'steve';
                            return ['grant' => $grant];
                        };

                        $endpoints->reissue($req2, [], $options);
                    }
                );
            });

            $this->it('fails on invalid reissue (grant missing exp)', function () {
                $this->assertThrowsWithMessage(
                    UnauthorizedException::class,
                    'Invalid grant',
                    function() {
                        $endpoints = new Endpoints;

                        $grant = [
                            'id' => 'a1b2c3d4e5f6g7h8i9j0',
                            'app' => $this->appTicket['app'],
                            'user' => 'john',
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadAppFunc' => function ($id) {
                                return $this->apps[$id];
                            },
                            'loadGrantFunc' => function () use ($grant) {
                                return ['grant' => $grant];
                            }
                        ];

                        $rsvp = (new Ticket($this->encryptionPassword))->rsvp($this->apps['social'], $grant);

                        $req1 = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];

                        $ticket = $endpoints->rsvp($req1, ['rsvp' => $rsvp], $options);

                        $req2 = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/reissue',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/reissue',
                                'POST',
                                $ticket
                            )['header']
                        ];

                        $options['loadGrantFunc'] = function () use ($grant) {
                            unset($grant['exp']);
                            return ['grant' => $grant];
                        };

                        $endpoints->reissue($req2, [], $options);
                    }
                );
            });

            $this->it('fails on invalid reissue (grant app does not match app or dlg)', function () {
                $this->assertThrowsWithMessage(
                    UnauthorizedException::class,
                    'Invalid grant',
                    function() {
                        $endpoints = new Endpoints;
                        $this->apps['social']['delegate'] = true;

                        /*
                         * 1. The app requests an app ticket using Oz.hawk
                         *    authentication
                         */

                        $appTicket = $endpoints->app($this->req, $this->options);

                        /*
                         * 2. The user is redirected to the server, logs in, and
                         *    grant app access, resulting in an RSVP
                         */

                        $grant = [
                            'id' => 'a1b2c3d4e5f6g7h8i9j0',
                            'app' => $appTicket['app'],
                            'user' => 'john',
                            'exp' => (new HawkUtils)->now() + 60000
                        ];

                        $rsvp = (new Ticket($this->encryptionPassword))->rsvp($this->apps['social'], $grant);

                        /*
                         * 3. After granting app access, the user returns to the
                         *    app with the RSVP
                         */

                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadGrantFunc' => function () use ($grant) {
                                return ['grant' => $grant];
                            },
                            'loadAppFunc' => $this->options['loadAppFunc']
                        ];

                        /*
                         * 4. The app exchanges the rsvp for a ticket
                         */

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $appTicket
                            )['header']
                        ];

                        $ticket = $endpoints->rsvp($req, ['rsvp' => $rsvp], $options);

                        /*
                         * 5. The app reissues the ticket with delegation to
                         *    another app
                         */

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/reissue',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/reissue',
                                'POST',
                                $ticket
                            )['header']
                        ];

                        $delegatedTicket = $endpoints->reissue($req, ['issueTo' => $this->apps['network']['id']], $options);

                        /*
                         * 6. The other app reissues their ticket
                         */

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/reissue',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/reissue',
                                'POST',
                                $ticket
                            )['header']
                        ];

                        $options['loadGrantFunc'] = function () use ($grant) {
                            $grant['app'] = 'xyz';
                            return ['grant' => $grant];
                        };

                        $endpoints->reissue($req, [], $options);
                    }
                );
            });
        });
    }

    public function testRsvp(): void
    {
        $this->describe('Endpoints::rsvp()', function () {

            $this->it('overrides defaults', function () {
                $grant = [
                    'id' => 'a1b2c3d4e5f6g7h8i9j0',
                    'app' => $this->appTicket['app'],
                    'user' => 'john',
                    'exp' => (new HawkUtils)->now() + 60000
                ];
                $options = [
                    'encryptionPassword' => $this->encryptionPassword,
                    'loadGrantFunc' => function () use ($grant) {
                        return ['grant' => $grant];
                    },
                    'loadAppFunc' => $this->options['loadAppFunc'],
                    'ticket' => [
                        'iron' => IronOptions::$defaults
                    ]
                ];

                $rsvp = (new Ticket($this->encryptionPassword))->rsvp($this->apps['social'], $grant);

                $req = [
                    'host' => 'example.com',
                    'port' => 443,
                    'method' => 'POST',
                    'url' => '/oz/rsvp',
                    'authorization' => (new Client)->header(
                        'https://example.com/oz/rsvp',
                        'POST',
                        $this->appTicket
                    )['header']
                ];

                expect((new Endpoints)->rsvp($req, ['rsvp' => $rsvp], $options))->notToBeEmpty();
            });

            $this->it('errors on invalid authentication', function () {
                $this->assertThrowsWithMessage(
                    UnauthorizedException::class,
                    '',
                    function() {
                        $grant = [
                            'id' => 'a1b2c3d4e5f6g7h8i9j0',
                            'app' => $this->appTicket['app'],
                            'user' => 'john',
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadGrantFunc' => function () use ($grant) {
                                return ['grant' => $grant];
                            },
                            'loadAppFunc' => $this->options['loadAppFunc'],
                            'ticket' => [
                                'iron' => IronOptions::$defaults
                            ]
                        ];

                        $rsvp = (new Ticket($this->encryptionPassword))->rsvp($this->apps['social'], $grant);

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp'
                        ];

                        (new Endpoints)->rsvp($req, ['rsvp' => $rsvp], $options);
                    }
                );
            });

            $this->it('errors on expired ticket', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Expired ticket',
                    function() {
                        $grant = [
                            'id' => 'a1b2c3d4e5f6g7h8i9j0',
                            'app' => $this->appTicket['app'],
                            'user' => 'john',
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadGrantFunc' => function () use ($grant) {
                                return ['grant' => $grant];
                            },
                            'loadAppFunc' => $this->options['loadAppFunc'],
                            'ticket' => [
                                'ttl' => 1
                            ]
                        ];

                        $appTicket = (new Endpoints)->app($this->req, $options);

                        $rsvp = (new Ticket($this->encryptionPassword))->rsvp($this->apps['social'], $grant);

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $appTicket
                            )['header']
                        ];

                        usleep(2000); // Wait 2 millisecond for ticket to expire

                        (new Endpoints)->rsvp($req, ['rsvp' => $rsvp], $options);
                    }
                );
            });

            $this->it('errors on missing payload', function () {
                $this->assertThrowsWithMessage(
                    BadRequestException::class,
                    'Missing required payload',
                    function() {
                        // @phpstan-ignore argument.type
                        (new Endpoints)->rsvp([], null, []);
                    }
                );
            });

            $this->it('fails on invalid rsvp (request params)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Incorrect number of sealed components',
                    function() {
                        $grant = [
                            'id' => 'a1b2c3d4e5f6g7h8i9j0',
                            'app' => $this->appTicket['app'],
                            'user' => 'john',
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadGrantFunc' => function () use ($grant) {
                                return ['grant' => $grant];
                            },
                            'loadAppFunc' => $this->options['loadAppFunc']
                        ];
                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];

                        (new Endpoints)->rsvp($req, ['rsvp' => ''], $options);
                    }
                );
            });

            $this->it('fails on invalid rsvp (invalid auth)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Incorrect number of sealed components',
                    function() {
                        $grant = [
                            'id' => 'a1b2c3d4e5f6g7h8i9j0',
                            'app' => $this->appTicket['app'],
                            'user' => 'john',
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadGrantFunc' => function () use ($grant) {
                                return ['grant' => $grant];
                            },
                            'loadAppFunc' => $this->options['loadAppFunc']
                        ];
                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];

                        (new Endpoints)->rsvp($req, ['rsvp' => 'abc'], $options);
                    }
                );
            });

            $this->it('fails on invalid rsvp (user ticket)', function () {
                $this->assertThrowsWithMessage(
                    UnauthorizedException::class,
                    'User ticket cannot be used on an application endpoint',
                    function() {
                        $grant = [
                            'id' => 'a1b2c3d4e5f6g7h8i9j0',
                            'app' => $this->appTicket['app'],
                            'user' => 'john',
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadGrantFunc' => function () use ($grant) {
                                return ['grant' => $grant];
                            },
                            'loadAppFunc' => $this->options['loadAppFunc']
                        ];

                        $rsvp = (new Ticket($this->encryptionPassword))->rsvp($this->apps['social'], $grant);

                        $req1 = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];

                        $ticket1 = (new Endpoints)->rsvp($req1, ['rsvp' => $rsvp], $options);

                        $req2 = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $ticket1
                            )['header']
                        ];

                        (new Endpoints)->rsvp($req2, ['rsvp' => $rsvp], $options);
                    }
                );
            });

            $this->it('fails on invalid rsvp (mismatching apps)', function () {
                $this->assertThrowsWithMessage(
                    ForbiddenException::class,
                    'Mismatching ticket and rsvp apps',
                    function() {
                        $grant = [
                            'id' => 'a1b2c3d4e5f6g7h8i9j0',
                            'app' => $this->appTicket['app'],
                            'user' => 'john',
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadGrantFunc' => function () use ($grant) {
                                return ['grant' => $grant];
                            },
                            'loadAppFunc' => $this->options['loadAppFunc']
                        ];

                        $rsvp = (new Ticket($this->encryptionPassword))->rsvp($this->apps['network'], $grant);

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];

                        (new Endpoints)->rsvp($req, ['rsvp' => $rsvp], $options);
                    }
                );
            });

            $this->it('fails on invalid rsvp (expired rsvp)', function () {
                $this->assertThrowsWithMessage(
                    ForbiddenException::class,
                    'Expired rsvp',
                    function() {
                        $grant = [
                            'id' => 'a1b2c3d4e5f6g7h8i9j0',
                            'app' => $this->appTicket['app'],
                            'user' => 'john',
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadGrantFunc' => function () use ($grant) {
                                return ['grant' => $grant];
                            },
                            'loadAppFunc' => $this->options['loadAppFunc']
                        ];

                        $rsvp = (new Ticket($this->encryptionPassword, ['ttl' => 1]))
                                    ->rsvp($this->apps['social'], $grant);

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];

                        usleep(2000); // Wait 2 millisecond for RSVP to expire

                        (new Endpoints)->rsvp($req, ['rsvp' => $rsvp], $options);
                    }
                );
            });

            $this->it('fails on invalid rsvp (expired grant)', function () {
                $this->assertThrowsWithMessage(
                    ForbiddenException::class,
                    'Invalid grant',
                    function() {
                        $grant = [
                            'id' => 'a1b2c3d4e5f6g7h8i9j0',
                            'app' => $this->appTicket['app'],
                            'user' => 'john',
                            'exp' => (new HawkUtils)->now() - 1000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadGrantFunc' => function () use ($grant) {
                                return ['grant' => $grant];
                            },
                            'loadAppFunc' => $this->options['loadAppFunc']
                        ];

                        $rsvp = (new Ticket($this->encryptionPassword))->rsvp($this->apps['social'], $grant);

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];

                        (new Endpoints)->rsvp($req, ['rsvp' => $rsvp], $options);
                    }
                );
            });

            $this->it('fails on invalid rsvp (missing grant)', function () {
                $this->assertThrowsWithMessage(
                    ForbiddenException::class,
                    'Invalid grant',
                    function() {
                        $grant = [
                            'id' => 'a1b2c3d4e5f6g7h8i9j0',
                            'app' => $this->appTicket['app'],
                            'user' => 'john',
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadGrantFunc' => function () {
                                return null;
                            },
                            'loadAppFunc' => $this->options['loadAppFunc']
                        ];

                        $rsvp = (new Ticket($this->encryptionPassword))->rsvp($this->apps['social'], $grant);

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];

                        (new Endpoints)->rsvp($req, ['rsvp' => $rsvp], $options);
                    }
                );
            });

            $this->it('fails on invalid rsvp (grant app mismatch)', function () {
                $this->assertThrowsWithMessage(
                    ForbiddenException::class,
                    'Invalid grant',
                    function() {
                        $grant = [
                            'id' => 'a1b2c3d4e5f6g7h8i9j0',
                            'app' => $this->appTicket['app'],
                            'user' => 'john',
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadGrantFunc' => function () use ($grant) {
                                $grant['app'] = $this->apps['network']['id'];
                                return ['grant' => $grant];
                            },
                            'loadAppFunc' => $this->options['loadAppFunc']
                        ];

                        $rsvp = (new Ticket($this->encryptionPassword))->rsvp($this->apps['social'], $grant);

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];

                        (new Endpoints)->rsvp($req, ['rsvp' => $rsvp], $options);
                    }
                );
            });

            $this->it('fails on invalid rsvp (grant missing exp)', function () {
                $this->assertThrowsWithMessage(
                    ForbiddenException::class,
                    'Invalid grant',
                    function() {
                        $grant = [
                            'id' => 'a1b2c3d4e5f6g7h8i9j0',
                            'app' => $this->appTicket['app'],
                            'user' => 'john',
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadGrantFunc' => function () use ($grant) {
                                unset($grant['exp']);
                                return ['grant' => $grant];
                            },
                            'loadAppFunc' => $this->options['loadAppFunc']
                        ];

                        $rsvp = (new Ticket($this->encryptionPassword))->rsvp($this->apps['social'], $grant);

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];

                        (new Endpoints)->rsvp($req, ['rsvp' => $rsvp], $options);
                    }
                );
            });

            $this->it('fails on invalid rsvp (grant error)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Boom!',
                    function() {
                        $grant = [
                            'id' => 'a1b2c3d4e5f6g7h8i9j0',
                            'app' => $this->appTicket['app'],
                            'user' => 'john',
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadGrantFunc' => function () {
                                throw new ServerException('Boom!');
                            },
                            'loadAppFunc' => $this->options['loadAppFunc']
                        ];

                        $rsvp = (new Ticket($this->encryptionPassword))->rsvp($this->apps['social'], $grant);

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];

                        (new Endpoints)->rsvp($req, ['rsvp' => $rsvp], $options);
                    }
                );
            });

            $this->it('fails on invalid rsvp (app error)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Nope.',
                    function() {
                        $grant = [
                            'id' => 'a1b2c3d4e5f6g7h8i9j0',
                            'app' => $this->appTicket['app'],
                            'user' => 'john',
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadGrantFunc' => function () use ($grant) {
                                return ['grant' => $grant];
                            },
                            'loadAppFunc' => function () {
                                throw new ServerException('Nope.');
                            }
                        ];

                        $rsvp = (new Ticket($this->encryptionPassword))->rsvp($this->apps['social'], $grant);

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];

                        (new Endpoints)->rsvp($req, ['rsvp' => $rsvp], $options);
                    }
                );
            });

            $this->it('fails on invalid rsvp (invalid app)', function () {
                $this->assertThrowsWithMessage(
                    ForbiddenException::class,
                    'Invalid application',
                    function() {
                        $grant = [
                            'id' => 'a1b2c3d4e5f6g7h8i9j0',
                            'app' => $this->appTicket['app'],
                            'user' => 'john',
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'loadGrantFunc' => function () use ($grant) {
                                return ['grant' => $grant];
                            },
                            'loadAppFunc' => function () {
                                return null;
                            }
                        ];

                        $rsvp = (new Ticket($this->encryptionPassword))->rsvp($this->apps['social'], $grant);

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/rsvp',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/rsvp',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];

                        (new Endpoints)->rsvp($req, ['rsvp' => $rsvp], $options);
                    }
                );
            });
        });
    }

    public function testUser(): void
    {
        $this->describe('Endpoints::user()', function () {

            $this->it('overrides defaults (user credentials grant)', function () {
                $grant = [
                    'exp' => (new HawkUtils)->now() + 60000
                ];
                $options = [
                    'encryptionPassword' => $this->encryptionPassword,
                    'grant' => $grant,
                    'loadAppFunc' => $this->options['loadAppFunc'],
                    'storeGrantFunc' => function ($grantObj) use (&$grant) {
                        $grant = $grantObj;
                        $grant['id'] = 'a1b2c3d4e5f6g7h8i9j0';
                        return $grant['id'];
                    },
                    'verifyUserFunc' => function ($userCreds) {
                        return 'john'; // User ID
                    },
                    'ticket' => [
                        'iron' => IronOptions::$defaults
                    ]
                ];
                $userCreds = [
                    'username' => 'johns_account',
                    'password' => 'j0hns_p4$$w0rd'
                ];

                $req = [
                    'host' => 'example.com',
                    'port' => 443,
                    'method' => 'POST',
                    'url' => '/oz/user',
                    'authorization' => (new Client)->header(
                        'https://example.com/oz/user',
                        'POST',
                        $this->appTicket
                    )['header']
                ];

                expect((new Endpoints)->user($req, ['user' => $userCreds], $options))->notToBeEmpty();
            });

            $this->it('overrides defaults (implicit grant)', function () {
                $grant = [
                    'exp' => (new HawkUtils)->now() + 60000
                ];
                $options = [
                    'encryptionPassword' => $this->encryptionPassword,
                    'grant' => $grant,
                    'storeGrantFunc' => function ($grantObj) use (&$grant) {
                        $grant = $grantObj;
                        $grant['id'] = 'a1b2c3d4e5f6g7h8i9j0';
                        return $grant['id'];
                    },
                    'verifyUserFunc' => function ($userCreds) {
                        return 'john'; // User ID
                    },
                    'ticket' => [
                        'iron' => IronOptions::$defaults
                    ]
                ];
                $userCreds = [
                    'username' => 'johns_account',
                    'password' => 'j0hns_p4$$w0rd'
                ];

                $req = [
                    'host' => 'example.com',
                    'port' => 443,
                    'method' => 'POST',
                    'url' => '/oz/user'
                ];

                expect((new Endpoints)->user($req, ['user' => $userCreds], $options))->notToBeEmpty();
            });

            $this->it('sets grant type to `user_credentials` if app authenticates', function () {
                $grant = [
                    'exp' => (new HawkUtils)->now() + 60000
                ];
                $options = [
                    'encryptionPassword' => $this->encryptionPassword,
                    'grant' => $grant,
                    'loadAppFunc' => $this->options['loadAppFunc'],
                    'storeGrantFunc' => function ($grantObj) use (&$grant) {
                        $grant = $grantObj;
                        $grant['id'] = 'a1b2c3d4e5f6g7h8i9j0';
                        return $grant['id'];
                    },
                    'verifyUserFunc' => function ($userCreds) {
                        return 'john'; // User ID
                    },
                    'ticket' => [
                        'iron' => IronOptions::$defaults
                    ]
                ];
                $userCreds = [
                    'username' => 'johns_account',
                    'password' => 'j0hns_p4$$w0rd'
                ];

                $req = [
                    'host' => 'example.com',
                    'port' => 443,
                    'method' => 'POST',
                    'url' => '/oz/user',
                    'authorization' => (new Client)->header(
                        'https://example.com/oz/user',
                        'POST',
                        $this->appTicket
                    )['header']
                ];

                (new Endpoints)->user($req, ['user' => $userCreds], $options);

                expect($grant['type'])->toEqual('user_credentials');
            });

            $this->it('sets grant type to `implicit` if app does not authenticate', function () {
                $grant = [
                    'exp' => (new HawkUtils)->now() + 60000
                ];
                $options = [
                    'encryptionPassword' => $this->encryptionPassword,
                    'grant' => $grant,
                    'storeGrantFunc' => function ($grantObj) use (&$grant) {
                        $grant = $grantObj;
                        $grant['id'] = 'a1b2c3d4e5f6g7h8i9j0';
                        return $grant['id'];
                    },
                    'verifyUserFunc' => function ($userCreds) {
                        return 'john'; // User ID
                    },
                    'ticket' => [
                        'iron' => IronOptions::$defaults
                    ]
                ];
                $userCreds = [
                    'username' => 'johns_account',
                    'password' => 'j0hns_p4$$w0rd'
                ];

                $req = [
                    'host' => 'example.com',
                    'port' => 443,
                    'method' => 'POST',
                    'url' => '/oz/user'
                ];

                (new Endpoints)->user($req, ['user' => $userCreds], $options);

                expect($grant['type'])->toEqual('implicit');
            });

            $this->it('fails on app error', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Nope.',
                    function() {
                        $grant = [
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'grant' => $grant,
                            'loadAppFunc' => function () {
                                throw new ServerException('Nope.');
                            },
                            'storeGrantFunc' => function ($grantObj) use (&$grant) {
                                $grant = $grantObj;
                                $grant['id'] = 'a1b2c3d4e5f6g7h8i9j0';
                                return $grant['id'];
                            },
                            'verifyUserFunc' => function ($userCreds) {
                                return 'john'; // User ID
                            }
                        ];
                        $userCreds = [
                            'username' => 'johns_account',
                            'password' => 'j0hns_p4$$w0rd'
                        ];

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/user',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/user',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];

                        (new Endpoints)->user($req, ['user' => $userCreds], $options);
                    }
                );
            });

            $this->it('fails on invalid app', function () {
                $this->assertThrowsWithMessage(
                    ForbiddenException::class,
                    'Invalid application',
                    function() {
                        $grant = [
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'grant' => $grant,
                            'loadAppFunc' => function () {
                                return null;
                            },
                            'storeGrantFunc' => function ($grantObj) use (&$grant) {
                                $grant = $grantObj;
                                $grant['id'] = 'a1b2c3d4e5f6g7h8i9j0';
                                return $grant['id'];
                            },
                            'verifyUserFunc' => function ($userCreds) {
                                return 'john'; // User ID
                            }
                        ];
                        $userCreds = [
                            'username' => 'johns_account',
                            'password' => 'j0hns_p4$$w0rd'
                        ];

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/user',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/user',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];

                        (new Endpoints)->user($req, ['user' => $userCreds], $options);
                    }
                );
            });

            $this->it('fails on missing payload', function () {
                $this->assertThrowsWithMessage(
                    BadRequestException::class,
                    'Missing required payload',
                    function() {
                        // @phpstan-ignore argument.type
                        (new Endpoints)->user([], null, []);
                    }
                );
            });

            $this->it('fails on attempted user credentials grant if not allowed', function () {
                $this->assertThrowsWithMessage(
                    UnauthorizedException::class,
                    'User credentials grant not allowed',
                    function() {
                        $grant = [
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'grant' => $grant,
                            'loadAppFunc' => $this->options['loadAppFunc'],
                            'storeGrantFunc' => function ($grantObj) use (&$grant) {
                                $grant = $grantObj;
                                $grant['id'] = 'a1b2c3d4e5f6g7h8i9j0';
                                return $grant['id'];
                            },
                            'verifyUserFunc' => function ($userCreds) {
                                return 'john'; // User ID
                            },
                            'allowedGrantTypes' => ['rsvp']
                        ];
                        $userCreds = [
                            'username' => 'johns_account',
                            'password' => 'j0hns_p4$$w0rd'
                        ];

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/user',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/user',
                                'POST',
                                $this->appTicket
                            )['header']
                        ];

                        expect((new Endpoints)->user($req, ['user' => $userCreds], $options))->notToBeEmpty();
                    }
                );
            });

            $this->it('fails on attempted implicit grant if not allowed', function () {
                $this->assertThrowsWithMessage(
                    UnauthorizedException::class,
                    'Implicit grant not allowed',
                    function() {
                        $grant = [
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'grant' => $grant,
                            'storeGrantFunc' => function ($grantObj) use (&$grant) {
                                $grant = $grantObj;
                                $grant['id'] = 'a1b2c3d4e5f6g7h8i9j0';
                                return $grant['id'];
                            },
                            'verifyUserFunc' => function ($userCreds) {
                                return 'john'; // User ID
                            },
                            'allowedGrantTypes' => ['rsvp']
                        ];
                        $userCreds = [
                            'username' => 'johns_account',
                            'password' => 'j0hns_p4$$w0rd'
                        ];

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/user'
                        ];

                        expect((new Endpoints)->user($req, ['user' => $userCreds], $options))->notToBeEmpty();
                    }
                );
            });

            $this->it('fails on use of user ticket', function () {
                $this->assertThrowsWithMessage(
                    UnauthorizedException::class,
                    'User ticket cannot be used on an application endpoint',
                    function() {
                        $grant = [
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'grant' => $grant,
                            'loadAppFunc' => $this->options['loadAppFunc'],
                            'storeGrantFunc' => function ($grantObj) use (&$grant) {
                                $grant = $grantObj;
                                $grant['id'] = 'a1b2c3d4e5f6g7h8i9j0';
                                return $grant['id'];
                            },
                            'verifyUserFunc' => function ($userCreds) {
                                return 'john'; // User ID
                            }
                        ];
                        $userCreds = [
                            'username' => 'johns_account',
                            'password' => 'j0hns_p4$$w0rd'
                        ];

                        $req1 = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/user'
                        ];

                        $userTicket = (new Endpoints)->user($req1, ['user' => $userCreds], $options);

                        $req2 = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/user',
                            'authorization' => (new Client)->header(
                                'https://example.com/oz/user',
                                'POST',
                                $userTicket
                            )['header']
                        ];

                        expect((new Endpoints)->user($req2, ['user' => $userCreds], $options))->notToBeEmpty();
                    }
                );
            });

            $this->it('fails on user credentials verification error', function () {
                $this->assertThrowsWithMessage(
                    UnauthorizedException::class,
                    'Incorrect password',
                    function () {
                        $grant = [
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'grant' => $grant,
                            'storeGrantFunc' => function ($grantObj) use (&$grant) {
                                $grant = $grantObj;
                                $grant['id'] = 'a1b2c3d4e5f6g7h8i9j0';
                                return $grant['id'];
                            },
                            'verifyUserFunc' => function ($userCreds) {
                                throw new UnauthorizedException('Incorrect password');
                            }
                        ];
                        $userCreds = [
                            'username' => 'johns_account',
                            'password' => 'j0hns_p4$$w0rd'
                        ];

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/user'
                        ];

                        (new Endpoints)->user($req, ['user' => $userCreds], $options);
                    }
                );
            });

            $this->it('fails on invalid grant options (missing)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid grant options',
                    function() {
                        $grant = [
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'storeGrantFunc' => function ($grantObj) use (&$grant) {
                                $grant = $grantObj;
                                $grant['id'] = 'a1b2c3d4e5f6g7h8i9j0';
                                return $grant['id'];
                            },
                            'verifyUserFunc' => function ($userCreds) {
                                return 'john'; // User ID
                            }
                        ];
                        $userCreds = [
                            'username' => 'johns_account',
                            'password' => 'j0hns_p4$$w0rd'
                        ];

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/user'
                        ];

                        (new Endpoints)->user($req, ['user' => $userCreds], $options);
                    }
                );
            });

            $this->it('fails on invalid grant options (exp)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid grant object',
                    function() {
                        $grant = [
                            'scope' => ['a']
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'grant' => $grant,
                            'storeGrantFunc' => function ($grantObj) use (&$grant) {
                                $grant = $grantObj;
                                $grant['id'] = 'a1b2c3d4e5f6g7h8i9j0';
                                return $grant['id'];
                            },
                            'verifyUserFunc' => function ($userCreds) {
                                return 'john'; // User ID
                            }
                        ];
                        $userCreds = [
                            'username' => 'johns_account',
                            'password' => 'j0hns_p4$$w0rd'
                        ];

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/user',
                        ];

                        (new Endpoints)->user($req, ['user' => $userCreds], $options);
                    }
                );
            });

            $this->it('fails on invalid grant options (scope)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'scope not an array',
                    function() {
                        $grant = [
                            'exp' => (new HawkUtils)->now() + 60000,
                            'scope' => 'a'
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'grant' => $grant,
                            'storeGrantFunc' => function ($grantObj) use (&$grant) {
                                $grant = $grantObj;
                                $grant['id'] = 'a1b2c3d4e5f6g7h8i9j0';
                                return $grant['id'];
                            },
                            'verifyUserFunc' => function ($userCreds) {
                                return 'john'; // User ID
                            }
                        ];
                        $userCreds = [
                            'username' => 'johns_account',
                            'password' => 'j0hns_p4$$w0rd'
                        ];

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/user'
                        ];

                        (new Endpoints)->user($req, ['user' => $userCreds], $options);
                    }
                );
            });

            $this->it('fails on invalid stored grant ID (missing)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid stored grant ID',
                    function() {
                        $grant = [
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'grant' => $grant,
                            'storeGrantFunc' => function ($grantObj) {
                                return null;
                            },
                            'verifyUserFunc' => function ($userCreds) {
                                return 'john'; // User ID
                            }
                        ];
                        $userCreds = [
                            'username' => 'johns_account',
                            'password' => 'j0hns_p4$$w0rd'
                        ];

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/user'
                        ];

                        (new Endpoints)->user($req, ['user' => $userCreds], $options);
                    }
                );
            });

            $this->it('fails on invalid stored grant ID (non-string value)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid stored grant ID',
                    function() {
                        $grant = [
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'grant' => $grant,
                            'storeGrantFunc' => function ($grantObj) {
                                return 123;
                            },
                            'verifyUserFunc' => function ($userCreds) {
                                return 'john'; // User ID
                            }
                        ];
                        $userCreds = [
                            'username' => 'johns_account',
                            'password' => 'j0hns_p4$$w0rd'
                        ];

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/user'
                        ];

                        (new Endpoints)->user($req, ['user' => $userCreds], $options);
                    }
                );
            });

            $this->it('fails on invalid stored grant ID (error)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Boom!',
                    function() {
                        $grant = [
                            'exp' => (new HawkUtils)->now() + 60000
                        ];
                        $options = [
                            'encryptionPassword' => $this->encryptionPassword,
                            'grant' => $grant,
                            'storeGrantFunc' => function ($grantObj) {
                                throw new ServerException('Boom!');
                            },
                            'verifyUserFunc' => function ($userCreds) {
                                return 'john'; // User ID
                            }
                        ];
                        $userCreds = [
                            'username' => 'johns_account',
                            'password' => 'j0hns_p4$$w0rd'
                        ];

                        $req = [
                            'host' => 'example.com',
                            'port' => 443,
                            'method' => 'POST',
                            'url' => '/oz/user'
                        ];

                        (new Endpoints)->user($req, ['user' => $userCreds], $options);
                    }
                );
            });
        });
    }
}
