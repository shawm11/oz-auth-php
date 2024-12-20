<?php

namespace Shawm11\Oz\Tests\Server;

use PHPUnit\Framework\TestCase;
use Shawm11\Oz\Server\Scope;
use Shawm11\Oz\Server\ServerException;
use Shawm11\Oz\Server\BadRequestException;

class ScopeTest extends TestCase
{
    use \Codeception\Specify;
    use \Codeception\AssertThrows;

    public function testValidate(): void
    {
        $this->describe('Scope::validate()', function () {

            $this->it('should return null for valid scope', function () {
                expect((new Scope)->validate(['a', 'b', 'c']))->toBeNull();
            });

            $this->it('should throw error when scope is null', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'null scope',
                    function() {
                        // @phpstan-ignore argument.type
            	        (new Scope)->validate(null);
                    }
                );
            });

            $this->it('should throw error when scope is not an array', function () {
                $this->assertThrowsWithMessage(
                    ServerException::class,
                    'scope not an array',
                    function() {
                        // @phpstan-ignore argument.type
            	        (new Scope)->validate('hello');
                    }
                );
            });

            $this->it('should throw error when scope contains non-string values', function () {
                $this->assertThrowsWithMessage(
                    BadRequestException::class,
                    'scope item is not a string',
                    function() {
            	        (new Scope)->validate(['a', 'b', 1]);
                    }
                );
            });

            $this->it('should throw error when scope contains duplicates', function () {
                $this->assertThrowsWithMessage(
                    BadRequestException::class,
                    'scope includes duplicated item',
                    function() {
            	        (new Scope)->validate(['a', 'b', 'b']);
                    }
                );
            });

            $this->it('should throw error when scope contains empty strings', function () {
                $this->assertThrowsWithMessage(
                    BadRequestException::class,
                    'scope includes null or empty string value',
                    function() {
            	        (new Scope)->validate(['a', 'b', '']);
                    }
                );
            });
        });
    }

    public function testIsSubset(): void
    {
        $this->describe('Scope::isSubset()', function () {

            $this->it('should return true when scope is a subset', function () {
                $scope = ['a', 'b', 'c'];
                $subset = ['a', 'c'];

                expect((new Scope)->isSubset($scope, $subset))->toBeTrue();
            });

            $this->it('should return false when scope is not a subset', function () {
                $scope = ['a'];
                $subset = ['a', 'c'];

                expect((new Scope)->isSubset($scope, $subset))->toBeFalse();
            });

            $this->it('should return false when scope is not a subset but equal length', function () {
                $scope = ['a', 'b'];
                $subset = ['a', 'c'];

                expect((new Scope)->isSubset($scope, $subset))->toBeFalse();
            });
        });
    }

    public function testIsEqual(): void
    {
        $this->describe('Scope::isEqual()', function () {
            $scope = ['a', 'b', 'c'];

            $this->it('compares scopes', function ($one, $two, $result) {
                expect((new Scope)->isEqual($one, $two))->toEqual($result);
            }, [
                'examples' => [
                    [null, null, true],
                    [$scope, $scope, true],
                    [null, $scope, false],
                    [$scope, null, false],
                    [$scope, [], false],
                    [[], $scope, false],
                    [$scope, ['a', 'b', 'c'], true],
                    [$scope, ['a', 'b', 'd'], false],
                    [['a', 'b', 'c'], $scope, true],
                    [['a', 'b', 'd'], $scope, false],
                ]
            ]);
        });
    }
}
