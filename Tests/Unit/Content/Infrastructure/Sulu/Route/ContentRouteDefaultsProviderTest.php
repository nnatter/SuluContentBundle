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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Infrastructure\Sulu\Route;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\ContentBundle\Content\Application\ContentResolver\ContentResolverInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentProjectionInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\Dimension;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Repository\DimensionRepositoryInterface;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Route\ContentRouteDefaultsProvider;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Route\ContentStructureBridge;
use Sulu\Bundle\ContentBundle\Content\Infrastructure\Sulu\Route\ContentStructureBridgeFactory;
use Sulu\Bundle\ContentBundle\Tests\Application\ExampleTestBundle\Entity\Example;

class ContentRouteDefaultsProviderTest extends TestCase
{
    protected function getContentRouteDefaultsProvider(
        EntityManagerInterface $entityManager,
        ContentResolverInterface $contentResolver,
        ContentStructureBridgeFactory $contentStructureBridgeFactory
    ): ContentRouteDefaultsProvider {
        return new ContentRouteDefaultsProvider(
            $entityManager, $contentResolver, $contentStructureBridgeFactory
        );
    }

    public function testSupports(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentResolver = $this->prophesize(ContentResolverInterface::class);
        $contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);

        $contentRouteDefaultsProvider = $this->getContentRouteDefaultsProvider(
            $entityManager->reveal(),
            $contentResolver->reveal(),
            $contentStructureBridgeFactory->reveal()
        );

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);

        $this->assertTrue($contentRouteDefaultsProvider->supports(\get_class($contentRichEntity->reveal())));
        $this->assertFalse($contentRouteDefaultsProvider->supports(\stdClass::class));
    }

    public function testIsPublished(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentResolver = $this->prophesize(ContentResolverInterface::class);
        $contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);

        $contentRouteDefaultsProvider = $this->getContentRouteDefaultsProvider(
            $entityManager->reveal(),
            $contentResolver->reveal(),
            $contentStructureBridgeFactory->reveal()
        );

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $contentProjection = $this->prophesize(TemplateInterface::class);
        $contentProjection->willImplement(ContentProjectionInterface::class);
        $contentProjection->getDimensionId()->willReturn('123-456');

        $dimensionRepository = $this->prophesize(DimensionRepositoryInterface::class);
        $dimensionRepository->findOneBy(['id' => '123-456'])->willReturn(new Dimension('123-456', [
            'locale' => 'en',
            'stage' => 'live',
        ]));
        $entityManager->getRepository(DimensionInterface::class)->willReturn($dimensionRepository->reveal());

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);

        $entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity.id = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity->reveal());

        $contentResolver->resolve(
            $contentRichEntity->reveal(),
            ['locale' => 'en', 'stage' => 'live']
        )->willReturn($contentProjection->reveal());

        $this->assertTrue($contentRouteDefaultsProvider->isPublished(Example::class, '123-123-123', 'en'));
    }

    public function testIsPublishedEntityNotFound(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentResolver = $this->prophesize(ContentResolverInterface::class);
        $contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);

        $contentRouteDefaultsProvider = $this->getContentRouteDefaultsProvider(
            $entityManager->reveal(),
            $contentResolver->reveal(),
            $contentStructureBridgeFactory->reveal()
        );

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);

        $entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity.id = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willThrow(new NoResultException());

        $contentResolver->resolve(Argument::cetera())->shouldNotBeCalled();

        $this->assertFalse($contentRouteDefaultsProvider->isPublished(Example::class, '123-123-123', 'en'));
    }

    public function testIsPublishedContentNotFound(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentResolver = $this->prophesize(ContentResolverInterface::class);
        $contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);

        $contentRouteDefaultsProvider = $this->getContentRouteDefaultsProvider(
            $entityManager->reveal(),
            $contentResolver->reveal(),
            $contentStructureBridgeFactory->reveal()
        );

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);

        $entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity.id = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity->reveal());

        $contentResolver->resolve(
            $contentRichEntity->reveal(),
            ['locale' => 'en', 'stage' => 'live']
        )->willThrow(new ContentNotFoundException($contentRichEntity->reveal(), ['locale' => 'en', 'stage' => 'live']));

        $this->assertFalse($contentRouteDefaultsProvider->isPublished(Example::class, '123-123-123', 'en'));
    }

    public function testIsPublishedWithLocalizedDimension(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentResolver = $this->prophesize(ContentResolverInterface::class);
        $contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);

        $contentRouteDefaultsProvider = $this->getContentRouteDefaultsProvider(
            $entityManager->reveal(),
            $contentResolver->reveal(),
            $contentStructureBridgeFactory->reveal()
        );
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $contentProjection = $this->prophesize(TemplateInterface::class);
        $contentProjection->willImplement(ContentProjectionInterface::class);
        $contentProjection->getDimensionId()->willReturn('123-456');

        $dimensionRepository = $this->prophesize(DimensionRepositoryInterface::class);
        $dimensionRepository->findOneBy(['id' => '123-456'])->willReturn(new Dimension('123-456', [
            'locale' => 'en',
            'stage' => 'live',
        ]));

        $entityManager->getRepository(DimensionInterface::class)->willReturn($dimensionRepository->reveal());
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);
        $entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity.id = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity->reveal());

        $contentResolver->resolve($contentRichEntity->reveal(), ['locale' => 'en', 'stage' => 'live'])
            ->willReturn($contentProjection->reveal())
            ->shouldBeCalled();

        $this->assertTrue($contentRouteDefaultsProvider->isPublished(Example::class, '123-123-123', 'en'));
    }

    public function testIsPublishedWithUnlocalizedDimension(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentResolver = $this->prophesize(ContentResolverInterface::class);
        $contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);

        $contentRouteDefaultsProvider = $this->getContentRouteDefaultsProvider(
            $entityManager->reveal(),
            $contentResolver->reveal(),
            $contentStructureBridgeFactory->reveal()
        );
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $contentProjection = $this->prophesize(TemplateInterface::class);
        $contentProjection->willImplement(ContentProjectionInterface::class);
        $contentProjection->getDimensionId()->willReturn('123-456');

        $dimensionRepository = $this->prophesize(DimensionRepositoryInterface::class);
        $dimensionRepository->findOneBy(['id' => '123-456'])->willReturn(new Dimension('123-456', [
            'stage' => 'live',
        ]));

        $entityManager->getRepository(DimensionInterface::class)->willReturn($dimensionRepository->reveal());
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);
        $entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity.id = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity->reveal());

        $contentResolver->resolve($contentRichEntity->reveal(), ['locale' => 'en', 'stage' => 'live'])
            ->willReturn($contentProjection->reveal())
            ->shouldBeCalled();

        $this->assertFalse($contentRouteDefaultsProvider->isPublished(Example::class, '123-123-123', 'en'));
    }

    public function testIsPublishedDimensionNotFound(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentResolver = $this->prophesize(ContentResolverInterface::class);
        $contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);

        $contentRouteDefaultsProvider = $this->getContentRouteDefaultsProvider(
            $entityManager->reveal(),
            $contentResolver->reveal(),
            $contentStructureBridgeFactory->reveal()
        );

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $contentProjection = $this->prophesize(TemplateInterface::class);
        $contentProjection->willImplement(ContentProjectionInterface::class);
        $contentProjection->getDimensionId()->willReturn('123-456');

        $dimensionRepository = $this->prophesize(DimensionRepositoryInterface::class);
        $dimensionRepository->findOneBy(['id' => '123-456'])->willReturn(null);

        $entityManager->getRepository(DimensionInterface::class)->willReturn($dimensionRepository->reveal());
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);
        $entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity.id = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity->reveal());

        $contentResolver->resolve($contentRichEntity->reveal(), ['locale' => 'en', 'stage' => 'live'])
            ->willReturn($contentProjection->reveal())
            ->shouldBeCalled();

        $this->assertFalse($contentRouteDefaultsProvider->isPublished(Example::class, '123-123-123', 'en'));
    }

    public function testGetByEntityReturnNoneTemplate(): void
    {
        $contentProjection = $this->prophesize(ContentProjectionInterface::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Expected to get "%s" from ContentResolver but "%s" given.',
            TemplateInterface::class,
            \get_class($contentProjection->reveal())
        ));

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentResolver = $this->prophesize(ContentResolverInterface::class);
        $contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);

        $contentRouteDefaultsProvider = $this->getContentRouteDefaultsProvider(
            $entityManager->reveal(),
            $contentResolver->reveal(),
            $contentStructureBridgeFactory->reveal()
        );

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);

        $entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity.id = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity->reveal());

        $contentResolver->resolve(
            $contentRichEntity->reveal(),
            ['locale' => 'en', 'stage' => 'live']
        )->willReturn($contentProjection->reveal());

        $contentRouteDefaultsProvider->getByEntity(Example::class, '123-123-123', 'en');
    }

    public function testGetByEntityReturnNoneTemplateFromPreview(): void
    {
        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf(
            'Expected to get "%s" from ContentResolver but "%s" given.',
            TemplateInterface::class,
            \get_class($contentRichEntity->reveal())
        ));

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentResolver = $this->prophesize(ContentResolverInterface::class);
        $contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);

        $contentRouteDefaultsProvider = $this->getContentRouteDefaultsProvider(
            $entityManager->reveal(),
            $contentResolver->reveal(),
            $contentStructureBridgeFactory->reveal()
        );

        /**
         * @var ContentProjectionInterface
         */
        $entity = $contentRichEntity->reveal();

        $contentRouteDefaultsProvider->getByEntity(Example::class, '123-123-123', 'en', $entity);
    }

    public function testGetByEntity(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentResolver = $this->prophesize(ContentResolverInterface::class);
        $contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);

        $contentRouteDefaultsProvider = $this->getContentRouteDefaultsProvider(
            $entityManager->reveal(),
            $contentResolver->reveal(),
            $contentStructureBridgeFactory->reveal()
        );

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);
        $contentProjection = $this->prophesize(TemplateInterface::class);
        $contentProjection->willImplement(ContentProjectionInterface::class);

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);

        $entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity.id = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity->reveal());

        $contentResolver->resolve($contentRichEntity->reveal(), ['locale' => 'en', 'stage' => 'live'])
            ->willReturn($contentProjection->reveal());

        $contentStructureBridge = $this->prophesize(ContentStructureBridge::class);
        $contentStructureBridge->getView()->willReturn('default');
        $contentStructureBridge->getController()->willReturn('App\Controller\TestController:testAction');
        $contentStructureBridgeFactory->getBridge($contentProjection->reveal(), '123-123-123', 'en')
            ->willReturn($contentStructureBridge->reveal());

        $result = $contentRouteDefaultsProvider->getByEntity(Example::class, '123-123-123', 'en');
        $this->assertSame($contentProjection->reveal(), $result['object']);
        $this->assertSame('default', $result['view']);
        $this->assertSame($contentStructureBridge->reveal(), $result['structure']);
        $this->assertSame('App\Controller\TestController:testAction', $result['_controller']);
    }

    public function testGetByEntityNotPublished(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $contentResolver = $this->prophesize(ContentResolverInterface::class);
        $contentStructureBridgeFactory = $this->prophesize(ContentStructureBridgeFactory::class);

        $contentRouteDefaultsProvider = $this->getContentRouteDefaultsProvider(
            $entityManager->reveal(),
            $contentResolver->reveal(),
            $contentStructureBridgeFactory->reveal()
        );

        $contentRichEntity = $this->prophesize(ContentRichEntityInterface::class);

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(AbstractQuery::class);

        $entityManager->createQueryBuilder()->willReturn($queryBuilder->reveal());
        $queryBuilder->select('entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->from(Example::class, 'entity')->willReturn($queryBuilder->reveal());
        $queryBuilder->where('entity.id = :id')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('id', '123-123-123')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query);
        $query->getSingleResult()->willReturn($contentRichEntity->reveal());

        $contentResolver->resolve($contentRichEntity->reveal(), ['locale' => 'en', 'stage' => 'live'])
            ->will(function ($arguments) {
                throw new ContentNotFoundException($arguments[0], $arguments[1]);
            });

        $this->assertEmpty($contentRouteDefaultsProvider->getByEntity(Example::class, '123-123-123', 'en'));
    }
}
