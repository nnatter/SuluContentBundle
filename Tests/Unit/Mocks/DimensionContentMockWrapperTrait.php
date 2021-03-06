<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Mocks;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionInterface;

/**
 * Trait for composing a class that wraps a DimensionContentInterface mock.
 *
 * @see MockWrapper to learn why this trait is needed.
 */
trait DimensionContentMockWrapperTrait
{
    public function getDimension(): DimensionInterface
    {
        return $this->instance->getDimension();
    }

    public function getContentRichEntity(): ContentRichEntityInterface
    {
        return $this->instance->getContentRichEntity();
    }

    public function isMerged(): bool
    {
        return $this->instance->isMerged();
    }

    public function markAsMerged(): void
    {
        $this->instance->markAsMerged();
    }
}
