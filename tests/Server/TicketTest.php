<?php

namespace Shawm11\Oz\Tests;

use PHPUnit\Framework\TestCase;
use Shawm11\Oz\Server\Ticket;
use Shawm11\Oz\Server\ServerException;
use Shawm11\Oz\Server\ForbiddenException;
use Shawm11\Oz\Server\BadRequestException;
use Shawm11\Hawk\Utils\Utils as HawkUtils;
use Shawm11\Iron\IronOptions;

class TicketTest extends TestCase
{
    use \Codeception\Specify;
    use \Codeception\AssertThrows;

    protected $password = 'a_password_that_is_not_too_short_and_also_not_very_random_but_is_good_enough';

    public function testIssue()
    {
        $this->describe('Ticket::issue()', function () {

            $this->it('should construct a valid ticket', function () {
                $app = [
                    'id' => '123',
                    'scope' => ['a', 'b']
                ];

                $grant = [
                    'id' => 's81u29n1812',
                    'user' => '456',
                    'exp' => (new HawkUtils)->now() + 5000,
                    'scope' => ['a']
                ];

                $options = [
                    'ttl' => 10 * 60 * 1000,
                    'ext' => [
                        'public' => [
                            'x' => 'welcome'
                        ],
                        'private' => [
                            'x' => 123
                        ]
                    ]
                ];

                $ticketClass = new Ticket($this->password, $options);

                $envelope = $ticketClass->issue($app, $grant);

                expect($envelope['ext'])->equals(['x' => 'welcome']);
                expect($envelope['exp'])->equals($grant['exp']);
                expect($envelope['scope'])->equals(['a']);

                $ticket = $ticketClass->parse($envelope['id']);

                expect($ticket['ext'])->equals($options['ext']);

                $envelope2 = $ticketClass->reissue($ticket, $grant);

                expect($envelope2['id'])->notEquals($envelope['id']);
            });

            $this->it('errors on missing app', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid application object',
                    function() {
        	           (new Ticket($this->password))->issue(null, null);
                    }
                );
            });

            $this->it('errors on invalid app', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid application object',
                    function() {
        	           (new Ticket($this->password))->issue('', null);
                    }
                );
            });

            $this->it('errors on invalid grant (missing id)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid grant object',
                    function() {
                        (new Ticket($this->password))->issue(['id' => 'abc'], []);
                    }
                );
            });

            $this->it('errors on invalid grant (missing user)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid grant object',
                    function() {
                        (new Ticket($this->password))->issue(['id' => 'abc'], ['id' => '123']);
                    }
                );
            });

            $this->it('errors on invalid grant (missing exp)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid grant object',
                    function() {
                        (new Ticket($this->password))->issue(['id' => 'abc'], ['id' => '123', 'user' => 'steve']);
                    }
                );
            });

            $this->it('errors on invalid grant (invalid type)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid grant object',
                    function() {
                        (new Ticket($this->password))->issue(
                            ['id' => 'abc'],
                            ['id' => '123', 'user' => 'steve', 'exp' => 1442690715989, 'type' => 'x']
                        );
                    }
                );
            });

            $this->it('errors on invalid grant (scope outside app)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Grant scope is not a subset of the application scope',
                    function() {
                        (new Ticket($this->password))->issue(
                            [
                                'id' => 'abc',
                                'scope' => ['a']
                            ],
                            [
                                'id' => '123',
                                'user' => 'steve',
                                'exp' => 1442690715989,
                                'scope' => ['b']
                            ]
                      );
                    }
                );
            });

            $this->it('errors on invalid app scope', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'scope not an array',
                    function() {
                        (new Ticket($this->password))->issue(['id' => 'abc', 'scope' => 'a'], null);
                    }
                );
            });

            $this->it('errors on invalid password', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid encryption password',
                    function() {
                      (new Ticket(''))->issue(['id' => 'abc'], null);
                    }
                );
            });
        });
    }

    public function testReissue()
    {
        $this->describe('Ticket::reissue()', function () {

            $this->it('sets delegate to false', function () {
                $app = [
                    'id' => '123'
                ];

                $ticketClass = new Ticket($this->password);

                $envelope = $ticketClass->issue($app, null);
                $ticket = $ticketClass->parse($envelope['id']);

                $ticketClass2 = new Ticket($this->password, ['issueTo' => '345', 'delegate' => false]);
                $envelope2 = $ticketClass2->reissue($ticket, null);

                expect($envelope2['delegate'])->false();
            });

            $this->it('errors on issueTo when delegate is not allowed', function () {
                $this->assertThrowsWithMessage(
                    ForbiddenException::class,
                    'Ticket does not allow delegation',
                    function() {
                        $app = [
                            'id' => '123'
                        ];
                        $options = [
                            'delegate' => false
                        ];

                        $ticketClass = new Ticket($this->password, $options);

                        $envelope = $ticketClass->issue($app, null);
                        $ticket = $ticketClass->parse($envelope['id']);

                        $ticketClass2 = new Ticket($this->password, ['issueTo' => '345']);
                        $envelope2 = $ticketClass2->reissue($ticket, null);
                    }
                );
            });

            $this->it('errors on delegate override', function () {
                $this->assertThrowsWithMessage(
                    ForbiddenException::class,
                    'Cannot override ticket delegate restriction',
                    function() {
                        $app = [
                            'id' => '123'
                        ];
                        $options = [
                            'delegate' => false
                        ];

                        $ticketClass = new Ticket($this->password, $options);

                        $envelope = $ticketClass->issue($app, null);
                        $ticket = $ticketClass->parse($envelope['id']);

                        $ticketClass2 = new Ticket($this->password, ['delegate' => true]);
                        $envelope2 = $ticketClass2->reissue($ticket, null);
                    }
                );
            });

            $this->it('errors on missing parent ticket', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid parent ticket object',
                    function() {
                       (new Ticket($this->password))->reissue(null, null);
                    }
                );
            });

            $this->it('errors on missing password', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid encryption password',
                    function() {
                       (new Ticket(''))->reissue([], null);
                    }
                );
            });

            $this->it('errors on missing parent scope', function () {
                $this->assertThrowsWithMessage(
                    ForbiddenException::class,
                    'New scope is not a subset of the parent ticket scope',
                    function() {
                       (new Ticket($this->password, ['scope' => ['a']]))->reissue([], null);
                    }
                );
            });

            $this->it('errors on invalid parent scope', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'scope not an array',
                    function() {
                       (new Ticket($this->password, ['scope' => ['a']]))->reissue(['scope' => 'a'] , null);
                    }
                );
            });

            $this->it('errors on invalid options scope', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'scope not an array',
                    function() {
                       (new Ticket($this->password, ['scope' => 'a']))->reissue(['scope' => ['a']] , null);
                    }
                );
            });

            $this->it('errors on invalid grant (missing id)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid grant object',
                    function() {
                        (new Ticket($this->password))->reissue([], []);
                    }
                );
            });

            $this->it('errors on invalid grant (missing user)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid grant object',
                    function() {
                        (new Ticket($this->password))->reissue([], ['id' => 'abc']);
                    }
                );
            });

            $this->it('errors on invalid grant (missing exp)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid grant object',
                    function() {
                        (new Ticket($this->password))->reissue([], ['id' => 'abc', 'user' => 'steve']);
                    }
                );
            });

            $this->it('errors on invalid grant (missing exp)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid grant object',
                    function() {
                        (new Ticket($this->password))->reissue(
                            [],
                            ['id' => 'abc', 'user' => 'steve', 'exp' => 1442690715989, 'type' => 'x']
                        );
                    }
                );
            });

            $this->it('errors on options.issueTo and ticket.dlg conflict', function () {
                $this->assertThrowsWithMessage(
                    BadRequestException::class,
                    'Cannot re-delegate',
                    function() {
                        (new Ticket($this->password, ['issueTo' => '345']))->reissue(['dlg' => '123'], null);
                    }
                );
            });

            $this->it('errors on mismatching grants (missing grant)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Parent ticket grant does not match options.grant',
                    function() {
                        (new Ticket($this->password))->reissue(['grant' => '123'], null);
                    }
                );
            });

            $this->it('errors on mismatching grants (missing parent)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Parent ticket grant does not match options.grant',
                    function() {
                        (new Ticket($this->password))->reissue(
                            [],
                            ['id' => '123', 'user' => 'steve', 'exp' => 1442690715989]
                        );
                    }
                );
            });

            $this->it('errors on mismatching grants (different)', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Parent ticket grant does not match options.grant',
                    function() {
                        (new Ticket($this->password))->reissue(
                            ['grant' => '234'],
                            ['id' => '123', 'user' => 'steve', 'exp' => 1442690715989]
                        );
                    }
                );
            });
        });
    }

    public function testRsvp()
    {
        $this->describe('Ticket::rsvp()', function () {

            $this->it('errors on missing app', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid application object',
                    function() {
                        (new Ticket($this->password))->rsvp(
                            null,
                            ['id' => '123']
                        );
                    }
                );
            });

            $this->it('errors on invalid app', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid application object',
                    function() {
                        (new Ticket($this->password))->rsvp(
                            [],
                            ['id' => '123']
                        );
                    }
                );
            });

            $this->it('errors on missing grant', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid grant object',
                    function() {
                        (new Ticket($this->password))->rsvp(
                            ['id' => '123'],
                            null
                        );
                    }
                );
            });

            $this->it('errors on invalid grant', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid grant object',
                    function() {
                        (new Ticket($this->password))->rsvp(
                            ['id' => '123'],
                            []
                        );
                    }
                );
            });

            $this->it('errors on missing password', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid encryption password',
                    function() {
                        (new Ticket(''))->rsvp(
                            ['id' => '123'],
                            ['id' => '123']
                        );
                    }
                );
            });

            $this->it('constructs a valid rsvp', function () {
                $app = [
                    'id' => '123'
                ];
                $grant = [
                    'id' => 's81u29n1812'
                ];

                $ticketClass = new Ticket($this->password);

                $envelope = $ticketClass->rsvp($app, $grant);
                $object = $ticketClass->parse($envelope);

                expect($object['app'])->equals($app['id']);
                expect($object['grant'])->equals($grant['id']);
            });

            $this->it('fails to construct a valid rsvp due to bad Iron options', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Bad options',
                    function() {
                        $app = [
                            'id' => '123'
                        ];
                        $grant = [
                            'id' => 's81u29n1812'
                        ];

                        $iron = IronOptions::$defaults;
                        $iron['encryption'] = null;

                        $ticketClass = new Ticket($this->password, ['iron' => $iron]);

                        $envelope = $ticketClass->rsvp($app, $grant);
                        $object = $ticketClass->parse($envelope);
                    }
                );
            });
        });
    }

    public function testGenerate()
    {
        $this->describe('Ticket::generate()', function () {

            $this->it('errors on missing password', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Empty password',
                    function() {
                        (new Ticket(null))->generate([]);
                    }
                );
            });

            $this->it('generates a ticket with only public ext', function () {
                $input = [];
                $options = ['ext' => ['public' => ['x' => 1]]];
                $ticket = (new Ticket($this->password, $options))->generate($input);

                expect($ticket['ext']['x'])->equals(1);
            });

            $this->it('generates a ticket with only private ext', function () {
                $input = [];
                $options = ['ext' => ['private' => ['x' => 1]]];
                $ticket = (new Ticket($this->password, $options))->generate($input);

                expect(isset($ticket['ext']['x']))->false();
            });

            $this->it('overrides hawk options', function () {
                $input = [];
                $options = ['keyBytes' => 10, 'hmacAlgorithm' => 'something'];
                $ticket = (new Ticket($this->password, $options))->generate($input);

                expect(strlen($ticket['key']))->equals(10);
                expect($ticket['algorithm'])->equals('something');
            });
        });
    }

    public function testParse()
    {
        $this->describe('Ticket::parse()', function () {

            $this->it('errors on wrong password', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Bad HMAC value',
                    function() {
                        $app = [
                            'id' => '123'
                        ];
                        $grant = [
                            'id' => 's81u29n1812',
                            'user' => '456',
                            'exp' => (new HawkUtils)->now() + 5000,
                            'scope' => ['a', 'b']
                        ];
                        $options = [
                            'ttl' => 10 * 60 *1000
                        ];

                        $badPassword = 'a_password_that_is_not_too_short_and_also_not_very_random_but_is_good_enough_x';

                        $envelope = (new Ticket($this->password, $options))->issue($app, $grant);
                        $object = (new Ticket($badPassword, $options))->parse($envelope['id']);
                    }
                );
            });

            $this->it('errors on missing password', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'Invalid encryption password',
                    function() {
                         (new Ticket(''))->parse('abc');
                    }
                );
            });
        });
    }
}
