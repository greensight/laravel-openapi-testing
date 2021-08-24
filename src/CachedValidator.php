<?php

namespace Greensight\LaravelOpenApiTesting;

use Osteel\OpenApi\Testing\ValidatorBuilder;
use Osteel\OpenApi\Testing\ValidatorInterface;

class CachedValidator
{
    protected static $map = []; // shared across all test "oas doc path" => "validator"

    public static function fromYaml(string $path): ValidatorInterface
    {
        return self::$map[$path] ??= ValidatorBuilder::fromYaml($path)->getValidator();
    }

    public static function fromJson(string $path): ValidatorInterface 
    {
        return self::$map[$path] ??= ValidatorBuilder::fromJson($path)->getValidator();
    }
}
