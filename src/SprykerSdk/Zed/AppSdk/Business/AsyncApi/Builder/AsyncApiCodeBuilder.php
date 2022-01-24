<?php

/**
 * Copyright © 2019-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerSdk\Zed\AppSdk\Business\AsyncApi\Builder;

use Generated\Shared\Transfer\AsyncApiRequestTransfer;
use Generated\Shared\Transfer\AsyncApiResponseTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use SprykerSdk\Zed\AppSdk\AppSdkConfig;
use SprykerSdk\Zed\AppSdk\Business\AsyncApi\AsyncApiInterface;
use SprykerSdk\Zed\AppSdk\Business\AsyncApi\Channel\AsyncApiChannelInterface;
use SprykerSdk\Zed\AppSdk\Business\AsyncApi\Loader\AsyncApiLoaderInterface;
use SprykerSdk\Zed\AppSdk\Business\AsyncApi\Message\AsyncApiMessageInterface;
use Symfony\Component\Process\Process;

class AsyncApiCodeBuilder implements AsyncApiCodeBuilderInterface
{
    /**
     * @var \SprykerSdk\Zed\AppSdk\AppSdkConfig
     */
    protected AppSdkConfig $config;

    /**
     * @var \SprykerSdk\Zed\AppSdk\Business\AsyncApi\Loader\AsyncApiLoaderInterface
     */
    protected AsyncApiLoaderInterface $asyncApiLoader;

    /**
     * @param \SprykerSdk\Zed\AppSdk\AppSdkConfig $config
     * @param \SprykerSdk\Zed\AppSdk\Business\AsyncApi\Loader\AsyncApiLoaderInterface $asyncApiLoader
     */
    public function __construct(AppSdkConfig $config, AsyncApiLoaderInterface $asyncApiLoader)
    {
        $this->config = $config;
        $this->asyncApiLoader = $asyncApiLoader;
    }

    /**
     * @param \Generated\Shared\Transfer\AsyncApiRequestTransfer $asyncApiRequestTransfer
     *
     * @return \Generated\Shared\Transfer\AsyncApiResponseTransfer
     */
    public function build(AsyncApiRequestTransfer $asyncApiRequestTransfer): AsyncApiResponseTransfer
    {
        $asyncApiResponseTransfer = new AsyncApiResponseTransfer();
        $asyncApi = $this->asyncApiLoader->load($asyncApiRequestTransfer->getTargetFileOrFail());

        $asyncApiResponseTransfer = $this->buildCodeForPublishMessagesChannels($asyncApi, $asyncApiResponseTransfer, $asyncApiRequestTransfer->getProjectNamespaceOrFail());
        $asyncApiResponseTransfer = $this->buildCodeForSubscribeMessagesChannels($asyncApi, $asyncApiResponseTransfer, $asyncApiRequestTransfer->getProjectNamespaceOrFail());

        return $asyncApiResponseTransfer;
    }

    /**
     * @param \SprykerSdk\Zed\AppSdk\Business\AsyncApi\AsyncApiInterface $asyncApi
     * @param \Generated\Shared\Transfer\AsyncApiResponseTransfer $asyncApiResponseTransfer
     * @param string $projectNamespace
     *
     * @return \Generated\Shared\Transfer\AsyncApiResponseTransfer
     */
    protected function buildCodeForPublishMessagesChannels(
        AsyncApiInterface $asyncApi,
        AsyncApiResponseTransfer $asyncApiResponseTransfer,
        string $projectNamespace
    ): AsyncApiResponseTransfer {
        foreach ($asyncApi->getChannels() as $channel) {
            $asyncApiResponseTransfer = $this->buildCodeForPublishMessages($channel, $asyncApiResponseTransfer, $projectNamespace);
        }

        return $asyncApiResponseTransfer;
    }

    /**
     * @param \SprykerSdk\Zed\AppSdk\Business\AsyncApi\AsyncApiInterface $asyncApi
     * @param \Generated\Shared\Transfer\AsyncApiResponseTransfer $asyncApiResponseTransfer
     * @param string $projectNamespace
     *
     * @return \Generated\Shared\Transfer\AsyncApiResponseTransfer
     */
    protected function buildCodeForSubscribeMessagesChannels(
        AsyncApiInterface $asyncApi,
        AsyncApiResponseTransfer $asyncApiResponseTransfer,
        string $projectNamespace
    ): AsyncApiResponseTransfer {
        foreach ($asyncApi->getChannels() as $channel) {
            $asyncApiResponseTransfer = $this->buildCodeForSubscribeMessages($channel, $asyncApiResponseTransfer, $projectNamespace);
        }

        return $asyncApiResponseTransfer;
    }

    /**
     * @param \SprykerSdk\Zed\AppSdk\Business\AsyncApi\Channel\AsyncApiChannelInterface $asyncApiChannel
     * @param \Generated\Shared\Transfer\AsyncApiResponseTransfer $asyncApiResponseTransfer
     * @param string $projectNamespace
     *
     * @return \Generated\Shared\Transfer\AsyncApiResponseTransfer
     */
    protected function buildCodeForPublishMessages(
        AsyncApiChannelInterface $asyncApiChannel,
        AsyncApiResponseTransfer $asyncApiResponseTransfer,
        string $projectNamespace
    ): AsyncApiResponseTransfer {
        foreach ($asyncApiChannel->getPublishMessages() as $asyncApiMessage) {
            $asyncApiResponseTransfer = $this->createTransferForMessage($asyncApiMessage, $asyncApiResponseTransfer, $projectNamespace);
            $asyncApiResponseTransfer = $this->createHandlerForMessage($asyncApiMessage, $asyncApiResponseTransfer, $projectNamespace);
        }

        return $asyncApiResponseTransfer;
    }

    /**
     * @param \SprykerSdk\Zed\AppSdk\Business\AsyncApi\Channel\AsyncApiChannelInterface $asyncApiChannel
     * @param \Generated\Shared\Transfer\AsyncApiResponseTransfer $asyncApiResponseTransfer
     * @param string $projectNamespace
     *
     * @return \Generated\Shared\Transfer\AsyncApiResponseTransfer
     */
    protected function buildCodeForSubscribeMessages(
        AsyncApiChannelInterface $asyncApiChannel,
        AsyncApiResponseTransfer $asyncApiResponseTransfer,
        string $projectNamespace
    ): AsyncApiResponseTransfer {
        foreach ($asyncApiChannel->getSubscribeMessages() as $asyncApiMessage) {
            $asyncApiResponseTransfer = $this->createTransferForMessage($asyncApiMessage, $asyncApiResponseTransfer, $projectNamespace);
        }

        return $asyncApiResponseTransfer;
    }

    /**
     * @param \SprykerSdk\Zed\AppSdk\Business\AsyncApi\Message\AsyncApiMessageInterface $asyncApiMessage
     * @param \Generated\Shared\Transfer\AsyncApiResponseTransfer $asyncApiResponseTransfer
     * @param string $projectNamespace
     *
     * @return \Generated\Shared\Transfer\AsyncApiResponseTransfer
     */
    protected function createTransferForMessage(
        AsyncApiMessageInterface $asyncApiMessage,
        AsyncApiResponseTransfer $asyncApiResponseTransfer,
        string $projectNamespace
    ): AsyncApiResponseTransfer {
        $commandLines = [];

        /** @var \SprykerSdk\Zed\AppSdk\Business\AsyncApi\Message\Attributes\AsyncApiMessageAttributeInterface $operationIdAttribute */
        $operationIdAttribute = $asyncApiMessage->getAttribute('operationId');
        /** @var string $moduleName */
        $moduleName = $operationIdAttribute->getValue();

        /** @var \SprykerSdk\Zed\AppSdk\Business\AsyncApi\Message\Attributes\AsyncApiMessageAttributeCollectionInterface $payload */
        $payload = $asyncApiMessage->getAttribute('payload');

        /** @var \SprykerSdk\Zed\AppSdk\Business\AsyncApi\Message\Attributes\AsyncApiMessageAttributeCollectionInterface $properties */
        $properties = $payload->getAttribute('properties');

        /** @var string $asyncApiMessageName */
        $asyncApiMessageName = $asyncApiMessage->getName();

        /** @var \SprykerSdk\Zed\AppSdk\Business\AsyncApi\Message\Attributes\AsyncApiMessageAttributeCollectionInterface $property */
        foreach ($properties->getAttributes() as $propertyName => $property) {
            /** @var \SprykerSdk\Zed\AppSdk\Business\AsyncApi\Message\Attributes\AsyncApiMessageAttributeInterface $typeAttribute */
            $typeAttribute = $property->getAttribute('type');
            /** @var string $type */
            $type = $typeAttribute->getValue();
            $commandLines[] = [
                'vendor/bin/spryk-run',
                'AddSharedTransferProperty',
                '--mode', 'project',
                '--organization', $projectNamespace,
                '--module', $moduleName,
                '--name', $asyncApiMessage->getName(),
                '--propertyName', $propertyName,
                '--propertyType', $type,
                '-n',
            ];
            $messageTransfer = new MessageTransfer();
            $messageTransfer->setMessage(sprintf('Added property "%s" with type "%s" to the "%sTransfer" transfer object of the module "%s".', $propertyName, $type, $asyncApiMessageName, $moduleName));
            $asyncApiResponseTransfer->addMessage($messageTransfer);
        }

        // Add messageAttributes to the Transfer
        $commandLines[] = [
            'vendor/bin/spryk-run',
            'AddSharedTransferProperty',
            '--mode', 'project',
            '--organization', $projectNamespace,
            '--module', $moduleName,
            '--name', $asyncApiMessage->getName(),
            '--propertyName', 'messageAttributes',
            '--propertyType', 'MessageAttributes',
            '-n',
        ];
        $messageTransfer = new MessageTransfer();
        $messageTransfer->setMessage(sprintf('Added property "messageAttributes" with type "MessageAttributesTransfer" to the "%sTransfer" transfer object of the module "%s".', $asyncApiMessage->getName(), $moduleName));
        $asyncApiResponseTransfer->addMessage($messageTransfer);

        $commandLines[] = [
            'vendor/bin/spryk-run',
            'AddSharedTransferDefinition',
            '--mode', 'project',
            '--organization', $projectNamespace,
            '--module', $moduleName,
            '--name', 'MessageAttributes',
            '-n',
        ];
        $messageTransfer = new MessageTransfer();
        $messageTransfer->setMessage(sprintf('Added transfer definition for "MessageAttributeTransfer" to the module "%s".', $moduleName));
        $asyncApiResponseTransfer->addMessage($messageTransfer);

        $this->runCommandLines($commandLines);

        return $asyncApiResponseTransfer;
    }

    /**
     * @param \SprykerSdk\Zed\AppSdk\Business\AsyncApi\Message\AsyncApiMessageInterface $asyncApiMessage
     * @param \Generated\Shared\Transfer\AsyncApiResponseTransfer $asyncApiResponseTransfer
     * @param string $projectNamespace
     *
     * @return \Generated\Shared\Transfer\AsyncApiResponseTransfer
     */
    protected function createHandlerForMessage(
        AsyncApiMessageInterface $asyncApiMessage,
        AsyncApiResponseTransfer $asyncApiResponseTransfer,
        string $projectNamespace
    ): AsyncApiResponseTransfer {
        $commandLines = [];
        /** @var \SprykerSdk\Zed\AppSdk\Business\AsyncApi\Message\Attributes\AsyncApiMessageAttributeInterface $moduleNameAttribute */
        $moduleNameAttribute = $asyncApiMessage->getAttribute('operationId');
        /** @var string $moduleName */
        $moduleName = $moduleNameAttribute->getValue();

        /** @var \SprykerSdk\Zed\AppSdk\Business\AsyncApi\Message\Attributes\AsyncApiMessageAttributeInterface $messageNameAttribute */
        $messageNameAttribute = $asyncApiMessage->getAttribute('name');
        /** @var string $messageName */
        $messageName = $messageNameAttribute->getValue();

        $commandLines[] = [
            'vendor/bin/spryk-run',
            'AddMessageBrokerHandlerPlugin',
            '--mode', 'project',
            '--organization', $projectNamespace,
            '--module', $moduleName,
            '--messageName', $messageName,
            '-n',
        ];

        $messageTransfer = new MessageTransfer();
        $messageTransfer->setMessage(sprintf('Added MessageHandlerPlugin for the message "%s" to the module "%s".', $messageName, $moduleName));
        $asyncApiResponseTransfer->addMessage($messageTransfer);

        $this->runCommandLines($commandLines);

        return $asyncApiResponseTransfer;
    }

    /**
     * @param array<array> $commandLines
     *
     * @return void
     */
    protected function runCommandLines(array $commandLines): void
    {
        foreach ($commandLines as $commandLine) {
            $process = new Process($commandLine, $this->config->getProjectRootPath());
            $process->run(function ($a, $buffer) {
                // For debugging purposes, set a breakpoint here to see issues.
            });
        }
    }
}
