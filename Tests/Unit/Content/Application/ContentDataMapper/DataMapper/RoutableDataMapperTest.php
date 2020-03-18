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

namespace Sulu\Bundle\ContentBundle\Tests\Unit\Content\Application\ContentDataMapper\DataMapper;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContentBundle\Content\Application\ContentDataMapper\DataMapper\RoutableDataMapper;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\DimensionContentInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\RoutableInterface;
use Sulu\Bundle\ContentBundle\Content\Domain\Model\TemplateInterface;
use Sulu\Bundle\ContentBundle\Tests\Unit\Mocks\MockWrapper;
use Sulu\Bundle\ContentBundle\Tests\Unit\Mocks\RoutableMockWrapperTrait;
use Sulu\Bundle\ContentBundle\Tests\Unit\Mocks\TemplateMockWrapperTrait;
use Sulu\Bundle\RouteBundle\Generator\RouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Manager\RouteManagerInterface;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;

class RoutableDataMapperTest extends TestCase
{
    /**
     * @param array<string, string> $structureDefaultTypes
     */
    protected function createRouteDataMapperInstance(
        StructureMetadataFactoryInterface $factory,
        RouteGeneratorInterface $routeGenerator,
        RouteManagerInterface $routeManager,
        array $structureDefaultTypes = []
    ): RoutableDataMapper {
        return new RoutableDataMapper($factory, $routeGenerator, $routeManager, $structureDefaultTypes);
    }

    protected function wrapRoutableMock(ObjectProphecy $contentProjectionMock): RoutableInterface
    {
        return new class($contentProjectionMock) extends MockWrapper implements
            RoutableInterface,
            TemplateInterface {
            use RoutableMockWrapperTrait;
            use TemplateMockWrapperTrait;
        };
    }

    public function testMapNoRoutable(): void
    {
        $data = [];

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);

        $factory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $routeManager = $this->prophesize(RouteManagerInterface::class);

        $factory->getStructureMetadata(Argument::cetera())->shouldNotBeCalled();
        $routeManager->createOrUpdateByAttributes(Argument::cetera())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance(
            $factory->reveal(),
            $routeGenerator->reveal(),
            $routeManager->reveal()
        );

