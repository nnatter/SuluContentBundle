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

namespace Sulu\Bundle\ContentBundle\Content\Application\ContentMerger;

use Sulu\Bundle\ContentBundle\Content\Application\ContentMerger\Merger\MergerInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentCollectionInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;

class ContentMerger implements ContentMergerInterface
{
    /**
     * @var iterable<MergerInterface>
     */
    private $mergers;

    /**
     * @param iterable<MergerInterface> $mergers
     */
    public function __construct(iterable $mergers)
    {
        $this->mergers = $mergers;
    }

    public function merge(DimensionContentCollectionInterface $dimensionContentCollection): DimensionContentInterface
    {
        if (!$dimensionContentCollection->count()) {
            throw new \RuntimeException('Expected at least one dimensionContent given.');
        }

        /** @var DimensionContentInterface[] $dimensionContentCollectionArray */
        $dimensionContentCollectionArray = iterator_to_array($dimensionContentCollection);
        $lastKey = \count($dimensionContentCollectionArray) - 1;

        $mostSpecificDimensionContent = $dimensionContentCollectionArray[$lastKey];
        $mostSpecificDimension = $mostSpecificDimensionContent->getDimension();
        $contentRichEntity = $mostSpecificDimensionContent->getContentRichEntity();

        $mergedDimensionContent = $contentRichEntity->createDimensionContent($mostSpecificDimension);
        $mergedDimensionContent->markAsMerged();

        foreach ($dimensionContentCollection as $dimensionContent) {
            foreach ($this->mergers as $merger) {
                $merger->merge($mergedDimensionContent, $dimensionContent);
            }
        }

        return $mergedDimensionContent;
    }
}
