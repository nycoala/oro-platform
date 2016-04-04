<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Create\SaveOrmEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class SaveOrmEntityTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var SaveOrmEntity */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new SaveOrmEntity($this->doctrineHelper);
    }

    public function testProcessWhenValidationErrorsOccurs()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityManager');

        $this->context->addError(new Error());
        $this->processor->process($this->context);
    }

    public function testProcessWhenNoEntity()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityManager');

        $this->processor->process($this->context);
    }

    public function testProcessForNotSupportedEntity()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityManager');

        $this->context->setResult([]);
        $this->processor->process($this->context);
    }

    public function testProcessForNotManageableEntity()
    {
        $entity = new \stdClass();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($this->identicalTo($entity), false)
            ->willReturn(null);

        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }

    public function testProcessForManageableEntity()
    {
        $entity = new \stdClass();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($this->identicalTo($entity), false)
            ->willReturn($em);

        $em->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($entity));
        $em->expects($this->once())
            ->method('flush')
            ->with($this->identicalTo($entity));

        $this->context->setResult($entity);
        $this->processor->process($this->context);
    }
}
