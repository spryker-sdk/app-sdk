<?php

/**
 * Copyright © 2019-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerSdk\Zed\AopSdk\Communication\Console;

use Generated\Shared\Transfer\OpenApiRequestTransfer;
use Generated\Shared\Transfer\OpenApiResponseTransfer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \SprykerSdk\Zed\AopSdk\Business\AopSdkFacadeInterface getFacade()
 */
class BuildCodeFromOpenApiConsole extends AbstractConsole
{
    /**
     * @var string
     */
    public const OPTION_OPEN_API_FILE = 'openapi-file';

    /**
     * @var string
     */
    public const OPTION_OPEN_API_FILE_SHORT = 'a';

    /**
     * @var string
     */
    public const APPLICATION_TYPE = 'application-type';

    /**
     * @var string
     */
    public const APPLICATION_TYPE_SHORT = 't';

    /**
     * @var string
     */
    public const OPTION_ORGANIZATION = 'organization';

    /**
     * @var string
     */
    public const OPTION_ORGANIZATION_SHORT = 'o';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('build:from:openapi')
            ->setDescription('Builds code from an OpenAPI file definition.')
            ->addOption(static::OPTION_OPEN_API_FILE, static::OPTION_OPEN_API_FILE_SHORT, InputOption::VALUE_REQUIRED, '', $this->getConfig()->getDefaultOpenApiFile())
            ->addOption(static::APPLICATION_TYPE, static::APPLICATION_TYPE_SHORT, InputOption::VALUE_REQUIRED, '', 'backend')
            ->addOption(static::OPTION_ORGANIZATION, static::OPTION_ORGANIZATION_SHORT, InputOption::VALUE_REQUIRED, 'Namespace that should be used for the code builder. When set to Spryker code will be generated in the core modules.', 'App');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $openApiRequestTransfer = new OpenApiRequestTransfer();
        $openApiRequestTransfer
            ->setTargetFile($input->getOption(static::OPTION_OPEN_API_FILE))
            ->setApplicationType($input->getOption(static::APPLICATION_TYPE))
            ->setOrganization($input->getOption(static::OPTION_ORGANIZATION));

        $openApiResponseTransfer = $this->getFacade()->buildFromOpenApi($openApiRequestTransfer);

        if ($openApiResponseTransfer->getErrors()->count() === 0) {
            $this->printMessages($openApiResponseTransfer, $output);

            return static::CODE_SUCCESS;
        }

        $this->printErrors($openApiResponseTransfer, $output);

        return static::CODE_ERROR;
    }

    /**
     * @param \Generated\Shared\Transfer\OpenApiResponseTransfer $openApiResponseTransfer
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    protected function printMessages(OpenApiResponseTransfer $openApiResponseTransfer, OutputInterface $output): void
    {
        if (!$output->isVerbose()) {
            return;
        }

        foreach ($openApiResponseTransfer->getMessages() as $message) {
            $output->writeln($message->getMessageOrFail());
        }
    }

    /**
     * @param \Generated\Shared\Transfer\OpenApiResponseTransfer $openApiResponseTransfer
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    protected function printErrors(OpenApiResponseTransfer $openApiResponseTransfer, OutputInterface $output): void
    {
        if (!$output->isVerbose()) {
            return;
        }

        foreach ($openApiResponseTransfer->getErrors() as $error) {
            $output->writeln($error->getMessageOrFail());
        }
    }
}