        $mapper->map($data, $dimensionContent->reveal(), $localizedDimensionContent->reveal());
    }

    public function testMapNoLocalizedDimension(): void
    {
        $data = [];

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);

        $factory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $routeManager = $this->prophesize(RouteManagerInterface::class);

        $factory->getStructureMetadata(Argument::cetera())->shouldNotBeCalled();
        $routeManager->createOrUpdateByAttributes(Argument::cetera())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance(
            $factory->reveal(),
            $routeGenerator->reveal(),
            $routeManager->reveal()
        );

        $mapper->map($data, $dimensionContent->reveal(), null);
    }

    public function testMapNoTemplateInterface(): void
    {
        $this->expectException(\RuntimeException::class);

        $data = [];

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent->willImplement(RoutableInterface::class);

        $factory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $routeManager = $this->prophesize(RouteManagerInterface::class);

        $factory->getStructureMetadata(Argument::cetera())->shouldNotBeCalled();
        $routeManager->createOrUpdateByAttributes(Argument::cetera())->shouldNotBeCalled();
        $localizedDimensionContent->getContentId()->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance(
            $factory->reveal(),
            $routeGenerator->reveal(),
            $routeManager->reveal()
        );

        $mapper->map($data, $dimensionContent->reveal(), $localizedDimensionContent->reveal());
    }

    public function testMapNoTemplate(): void
    {
        $this->expectException(\RuntimeException::class);

        $data = [];

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent->willImplement(RoutableInterface::class);
        $localizedDimensionContent->willImplement(TemplateInterface::class);

        $factory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $routeManager = $this->prophesize(RouteManagerInterface::class);

        $factory->getStructureMetadata(Argument::cetera())->shouldNotBeCalled();
        $routeManager->createOrUpdateByAttributes(Argument::cetera())->shouldNotBeCalled();
        $localizedDimensionContent->getContentId()->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance(
            $factory->reveal(),
            $routeGenerator->reveal(),
            $routeManager->reveal()
        );

        $mapper->map(
            $data,
            $dimensionContent->reveal(),
            $this->wrapRoutableMock($localizedDimensionContent)
        );
    }

    public function testMapNoMetadata(): void
    {
        $data = [
            'template' => 'default',
        ];

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent->willImplement(RoutableInterface::class);
        $localizedDimensionContent->willImplement(TemplateInterface::class);

        $factory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $routeManager = $this->prophesize(RouteManagerInterface::class);

        $routeManager->createOrUpdateByAttributes(Argument::cetera())->shouldNotBeCalled();

        $factory->getStructureMetadata('mock-template-type', 'default')->willReturn(null)->shouldBeCalled();

        $mapper = $this->createRouteDataMapperInstance(
            $factory->reveal(),
            $routeGenerator->reveal(),
            $routeManager->reveal()
        );

        $mapper->map(
            $data,
            $dimensionContent->reveal(),
            $this->wrapRoutableMock($localizedDimensionContent)
        );
    }

    public function testMapNoRouteProperty(): void
    {
        $data = [
            'template' => 'default',
        ];

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent->willImplement(RoutableInterface::class);
        $localizedDimensionContent->willImplement(TemplateInterface::class);

        $factory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $routeManager = $this->prophesize(RouteManagerInterface::class);

        $routeManager->createOrUpdateByAttributes(Argument::cetera())->shouldNotBeCalled();

        $metadata = $this->prophesize(StructureMetadata::class);
        $property = $this->prophesize(PropertyMetadata::class);
        $property->getType()->willReturn('text_line');
        $property->getName()->willReturn('url');

        $metadata->getProperties()->WillReturn([$property->reveal()]);

        $localizedDimensionContent->getContentId()->willReturn('123-123-123');
        $localizedDimensionContent->getLocale()->willReturn('de');
        $factory->getStructureMetadata('mock-template-type', 'default')->willReturn($metadata->reveal())->shouldBeCalled();

        $mapper = $this->createRouteDataMapperInstance(
            $factory->reveal(),
            $routeGenerator->reveal(),
            $routeManager->reveal()
        );

        $mapper->map(
            $data,
            $dimensionContent->reveal(),
            $this->wrapRoutableMock($localizedDimensionContent)
        );
    }

    public function testMapNoRoutePropertyData(): void
    {
        $data = [
            'template' => 'default',
        ];

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent->willImplement(RoutableInterface::class);
        $localizedDimensionContent->willImplement(TemplateInterface::class);

        $factory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $routeManager = $this->prophesize(RouteManagerInterface::class);

        $routeManager->createOrUpdateByAttributes(Argument::cetera())->shouldNotBeCalled();

        $metadata = $this->prophesize(StructureMetadata::class);
        $property = $this->prophesize(PropertyMetadata::class);
        $property->getType()->willReturn('route');
        $property->getName()->willReturn('url');

        $metadata->getProperties()->WillReturn([$property->reveal()]);

        $localizedDimensionContent->getContentId()->willReturn('123-123-123');
        $localizedDimensionContent->getTemplateData()->willReturn(['url' => '/test']);
        $localizedDimensionContent->getLocale()->willReturn('de');
        $factory->getStructureMetadata('mock-template-type', 'default')->willReturn($metadata->reveal())->shouldBeCalled();

        $mapper = $this->createRouteDataMapperInstance(
            $factory->reveal(),
            $routeGenerator->reveal(),
            $routeManager->reveal()
        );

        $mapper->map(
            $data,
            $dimensionContent->reveal(),
            $this->wrapRoutableMock($localizedDimensionContent)
        );
    }

    public function testMapNoRoutePropertyDataAndNoOldRoute(): void
    {
        $data = [
            'template' => 'default',
        ];

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent->willImplement(RoutableInterface::class);
        $localizedDimensionContent->willImplement(TemplateInterface::class);

        $factory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $routeManager = $this->prophesize(RouteManagerInterface::class);

        $metadata = $this->prophesize(StructureMetadata::class);
        $property = $this->prophesize(PropertyMetadata::class);
        $property->getType()->willReturn('route');
        $property->getName()->willReturn('url');

        $metadata->getProperties()->WillReturn([$property->reveal()]);

        $localizedDimensionContent->getContentId()->willReturn('123-123-123');
        $localizedDimensionContent->getTemplateData()->willReturn([]);
        $localizedDimensionContent->getLocale()->willReturn('en');
        $localizedDimensionContent->setTemplateData(['url' => '/test'])->shouldBeCalled();
        $localizedDimensionContentMock = $this->wrapRoutableMock($localizedDimensionContent);

        $factory->getStructureMetadata('mock-template-type', 'default')->willReturn($metadata->reveal())->shouldBeCalled();

        $routeGenerator->generate($localizedDimensionContentMock, ['route_schema' => '/{object.getTitle()}'])
            ->willReturn('/test');

        $routeManager->createOrUpdateByAttributes(
            'mock-content-class',
            '123-123-123',
            'en',
            '/test'
        )->shouldBeCalled();

        $mapper = $this->createRouteDataMapperInstance(
            $factory->reveal(),
            $routeGenerator->reveal(),
            $routeManager->reveal()
        );

        $mapper->map(
            $data,
            $dimensionContent->reveal(),
            $localizedDimensionContentMock
        );
    }

    public function testMapNoRoutePropertyDataAndNoOldRouteIgnoreSlash(): void
    {
        $data = [
            'template' => 'default',
        ];

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent->willImplement(RoutableInterface::class);
        $localizedDimensionContent->willImplement(TemplateInterface::class);

        $factory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $routeManager = $this->prophesize(RouteManagerInterface::class);

        $metadata = $this->prophesize(StructureMetadata::class);
        $property = $this->prophesize(PropertyMetadata::class);
        $property->getType()->willReturn('route');
        $property->getName()->willReturn('url');

        $metadata->getProperties()->WillReturn([$property->reveal()]);

        $localizedDimensionContent->getContentId()->willReturn('123-123-123');
        $localizedDimensionContent->getTemplateData()->willReturn([]);
        $localizedDimensionContent->getLocale()->willReturn('en');
        $localizedDimensionContent->setTemplateData(Argument::cetera())->shouldNotBeCalled();
        $localizedDimensionContentMock = $this->wrapRoutableMock($localizedDimensionContent);

        $factory->getStructureMetadata('mock-template-type', 'default')->willReturn($metadata->reveal())->shouldBeCalled();

        $routeGenerator->generate($localizedDimensionContentMock, ['route_schema' => '/{object.getTitle()}'])
            ->willReturn('/');

        $routeManager->createOrUpdateByAttributes(Argument::cetera())->shouldNotBeCalled();

        $mapper = $this->createRouteDataMapperInstance(
            $factory->reveal(),
            $routeGenerator->reveal(),
            $routeManager->reveal()
        );

        $mapper->map(
            $data,
            $dimensionContent->reveal(),
            $localizedDimensionContentMock
        );
    }

    public function testMapNoContentId(): void
    {
        $data = [
            'template' => 'default',
        ];

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent->willImplement(RoutableInterface::class);
        $localizedDimensionContent->willImplement(TemplateInterface::class);

        $factory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $routeManager = $this->prophesize(RouteManagerInterface::class);

        $routeManager->createOrUpdateByAttributes(Argument::cetera())->shouldNotBeCalled();

        $metadata = $this->prophesize(StructureMetadata::class);
        $property = $this->prophesize(PropertyMetadata::class);
        $property->getType()->willReturn('route');

        $metadata->getProperties()->WillReturn([$property->reveal()]);

        $factory->getStructureMetadata('mock-template-type', 'default')->willReturn($metadata->reveal())->shouldBeCalled();

        $localizedDimensionContent->getContentId()->willReturn(null);

        $mapper = $this->createRouteDataMapperInstance(
            $factory->reveal(),
            $routeGenerator->reveal(),
            $routeManager->reveal()
        );

        $mapper->map(
            $data,
            $dimensionContent->reveal(),
            $this->wrapRoutableMock($localizedDimensionContent)
        );
    }

    public function testMapNoLocale(): void
    {
        $data = [
            'template' => 'default',
        ];

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent->willImplement(RoutableInterface::class);
        $localizedDimensionContent->willImplement(TemplateInterface::class);

        $factory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $routeManager = $this->prophesize(RouteManagerInterface::class);

        $routeManager->createOrUpdateByAttributes(Argument::cetera())->shouldNotBeCalled();

        $metadata = $this->prophesize(StructureMetadata::class);
        $property = $this->prophesize(PropertyMetadata::class);
        $property->getType()->willReturn('route');

        $metadata->getProperties()->WillReturn([$property->reveal()]);

        $factory->getStructureMetadata('mock-template-type', 'default')->willReturn($metadata->reveal())->shouldBeCalled();

        $localizedDimensionContent->getContentId()->willReturn('123-123-123');
        $localizedDimensionContent->getLocale()->willReturn(null);

        $mapper = $this->createRouteDataMapperInstance(
            $factory->reveal(),
            $routeGenerator->reveal(),
            $routeManager->reveal()
        );

        $mapper->map(
            $data,
            $dimensionContent->reveal(),
            $this->wrapRoutableMock($localizedDimensionContent)
        );
    }

    public function testMapNoRoutePath(): void
    {
        $data = [
            'template' => 'default',
            'url' => null,
        ];

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent->willImplement(RoutableInterface::class);
        $localizedDimensionContent->willImplement(TemplateInterface::class);

        $factory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $routeManager = $this->prophesize(RouteManagerInterface::class);

        $metadata = $this->prophesize(StructureMetadata::class);
        $property = $this->prophesize(PropertyMetadata::class);
        $property->getType()->willReturn('route');
        $property->getName()->willReturn('url');

        $metadata->getProperties()->WillReturn([$property->reveal()]);

        $factory->getStructureMetadata('mock-template-type', 'default')->willReturn($metadata->reveal())->shouldBeCalled();

        $localizedDimensionContent->getContentId()->willReturn('123-123-123');
        $localizedDimensionContent->getTemplateData()->willReturn(['title' => 'Test', 'url' => null]);
        $localizedDimensionContent->setTemplateData(['title' => 'Test', 'url' => '/test'])->shouldBeCalled();
        $localizedDimensionContent->getLocale()->willReturn('en');

        $localizedDimensionContentMock = $this->wrapRoutableMock($localizedDimensionContent);

        $routeGenerator->generate($localizedDimensionContentMock, ['route_schema' => '/{object.getTitle()}'])
            ->willReturn('/test');

        $routeManager->createOrUpdateByAttributes(
            'mock-content-class',
            '123-123-123',
            'en',
            '/test'
        )->shouldBeCalled();

        $mapper = $this->createRouteDataMapperInstance(
            $factory->reveal(),
            $routeGenerator->reveal(),
            $routeManager->reveal()
        );

        $mapper->map(
            $data,
            $dimensionContent->reveal(),
            $localizedDimensionContentMock
        );
    }

    public function testMap(): void
    {
        $data = [
            'template' => 'default',
            'url' => '/test',
        ];

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent->willImplement(RoutableInterface::class);
        $localizedDimensionContent->willImplement(TemplateInterface::class);

        $factory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $routeManager = $this->prophesize(RouteManagerInterface::class);

        $metadata = $this->prophesize(StructureMetadata::class);
        $property = $this->prophesize(PropertyMetadata::class);
        $property->getType()->willReturn('route');
        $property->getName()->willReturn('url');

        $metadata->getProperties()->WillReturn([$property->reveal()]);

        $localizedDimensionContent->getTemplateData()->willReturn([]);
        $factory->getStructureMetadata('mock-template-type', 'default')->willReturn($metadata->reveal())->shouldBeCalled();

        $localizedDimensionContent->getContentId()->willReturn('123-123-123');
        $localizedDimensionContent->getLocale()->willReturn('en');
        $localizedDimensionContentMock = $this->wrapRoutableMock($localizedDimensionContent);

        $routeGenerator->generate($localizedDimensionContentMock, ['schema' => '/{object.getTitle()}'])
            ->shouldNotBeCalled();

        $routeManager->createOrUpdateByAttributes(
            'mock-content-class',
            '123-123-123',
            'en',
            '/test'
        )->shouldBeCalled();

        $mapper = $this->createRouteDataMapperInstance(
            $factory->reveal(),
            $routeGenerator->reveal(),
            $routeManager->reveal()
        );

        $mapper->map(
            $data,
            $dimensionContent->reveal(),
            $localizedDimensionContentMock
        );
    }

    public function testMapNoTemplateWithDefaultTemplate(): void
    {
        $data = [
            'url' => '/test',
        ];

        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $localizedDimensionContent->willImplement(RoutableInterface::class);
        $localizedDimensionContent->willImplement(TemplateInterface::class);

        $factory = $this->prophesize(StructureMetadataFactoryInterface::class);
        $routeGenerator = $this->prophesize(RouteGeneratorInterface::class);
        $routeManager = $this->prophesize(RouteManagerInterface::class);

        $metadata = $this->prophesize(StructureMetadata::class);
        $property = $this->prophesize(PropertyMetadata::class);
        $property->getType()->willReturn('route');
        $property->getName()->willReturn('url');

        $metadata->getProperties()->WillReturn([$property->reveal()]);

        $localizedDimensionContent->getTemplateData()->willReturn([]);
        $factory->getStructureMetadata('mock-template-type', 'default')->willReturn($metadata->reveal())->shouldBeCalled();

        $localizedDimensionContent->getContentId()->willReturn('123-123-123');
        $localizedDimensionContent->getLocale()->willReturn('en');
        $localizedDimensionContentMock = $this->wrapRoutableMock($localizedDimensionContent);

        $routeGenerator->generate($localizedDimensionContentMock, ['schema' => '/{object.getTitle()}'])
            ->shouldNotBeCalled();

        $routeManager->createOrUpdateByAttributes(
            'mock-content-class',
            '123-123-123',
            'en',
            '/test'
        )->shouldBeCalled();

        $mapper = $this->createRouteDataMapperInstance(
            $factory->reveal(),
            $routeGenerator->reveal(),
            $routeManager->reveal(),
            ['mock-template-type' => 'default']
        );

        $mapper->map(
            $data,
            $dimensionContent->reveal(),
            $localizedDimensionContentMock
        );
    }
}
