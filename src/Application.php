<?php

namespace Drupal\ParseComposer;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Drupal\ParseComposer\Command\GeneratePackagesCommand as Command;

class Application extends BaseApplication
{
    protected function getCommandName(InputInterface $input)
    {
        return 'drupal-packagist';
    }

    protected function getDefaultCommands()
    {
        $default = parent::getDefaultCommands();
        $default[] = new Command();
        return $default;
    }

    public function getDefinition()
    {
        $inputDefinition = parent::getDefinition();
        $inputDefinition->setArguments();
        return $inputDefinition;
    }
}
