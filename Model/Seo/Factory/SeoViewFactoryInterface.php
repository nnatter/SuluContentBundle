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

namespace Sulu\Bundle\ContentBundle\Model\Seo\Factory;

use Sulu\Bundle\ContentBundle\Model\Seo\SeoInterface;
use Sulu\Bundle\ContentBundle\Model\Seo\SeoViewInterface;

interface SeoViewFactoryInterface
{
    /**
     * @param SeoInterface[] $seoDimensions
     * @return SeoViewInterface|null
     */
    public function create(array $seoDimensions, string $locale): ?SeoViewInterface;
}
