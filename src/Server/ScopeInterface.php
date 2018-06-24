<?php

namespace Shawm11\Oz\Server;

interface ScopeInterface
{
    /**
     * Validate a scope for proper structure (an array of unique strings)
     *
     * @param  array  $scope  The array being validated
     * @throws ServerException
     * @return null  Returns if the scope is valid
     */
    public function validate($scope);

    /**
     * Check if one scope is a subset of another
     *
     * @param array  $scope  The superset
     * @param array  $subset  The subset
     * @return boolean  If $subset is fully contained with $scope
     */
    public function isSubset($scope, $subset);

    /**
     *  Check if two scope arrays are the same
     *
     * @param  array  $one  First of the two scopes being compared
     * @param  array  $two  Second of the two scopes being compared
     * @return boolean  If $one is equal to $two
     */
    public function isEqual($one, $two);
}
