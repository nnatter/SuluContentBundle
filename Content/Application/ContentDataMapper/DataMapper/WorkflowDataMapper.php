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

namespace Sulu\Bundle\ContentBundle\Content\Application\ContentDataMapper\DataMapper;

use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\WorkflowInterface;

class WorkflowDataMapper implements DataMapperInterface
{
    public function map(
        array $data,
        object $unlocalizedObject,
        ?object $localizedObject = null
    ): void {
        if (!$unlocalizedObject instanceof WorkflowInterface) {
            return;
        }

        if ($localizedObject) {
            if (!$localizedObject instanceof WorkflowInterface) {
                throw new \RuntimeException(sprintf('Expected "$localizedObject" from type "%s" but "%s" given.', WorkflowInterface::class, \get_class($localizedObject)));
            }

            $this->setWorkflowData($localizedObject, $data);

            return;
        }

        $this->setWorkflowData($unlocalizedObject, $data);
    }

    /**
     * @param mixed[] $data
     */
    private function setWorkflowData(WorkflowInterface $object, array $data): void
    {
        $this->setInitialPlaceToDraftDimension($object, $data);
        $this->setPublishedToLiveDimension($object, $data);
    }

    /**
     * @param mixed[] $data
     */
    private function setInitialPlaceToDraftDimension(WorkflowInterface $object, array $data): void
    {
        // we want to set the initial place only to the draft dimension, the live dimension should not have a place
        // after the place was set by this mapper initially, the place should only be changed by the ContentWorkflow
        // see: https://github.com/sulu/SuluContentBundle/issues/92

        if (!$object instanceof DimensionContentInterface
            || DimensionInterface::STAGE_DRAFT !== $object->getDimension()->getStage()) {
            return;
        }

        if (!$object->getWorkflowPlace()) {
            // TODO: get public workflow registry and set initial place based on $object::getWorkflowName()
            $object->setWorkflowPlace(WorkflowInterface::WORKFLOW_PLACE_UNPUBLISHED);
        }
    }

    /**
     * @param mixed[] $data
     */
    private function setPublishedToLiveDimension(WorkflowInterface $object, array $data): void
    {
        // the published property of the draft dimension should only be changed by a ContentWorkflow subscriber
        // therefore we only want to copy the published property from the draft to the live dimension

        if (!$object instanceof DimensionContentInterface
            || DimensionInterface::STAGE_LIVE !== $object->getDimension()->getStage()) {
            return;
        }

        $published = $data['published'] ?? null;

        if (!$published) {
            return;
        }

        $object->setWorkflowPublished(new \DateTimeImmutable($published));
    }
}
