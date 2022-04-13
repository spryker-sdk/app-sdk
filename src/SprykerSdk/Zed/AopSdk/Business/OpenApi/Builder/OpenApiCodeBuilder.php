<?php

/**
 * Copyright © 2019-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerSdk\Zed\AopSdk\Business\OpenApi\Builder;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Schema;
use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Generated\Shared\Transfer\MessageTransfer;
use Generated\Shared\Transfer\OpenApiRequestTransfer;
use Generated\Shared\Transfer\OpenApiResponseTransfer;
use SprykerSdk\Zed\AopSdk\AopSdkConfig;
use Symfony\Component\Process\Process;

class OpenApiCodeBuilder implements OpenApiCodeBuilderInterface
{
    /**
     * @var string
     */
    protected const APPSRESOURCE = 'AppsResource';

    /**
     * @var string
     */
    protected const SPRYKER = 'Spryker';

    /**
     * @var string
     */
    protected const CORE = 'core';

    /**
     * @var string
     */
    protected const ATTRIBUTE_SUFFIX = 'Attributes';

    /**
     * @var \SprykerSdk\Zed\AopSdk\AopSdkConfig
     */
    protected AopSdkConfig $config;

    /**
     * @var string
     */
    protected string $sprykMode = 'project';

    /**
     * @var string
     */
    protected string $moduleName = '';

    /**
     * @var string
     */
    protected string $organization = '';

    /**
     * @var array
     */
    protected array $parsedData = [];

    /**
     * @param \SprykerSdk\Zed\AopSdk\AopSdkConfig $config
     */
    public function __construct(AopSdkConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param \Generated\Shared\Transfer\OpenApiRequestTransfer $openApiRequestTransfer
     *
     * @return \Generated\Shared\Transfer\OpenApiResponseTransfer
     */
    public function build(OpenApiRequestTransfer $openApiRequestTransfer): OpenApiResponseTransfer
    {
        $openApiResponseTransfer = new OpenApiResponseTransfer();
        $openApi = $this->load($openApiRequestTransfer->getTargetFileOrFail());

        //Set default value for organization
        $this->organization = $openApiRequestTransfer->getOrganizationOrFail();

        if ($this->organization === static::SPRYKER) {
            $this->sprykMode = static::CORE; //Set sprykMode based on organization
        }

        $openApiResponseTransfer = $this->buildCodeForOpenApi($openApi, $openApiResponseTransfer);

        if ($openApiResponseTransfer->getMessages()->count() === 0) {
            $messageTransfer = new MessageTransfer();
            $messageTransfer->setMessage('Something went wrong. Either not channels have been found or the channels do not have messages defined.');
            $openApiResponseTransfer->addError($messageTransfer);
        }

        return $openApiResponseTransfer;
    }

    /**
     * @param string $openApiFilePath
     *
     * @return \cebe\openapi\spec\OpenApi
     */
    public function load(string $openApiFilePath): OpenApi
    {
        return Reader::readFromYamlFile(realpath($openApiFilePath));
    }

    /**
     * @param string $resourceUri
     *
     * @return string
     */
    protected function getModuleName(string $resourceUri): string
    {
        $resources = explode('/', trim($resourceUri, '/'));

        return ucwords(current($resources)) . 'Api';
    }

    /**
     * @param string $resourceUri
     *
     * @return string
     */
    protected function getController(string $resourceUri): string
    {
        $controllerName = static::APPSRESOURCE;

        $resources = explode('/', trim($resourceUri, '/'));

        $lastResource = end($resources);

        if (count($resources) > 1 && is_string($lastResource) && strpos($lastResource, '{') === false) {
            $controllerName = $this->getInflectorFactory()->classify($lastResource);
        }

        return "{$controllerName}Controller";
    }

    /**
     * @return \Doctrine\Inflector\Inflector
     */
    protected function getInflectorFactory(): Inflector
    {
        return InflectorFactory::create()->build();
    }

    /**
     * @param \cebe\openapi\spec\OpenApi $openApi
     * @param \Generated\Shared\Transfer\OpenApiResponseTransfer $openApiResponseTransfer
     *
     * @return \Generated\Shared\Transfer\OpenApiResponseTransfer
     */
    protected function buildCodeForOpenApi(
        OpenApi $openApi,
        OpenApiResponseTransfer $openApiResponseTransfer
    ): OpenApiResponseTransfer {
        /** @var \cebe\openapi\spec\PathItem $pathItem */
        foreach ($openApi->paths->getPaths() as $path => $pathItem) {
            //Parse data api path wise
            $this->parseApiPathItem($path, $pathItem);
        }

        //Generating Commands using parsed data
        $this->generateCommands();

        return $openApiResponseTransfer;
    }

    /**
     * @param string $path
     * @param \cebe\openapi\spec\PathItem $pathItem
     *
     * @return void
     */
    protected function parseApiPathItem(string $path, PathItem $pathItem): void
    {
        /** @var \cebe\openapi\spec\Operation $operation */
        foreach ($pathItem->getOperations() as $method => $operation) {
            //Set Module Name
            $this->parsedData[$path][$method]['moduleName'] = $this->getModuleName($path);
            //Set Controller Name
            $this->parsedData[$path][$method]['controllerName'] = $this->getController($path);

            if ($operation->requestBody) {
                //Set Request Body by parsing
                $this->parseRequestBody($operation, $path, $method);
            }

            //Set Response Body by parsing
            $this->parseResponseBody($operation, $path, $method);
        }
    }

    /**
     * @param \cebe\openapi\spec\Operation $operation
     * @param string $path
     * @param string $method
     *
     * @return void
     */
    protected function parseRequestBody(Operation $operation, string $path, string $method): void
    {
        /** @var \cebe\openapi\spec\RequestBody $mediaType */
        foreach ($operation->requestBody->content as $mediaType) {
            $this->parsedData[$path][$method]['requestBody'][$this->getPathUsingSchema($mediaType->schema)] = $this->requestBodyParser($mediaType->schema);
        }
    }

    /**
     * @param \cebe\openapi\spec\Schema $schema
     *
     * @return array
     */
    protected function requestBodyParser(Schema $schema): array
    {
        $response = [];

        foreach ($schema->properties as $key => $schemaObject) {
            if ($schemaObject->properties) {
                return $this->requestBodyParser($schemaObject);
            }
        }

        foreach ($schema->properties as $key => $schemaObject) {
            if ($schemaObject->type == 'array') {
                if (isset($schemaObject->items->properties['type'])) {
                    $response[$key] = 'array[]:' . $schemaObject->items->properties['type']->type;
                } elseif (isset($schemaObject->items->type)) {
                    $response[$key] = 'array[]:' . $schemaObject->items->type;
                } else {
                    $response[$this->getInflectorFactory()->camelize($this->getPathUsingSchema($schema))] = $this->getPathUsingSchema($schemaObject->items) . '[]:' . $this->getInflectorFactory()->camelize($this->getPathUsingSchema($schemaObject->items));
                }
            } else {
                $response[$key] = $schemaObject->type;
            }
        }

        return $response;
    }

    /**
     * @param \cebe\openapi\spec\Operation $operation
     * @param string $path
     * @param string $method
     *
     * @return void
     */
    protected function parseResponseBody(Operation $operation, string $path, string $method): void
    {
        /** @var \cebe\openapi\spec\Response|\cebe\openapi\spec\Reference $content */
        foreach ($operation->responses as $status => $content) {
            if ($content->content) {
                foreach ($content->content as $response) {
                    $this->parsedData[$path][$method]['responses'][$this->getPathUsingSchema($response->schema)] =
                    $this->responseBodyParser($response->schema);
                }
            }
        }
    }

    /**
     * @param \cebe\openapi\spec\Schema $schema
     *
     * @return array
     */
    protected function responseBodyParser(Schema $schema): array
    {
        $response = [];

        foreach ($schema->properties as $key => $schemaObject) {
            if ($schemaObject->properties) {
                return $this->responseBodyParser($schemaObject);
            }

            if ($schemaObject->type == 'array') {
                if (isset($schemaObject->items->properties['type'])) {
                    $response[$key] = 'array[]:' . $schemaObject->items->properties['type']->type;
                } elseif (isset($schemaObject->items->type)) {
                    $response[$key] = 'array[]:' . $schemaObject->items->type;
                } else {
                    $response[$this->getInflectorFactory()->camelize($this->getPathUsingSchema($schema))] = $this->getPathUsingSchema($schemaObject->items) . '[]:' . $this->getInflectorFactory()->camelize($this->getPathUsingSchema($schemaObject->items));
                }
            } else {
                $response[$key] = $schemaObject->type;
            }
        }

        return $response;
    }

    /**
     * @param \SprykerSdk\Zed\AopSdk\Business\OpenApi\Builder\cebe\openapi\spec\Schema $schema
     *
     * @return string
     */
    protected function getPathUsingSchema(Schema $schema): string
    {
        $referencePath = $schema->getDocumentPosition()->getPath();

        return end($referencePath);
    }

    /**
     * @param array $parameters
     *
     * @return array
     */
    protected function generateParameters(array $parameters): array
    {
        $response = [];
        foreach ($parameters as $key => $value) {
            $response[] = "{$key}:{$value}";
        }

        return $response;
    }

    /**
     * @return void
     */
    protected function generateCommands(): void
    {
        $commandLines = [];

        foreach ($this->parsedData as $uri => $operations) {
            foreach ($operations as $method => $data) {
                if (isset($data['requestBody'])) {
                    foreach ($data['requestBody'] as $command => $parameters) {
                        $commandLines[$command] = [
                            'vendor/bin/spryk-run',
                            'AddSharedTransferProperty',
                            '--mode', $this->sprykMode,
                            '--organization', $this->organization,
                            '--module', $data['moduleName'],
                            '--name', $command,
                            '--propertyName', implode(',', $this->generateParameters($parameters)),
                            '-n',
                            '-v',
                        ];
                    }
                }

                if (isset($data['responses'])) {
                    foreach ($data['responses'] as $command => $parameters) {
                        $commandLines[$command] = [
                            'vendor/bin/spryk-run',
                            'AddSharedTransferProperty',
                            '--mode', $this->sprykMode,
                            '--organization', $this->organization,
                            '--module', $data['moduleName'],
                            '--name', $command,
                            '--propertyName', implode(',', $this->generateParameters($parameters)),
                            '-n',
                            '-v',
                        ];
                    }
                }
            }
        }

        dd(array_values($commandLines));
    }

    /**
     * @codeCoverageIgnore
     *
     * @param array<array> $commandLines
     *
     * @return void
     */
    protected function runCommandLines(array $commandLines): void
    {
        foreach ($commandLines as $commandLine) {
            $process = new Process($commandLine, $this->config->getProjectRootPath());
            $process->run(function ($a, $buffer) {
                echo $buffer;
                // For debugging purposes, set a breakpoint here to see issues.
            });
        }
    }
}
