<?php

namespace Shawm11\Oz\Server;

class Scope implements ScopeInterface
{
    /**
     * {@inheritdoc}
     */
    public function validate($scope)
    {
        if (!$scope && $scope !== []) {
            throw new ServerException('null scope');
        }

        if (gettype($scope) !== 'array') {
            throw new ServerException('scope not an array');
        }

        $hash = [];

        foreach ($scope as $scopeItem) {
            if (!$scopeItem) {
                throw new BadRequestException('scope includes null or empty string value');
            }

            if (gettype($scopeItem) !== 'string') {
                throw new BadRequestException('scope item is not a string');
            }

            if (isset($hash[$scopeItem])) {
                throw new BadRequestException('scope includes duplicated item');
            }

            $hash[$scopeItem] = true;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isSubset($scope, $subset)
    {
        if (!$scope) {
            return false;
        }

        if (count($scope) < count($subset)) {
            return false;
        }

        $common = array_intersect($scope, $subset);

        return count($common) === count($subset);
    }

    public function isEqual($one, $two)
    {
        return $one === $two;
    }
}
