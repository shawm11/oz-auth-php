<?php

namespace Shawm11\Oz\Tests\Client;

use PHPUnit\Framework\TestCase;
use Shawm11\Oz\Client\Client;

class ClientTest extends TestCase
{
    use \Codeception\Specify;
    use \Codeception\AssertThrows;

    public function testHeader(): void
    {
        $this->describe('Client::header()', function () {

            $this->it('generates header', function () {
                $app = [
                    'id' => 'social',
                    'scope' => ['a', 'b', 'c'],
                    'key' => 'werxhqb98rpaxn39848xrunpaw3489ruxnpa98w4rxn',
                    'algorithm' => 'sha256'
                ];

                $header = (new Client)->header('http://example.com/oz/app', 'POST', $app);

                expect($header)->notEmpty();
            });
        });
    }
}
