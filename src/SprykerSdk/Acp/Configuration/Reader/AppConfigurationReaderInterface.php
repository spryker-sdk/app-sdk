<?php

/**
 * Copyright © 2019-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerSdk\Acp\Configuration\Reader;

interface AppConfigurationReaderInterface
{
    /**
     * @param string $filePath
     *
     * @return array
     */
    public function readConfigurationFile(string $filePath): array;
}
