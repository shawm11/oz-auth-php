<?php

namespace Shawm11\Oz\Tests;

use PHPUnit\Framework\TestCase;
use Shawm11\Hawk\Utils\Utils as HawkUtils;
use Shawm11\Oz\Client\Client as OzClient;
use Shawm11\Oz\Server\Endpoints as OzEndpoints;
use Shawm11\Oz\Server\Ticket as OzTicket;

class OzTest extends TestCase
{
    use \Codeception\Specify;

    public function testOz(): void
    {
        $this->describe('Oz', function () {

            $this->it('runs a full RSVP flow', function () {
                $ozEndpoints = new OzEndpoints;
                $ozClient = new OzClient;

                $encryptionPassword = 'a_password_that_is_not_too_short_and_also_not_very_random_but_is_good_enough';
                $apps = [
                    'social' => [
                        'id' => 'social',
                        'scope' => ['a', 'b', 'c'],
                        'key' => 'werxhqb98rpaxn39848xrunpaw3489ruxnpa98w4rxn',
                        'algorithm' => 'sha256',
                        'delegate' => true
                    ],
                    'network' => [
                        'id' => 'network',
                        'scope' => ['b', 's'],
                        'key' => 'witf745itwn7ey4otnw7eyi4t7syeir7bytise7rbyi',
                        'algorithm' => 'sha256'
                    ]
                ];

                /*
                 * 1. The app requests an app ticket using Oz.hawk
                 *    authentication
                 */

                $req = [
                    'method' => 'POST',
                    'url' => '/oz/app',
                    'host' => 'example.com',
                    'port' => 443,
                    'authorization' => $ozClient->header(
                        'https://example.com/oz/app',
                        'POST',
                        $apps['social']
                    )['header']
                ];

                $options = [
                    'encryptionPassword' => $encryptionPassword,
                    'loadAppFunc' => function ($id) use ($apps){
                        return $apps[$id];
                    }
                ];

                $appTicket = $ozEndpoints->app($req, $options);

                /*
                 * 2. The app refreshes its own ticket
                 */

                $req = [
                    'method' => 'POST',
                    'url' => '/oz/reissue',
                    'host' => 'example.com',
                    'port' => 443,
                    'authorization' => $ozClient->header(
                        'https://example.com/oz/reissue',
                        'POST',
                        $appTicket
                    )['header']
                ];

                $reAppTicket = $ozEndpoints->reissue($req, [], $options);

                /*
                 * 3. The user is redirected to the server, logs in, and grant
                 *    the app access, resulting in an RSVP
                 */

                $grant = [
                    'id' => 'a1b2c3d4e5f6g7h8i9j0',
                    'app' => $reAppTicket['app'],
                    'user' => 'john',
                    'exp' => (new HawkUtils)->now() + 60000
                ];

                $rsvp = (new OzTicket($encryptionPassword))->rsvp($apps['social'], $grant);

                /*
                 * 4. After granting the app access, the user returns to the app
                 *    with the RSVP
                 */

                $options['loadGrantFunc'] = function ($id) use ($grant) {
                    return [
                        'grant' => $grant,
                        'ext' => [
                            'public' => 'everybody knows',
                            'private' => 'the the dice are loaded'
                        ]
                    ];
                };

                /*
                 * 5. The app exchanges the rsvp for a ticket
                 */

                $payload = ['rsvp' => $rsvp];

                $req = [
                    'method' => 'POST',
                    'url' => '/oz/rsvp',
                    'host' => 'example.com',
                    'port' => 443,
                    'authorization' => $ozClient->header(
                        'https://example.com/oz/rsvp',
                        'POST',
                        $reAppTicket
                    )['header']
                ];

                $userTicket = $ozEndpoints->rsvp($req, $payload, $options);

                /*
                 * 6. The app reissues the ticket with delegation to another app
                 */

                $payload = [
                    'issueTo' => $apps['network']['id'],
                    'scope' => ['a']
                ];

                $req = [
                    'method' => 'POST',
                    'url' => '/oz/reissue',
                    'host' => 'example.com',
                    'port' => 443,
                    'authorization' => $ozClient->header(
                        'https://example.com/oz/reissue',
                        'POST',
                        $userTicket
                    )['header']
                ];

                $delegatedTicket = $ozEndpoints->reissue($req, $payload, $options);

                /*
                 * 7. The other app reissues their ticket
                 */

                $req = [
                    'method' => 'POST',
                    'url' => '/oz/reissue',
                    'host' => 'example.com',
                    'port' => 443,
                    'authorization' => $ozClient->header(
                        'https://example.com/oz/reissue',
                        'POST',
                        $delegatedTicket
                    )['header']
                ];

                expect($ozEndpoints->reissue($req, [], $options)['id'])->notEmpty();
            });

            $this->it('runs a full User Credentials flow', function () {
                $ozEndpoints = new OzEndpoints;
                $ozClient = new OzClient;

                $encryptionPassword = 'a_password_that_is_not_too_short_and_also_not_very_random_but_is_good_enough';
                $grant = [];
                $apps = [
                    'social' => [
                        'id' => 'social',
                        'scope' => ['a', 'b', 'c'],
                        'key' => 'werxhqb98rpaxn39848xrunpaw3489ruxnpa98w4rxn',
                        'algorithm' => 'sha256',
                        'delegate' => true
                    ],
                    'network' => [
                        'id' => 'network',
                        'scope' => ['b', 's'],
                        'key' => 'witf745itwn7ey4otnw7eyi4t7syeir7bytise7rbyi',
                        'algorithm' => 'sha256'
                    ]
                ];

                /*
                 * 1. The app requests an app ticket using Oz.hawk
                 *    authentication
                 */

                $req = [
                    'method' => 'POST',
                    'url' => '/oz/app',
                    'host' => 'example.com',
                    'port' => 443,
                    'authorization' => $ozClient->header(
                        'https://example.com/oz/app',
                        'POST',
                        $apps['social']
                    )['header']
                ];

                $options = [
                    'encryptionPassword' => $encryptionPassword,
                    'loadAppFunc' => function ($id) use ($apps){
                        return $apps[$id];
                    }
                ];

                $appTicket = $ozEndpoints->app($req, $options);

                /*
                 * 2. The app refreshes its own ticket
                 */

                $req = [
                    'method' => 'POST',
                    'url' => '/oz/reissue',
                    'host' => 'example.com',
                    'port' => 443,
                    'authorization' => $ozClient->header(
                        'https://example.com/oz/reissue',
                        'POST',
                        $appTicket
                    )['header']
                ];

                $reAppTicket = $ozEndpoints->reissue($req, [], $options);

                /*
                 * 3. User gives his credentials to the app
                 */

                $userCredentials = [
                    'username' => 'johns_account',
                    'password' => 'j0hns_p4$$w0rd'
                ];

                /*
                 * 4. The app requests a user ticket using the user's
                 *    credentials securely
                 */

                $payload = ['user' => $userCredentials];

                $req = [
                    'method' => 'POST',
                    'url' => '/oz/user',
                    'host' => 'example.com',
                    'port' => 443,
                    'authorization' => $ozClient->header(
                        'https://example.com/oz/user',
                        'POST',
                        $reAppTicket
                    )['header']
                ];

                $options['grant'] = [
                    'exp' => (new HawkUtils)->now() + 60000
                ];
                $options['verifyUserFunc'] = function ($userCreds) {
                    return 'john'; // User ID
                };
                $options['storeGrantFunc'] = function ($grantObj) use (&$grant) {
                    $grant = $grantObj;
                    $grant['id'] = 'a1b2c3d4e5f6g7h8i9j0';
                    return $grant['id'];
                };

                $userTicket = $ozEndpoints->user($req, $payload, $options);

                expect($userTicket['app'])->notNull();

                /*
                 * 5. The app reissues the ticket with delegation to another app
                 */

                $payload = [
                    'issueTo' => $apps['network']['id'],
                    'scope' => ['a']
                ];

                $req = [
                    'method' => 'POST',
                    'url' => '/oz/reissue',
                    'host' => 'example.com',
                    'port' => 443,
                    'authorization' => $ozClient->header(
                        'https://example.com/oz/reissue',
                        'POST',
                        $userTicket
                    )['header']
                ];

                $options['loadGrantFunc'] = function ($id) use ($grant) {
                    return [
                        'grant' => $grant,
                        'ext' => [
                            'public' => 'everybody knows',
                            'private' => 'the the dice are loaded'
                        ]
                    ];
                };

                $delegatedTicket = $ozEndpoints->reissue($req, $payload, $options);

                /*
                 * 6. The other app reissues their ticket
                 */

                $req = [
                    'method' => 'POST',
                    'url' => '/oz/reissue',
                    'host' => 'example.com',
                    'port' => 443,
                    'authorization' => $ozClient->header(
                        'https://example.com/oz/reissue',
                        'POST',
                        $delegatedTicket
                    )['header']
                ];

                expect($ozEndpoints->reissue($req, [], $options)['id'])->notEmpty();
            });

            $this->it('runs a full Implicit flow', function () {
                $ozEndpoints = new OzEndpoints;
                $ozClient = new OzClient;

                $grant = [];
                $encryptionPassword = 'a_password_that_is_not_too_short_and_also_not_very_random_but_is_good_enough';

                /*
                 * 1. User gives his credentials to the app
                 */

                $userCredentials = [
                    'username' => 'johns_account',
                    'password' => 'j0hns_p4$$w0rd'
                ];

                /*
                 * 2. The app requests a user ticket using the user's
                 *    credentials securely
                 */

                $payload = ['user' => $userCredentials];

                $req = [
                    'method' => 'POST',
                    'url' => '/oz/user',
                    'host' => 'example.com',
                    'port' => 443
                ];

                $options = [
                    'encryptionPassword' => $encryptionPassword,
                    'grant' => [
                        'exp' => (new HawkUtils)->now() + 60000
                    ],
                    'verifyUserFunc' => function ($userCreds) {
                        return 'john'; // User ID
                    },
                    'storeGrantFunc' => function ($grantObj) use (&$grant) {
                        $grant = $grantObj;
                        $grant['id'] = 'a1b2c3d4e5f6g7h8i9j0';
                        return $grant['id'];
                    }
                ];

                $userTicket = $ozEndpoints->user($req, $payload, $options);

                expect($userTicket['app'])->null();

                /*
                 * 3. The app refreshes the user ticket
                 */

                 $req = [
                     'method' => 'POST',
                     'url' => '/oz/reissue',
                     'host' => 'example.com',
                     'port' => 443,
                     'authorization' => $ozClient->header(
                         'https://example.com/oz/reissue',
                         'POST',
                         $userTicket
                     )['header']
                 ];

                 $options['loadGrantFunc'] = function ($id) use ($grant) {
                     return [
                         'grant' => $grant,
                         'ext' => [
                             'public' => 'everybody knows',
                             'private' => 'the the dice are loaded'
                         ]
                     ];
                 };

                expect($ozEndpoints->reissue($req, [], $options)['id'])->notEmpty();
            });
        });
    }
}
