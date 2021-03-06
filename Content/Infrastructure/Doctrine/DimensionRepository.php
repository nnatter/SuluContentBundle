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

namespace Sulu\Bundle\ContentBundle\Content\Infrastructure\Doctrine;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionCollection;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionCollectionInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Repository\DimensionRepositoryInterface;

class DimensionRepository implements DimensionRepositoryInterface
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var EntityRepository<DimensionInterface>
     */
    protected $entityRepository;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        $this->entityRepository = new EntityRepository($em, $class);
        $this->entityManager = $em;
        $this->className = $this->entityRepository->getClassName();
    }

    public function create(
        ?string $id = null,
        array $attributes = []
    ): DimensionInterface {
        /** @var DimensionInterface $dimension */
        $dimension = new $this->className($id, $attributes);

        return $dimension;
    }

    public function remove(DimensionInterface $dimension): void
    {
        $this->entityManager->remove($dimension);
    }

    public function add(DimensionInterface $dimension): void
    {
        $this->entityManager->persist($dimension);
    }

    public function findByAttributes(array $attributes): DimensionCollectionInterface
    {
        $attributes = $this->getNormalizedAttributes($attributes);

        $queryBuilder = $this->entityRepository->createQueryBuilder('dimension');
        $queryBuilder->addCriteria($this->getAttributesCriteria('dimension', $attributes));

        $this->addSortBy($queryBuilder, $attributes);

        return new DimensionCollection($attributes, $queryBuilder->getQuery()->getResult());
    }

    public function findOneBy(array $criteria): ?DimensionInterface
    {
        /** @var DimensionInterface|null $directory */
        $directory = $this->entityRepository->findOneBy($criteria);

        return $directory;
    }

    public function findBy(array $criteria): iterable
    {
        /** @var DimensionInterface[] $dimensions */
        $dimensions = $this->entityRepository->findBy($criteria);

        return $dimensions;
    }

    /**
     * Less specific should be returned first to merge correctly.
     *
     * @param mixed[] $attributes
     */
    private function addSortBy(QueryBuilder $queryBuilder, array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $queryBuilder->addOrderBy('dimension.' . $key);
        }
    }

    /**
     * @param mixed[] $attributes
     */
    private function getAttributesCriteria(string $dimensionAlias, array $attributes): Criteria
    {
        $criteria = Criteria::create();

        foreach ($attributes as $key => $value) {
            $fieldName = $dimensionAlias . '.' . $key;
            $expr = $criteria->expr()->isNull($fieldName);

            if (null !== $value) {
                $eqExpr = $criteria->expr()->eq($fieldName, $value);
                $expr = $criteria->expr()->orX($expr, $eqExpr);
            }

            $criteria->andWhere($expr);
        }

        return $criteria;
    }

    /**
     * @param mixed[] $attributes
     *
     * @return mixed[]
     */
    private function getNormalizedAttributes(array $attributes): array
    {
        $defaultValues = $this->className::getDefaultValues();

        // Ignore any key which is given
        $attributes = array_intersect_key($attributes, $defaultValues);

        $attributes = array_merge(
            $defaultValues,
            $attributes
        );

        unset($attributes['id']);
        unset($attributes['no']);

        return $attributes;
    }
}
