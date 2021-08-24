<?php

use Greensight\LaravelOpenApiTesting\CachedValidator;

test("cached validator references the same base Validator if paths are the same", function () {
    $validator1 = CachedValidator::fromYaml(__DIR__ . "/stubs/index1.yaml");
    $validator2 = CachedValidator::fromYaml(__DIR__ . "/stubs/index1.yaml");

    expect($validator1)->toBe($validator2);
});

test("cached validator references separate base Validators if paths are different", function () {
    $validator1 = CachedValidator::fromYaml(__DIR__ . "/stubs/index1.yaml");
    $validator2 = CachedValidator::fromYaml(__DIR__ . "/stubs/index2.yaml");

    expect($validator1)->not->toBe($validator2);
});