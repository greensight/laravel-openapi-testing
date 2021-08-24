<?php

namespace Greensight\LaravelOpenApiTesting;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use LogicException;
use Osteel\OpenApi\Testing\ValidatorInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

trait ValidatesAgainstOpenApiSpec
{
    protected bool $_skipNextOpenApiRequestValidation = false;
    protected bool $_skipNextOpenApiResponseValidation = false;
    protected string $_forcedOpenApiPath = '';

    protected function getOpenApiDocumentPath(): string
    {
        // Override me with smth like `return public_path('api-docs/v1/index.yaml');`
        return '';
    }

    /**
     * Call the given URI and return the Response.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $parameters
     * @param  array  $cookies
     * @param  array  $files
     * @param  array  $server
     * @param  string|null  $content
     * @return \Illuminate\Testing\TestResponse
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $kernel = $this->app->make(HttpKernel::class);

        $files = array_merge($files, $this->extractFilesFromDataArray($parameters));

        $symfonyRequest = SymfonyRequest::create(
            $this->prepareUrlForRequest($uri),
            $method,
            $parameters,
            $cookies,
            $files,
            array_replace($this->serverVariables, $server),
            $content
        );

        $response = $kernel->handle(
            $request = Request::createFromBase($symfonyRequest)
        );

        $kernel->terminate($request, $response);

        if ($this->followRedirects) {
            $response = $this->followRedirects($response);
        }

        $this->validateAgainstOpenApiSpec($request, $symfonyRequest, $response, $method);

        return $this->createTestResponse($response);
    }

    public function validateAgainstOpenApiSpec(Request $request, SymfonyRequest $symfonyRequest, SymfonyResponse $response, string $method): void 
    {
        if ($this->_skipNextOpenApiRequestValidation && $this->_skipNextOpenApiResponseValidation) {
            return;
        }

        $openApiPath = $this->getOpenApiPathForRequest($request);
        $validator = $this->buildOpenApiValidator();
        $this->assertOpenApiRequest($symfonyRequest, $validator, $method, $openApiPath);
        $this->assertOpenApiResponse($response, $validator, $method, $openApiPath);
    }

    protected function buildOpenApiValidator(): ValidatorInterface
    {
        $yamlPath = $this->getOpenApiDocumentPath();
        if (!$yamlPath) {
            throw new LogicException('You need to override ValidatesAgainstOpenApiSpec::getOpenApiDocumentPath() and set correct path there');
        }

        return CachedValidator::fromYaml($yamlPath);
    }

    protected function forceOpenApiPath(string $path)
    {
        $this->_forcedOpenApiPath = $path;

        return $this;
    }

    protected function getOpenApiPathForRequest(Request $request)
    {
        if ($this->_forcedOpenApiPath) {
            $path = $this->_forcedOpenApiPath;
            $this->_forcedOpenApiPath = '';

            return $path;
        }

        if ($request->route()?->uri) {
            return "/" . ltrim($request->route()?->uri, "/");
        }

        return $request->getRequestUri();
    }

    protected function skipNextOpenApiValidation(): static
    {
        return $this->skipNextOpenApiRequestValidation()->skipNextOpenApiResponseValidation();
    }

    protected function assertOpenApiRequest(SymfonyRequest $request, ValidatorInterface $validator, string $method, string $uri): void
    {
        if ($this->_skipNextOpenApiRequestValidation) {
            $this->_skipNextOpenApiRequestValidation = false;

            return;
        }

        $this->assertTrue($validator->validate($request, $uri, $method));
    }

    protected function skipNextOpenApiRequestValidation(): static
    {
        $this->_skipNextOpenApiRequestValidation = true;

        return $this;
    }

    protected function assertOpenApiResponse(SymfonyResponse $response, ValidatorInterface $validator, string $method, string $uri): void
    {
        if ($this->_skipNextOpenApiResponseValidation) {
            $this->_skipNextOpenApiResponseValidation = false;

            return;
        }

        $this->assertTrue($validator->validate($response, $uri, $method));
    }

    protected function skipNextOpenApiResponseValidation(): static
    {
        $this->_skipNextOpenApiResponseValidation = true;

        return $this;
    }
}
