<?php

namespace Oro\Bundle\InstallerBundle\Process;

use Sylius\Bundle\FlowBundle\Process\Builder\ProcessBuilderInterface;
use Sylius\Bundle\FlowBundle\Process\Scenario\ProcessScenarioInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class InstallerScenario implements ProcessScenarioInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function build(ProcessBuilderInterface $builder)
    {
        $builder
            ->add('configure', new Step\ConfigureStep())
            ->add('schema', new Step\SchemaStep())
            ->add('setup', new Step\SetupStep())
            ->add('installation', new Step\InstallationStep())
            ->add('final', new Step\FinalStep())
            ->setRedirect('oro_default');
    }
}
