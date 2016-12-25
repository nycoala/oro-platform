<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CustomizeLoadedData;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\BuildExtendedAssociations;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;

class BuildExtendedAssociationsTest extends \PHPUnit_Framework_TestCase
{
    /** @var CustomizeLoadedDataContext */
    protected $context;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $associationManager;

    /** @var BuildExtendedAssociations */
    protected $processor;

    protected function setUp()
    {
        $this->context = new CustomizeLoadedDataContext();
        $this->associationManager = $this->getMockBuilder(AssociationManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->processor = new BuildExtendedAssociations($this->associationManager);
    }

    public function testProcessWhenNoData()
    {
        $this->processor->process($this->context);
        $this->assertFalse($this->context->hasResult());
    }

    public function testProcessWithoutConfig()
    {
        $data = [
            'field1' => 123
        ];

        $this->context->setResult($data);
        $this->processor->process($this->context);
    }

    public function testProcessWithoutExtendedAssociations()
    {
        $data = [
            'field1' => 123
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('field1')->setDataType('integer');

        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            [
                'field1' => 123
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForExcludedExtendedAssociation()
    {
        $data = [
            'association1' => ['id' => 1]
        ];
        $config = new EntityDefinitionConfig();
        $association = $config->addField('association');
        $association->setDataType('association:manyToOne:kind');
        $association->setExcluded();

        $this->associationManager->expects(self::never())
            ->method('getAssociationTargets');

        $this->context->setClassName('Test\Class');
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            [
                'association1' => ['id' => 1]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessWhenExtendedAssociationValueIsAlreadySet()
    {
        $data = [
            'association'  => ['__class' => 'Test\Target1', 'id' => 1],
            'association1' => ['id' => 1]
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('association')->setDataType('association:manyToOne:kind');

        $this->associationManager->expects(self::never())
            ->method('getAssociationTargets');

        $this->context->setClassName('Test\Class');
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            [
                'association'  => ['__class' => 'Test\Target1', 'id' => 1],
                'association1' => ['id' => 1]
            ],
            $this->context->getResult()
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Unsupported type of extended association: unknown.
     */
    public function testProcessForUnsupportedExtendedAssociation()
    {
        $data = [
            'association1' => null,
            'association2' => ['id' => 2]
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('association')->setDataType('association:unknown:kind');

        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with('Test\Class', null, 'unknown', 'kind')
            ->willReturn([]);

        $this->context->setClassName('Test\Class');
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
    }

    public function testProcessForManyToOneExtendedAssociation()
    {
        $data = [
            'association1' => null,
            'association2' => ['id' => 2]
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('association')->setDataType('association:manyToOne:kind');

        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with('Test\Class', null, 'manyToOne', 'kind')
            ->willReturn(
                ['Test\Target1' => 'association1', 'Test\Target2' => 'association2']
            );

        $this->context->setClassName('Test\Class');
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            [
                'association1' => null,
                'association2' => ['id' => 2],
                'association'  => [
                    '__class__' => 'Test\Target2',
                    'id'        => 2
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForManyToOneExtendedAssociationWhenAllDependedAssociationsAreNull()
    {
        $data = [
            'association1' => null,
            'association2' => null
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('association')->setDataType('association:manyToOne:kind');

        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with('Test\Class', null, 'manyToOne', 'kind')
            ->willReturn(
                ['Test\Target1' => 'association1', 'Test\Target2' => 'association2']
            );

        $this->context->setClassName('Test\Class');
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            [
                'association1' => null,
                'association2' => null,
                'association'  => null
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForManyToManyExtendedAssociation()
    {
        $data = [
            'association1' => [],
            'association2' => [['id' => 2]],
            'association3' => [['id' => 3]]
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('association')->setDataType('association:manyToMany:kind');

        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with('Test\Class', null, 'manyToMany', 'kind')
            ->willReturn(
                [
                    'Test\Target1' => 'association1',
                    'Test\Target2' => 'association2',
                    'Test\Target3' => 'association3'
                ]
            );

        $this->context->setClassName('Test\Class');
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            [
                'association1' => [],
                'association2' => [['id' => 2]],
                'association3' => [['id' => 3]],
                'association'  => [
                    ['__class__' => 'Test\Target2', 'id' => 2],
                    ['__class__' => 'Test\Target3', 'id' => 3],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForManyToManyExtendedAssociationWhenAllDependedAssociationsAreEmpty()
    {
        $data = [
            'association1' => [],
            'association2' => []
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('association')->setDataType('association:manyToMany:kind');

        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with('Test\Class', null, 'manyToMany', 'kind')
            ->willReturn(
                [
                    'Test\Target1' => 'association1',
                    'Test\Target2' => 'association2'
                ]
            );

        $this->context->setClassName('Test\Class');
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            [
                'association1' => [],
                'association2' => [],
                'association'  => []
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForMultipleManyToOneExtendedAssociation()
    {
        $data = [
            'association1' => null,
            'association2' => ['id' => 2],
            'association3' => ['id' => 3]
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('association')->setDataType('association:multipleManyToOne:kind');

        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with('Test\Class', null, 'multipleManyToOne', 'kind')
            ->willReturn(
                [
                    'Test\Target1' => 'association1',
                    'Test\Target2' => 'association2',
                    'Test\Target3' => 'association3'
                ]
            );

        $this->context->setClassName('Test\Class');
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            [
                'association1' => null,
                'association2' => ['id' => 2],
                'association3' => ['id' => 3],
                'association'  => [
                    ['__class__' => 'Test\Target2', 'id' => 2],
                    ['__class__' => 'Test\Target3', 'id' => 3],
                ]
            ],
            $this->context->getResult()
        );
    }

    public function testProcessForMultipleManyToOneExtendedAssociationWhenAllDependedAssociationsAreNull()
    {
        $data = [
            'association1' => null,
            'association2' => null
        ];
        $config = new EntityDefinitionConfig();
        $config->addField('association')->setDataType('association:multipleManyToOne:kind');

        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with('Test\Class', null, 'multipleManyToOne', 'kind')
            ->willReturn(
                [
                    'Test\Target1' => 'association1',
                    'Test\Target2' => 'association2'
                ]
            );

        $this->context->setClassName('Test\Class');
        $this->context->setResult($data);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
        $this->assertEquals(
            [
                'association1' => null,
                'association2' => null,
                'association'  => []
            ],
            $this->context->getResult()
        );
    }
}