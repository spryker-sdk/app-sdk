<?php

/**
 * Copyright © 2019-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerSdk\Zed\AppSdk\Business\Translation\Builder;

use Generated\Shared\Transfer\AppTranslationRequestTransfer;
use Generated\Shared\Transfer\AppTranslationResponseTransfer;

interface AppTranslationBuilderInterface
{
    /**
     * @param \Generated\Shared\Transfer\AppTranslationRequestTransfer $appTranslationRequestTransfer
     *
     * @return \Generated\Shared\Transfer\AppTranslationResponseTransfer
     */
    public function appTranslationCreate(AppTranslationRequestTransfer $appTranslationRequestTransfer): AppTranslationResponseTransfer;
}
