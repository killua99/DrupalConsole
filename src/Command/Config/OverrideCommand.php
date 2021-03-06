<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Config\OverrideCommand.
 */

namespace Drupal\Console\Command\Config;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

class OverrideCommand extends Command
{
    use ContainerAwareCommandTrait;

    protected function configure()
    {
        $this
            ->setName('config:override')
            ->setDescription($this->trans('commands.config.override.description'))
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                $this->trans('commands.config.override.arguments.name')
            )
            ->addArgument('key', InputArgument::REQUIRED, $this->trans('commands.config.override.arguments.key'))
            ->addArgument('value', InputArgument::REQUIRED, $this->trans('commands.config.override.arguments.value'));
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $name = $input->getArgument('name');
        $configFactory = $this->getDrupalService('config.factory');
        $names = $configFactory->listAll();
        if ($name) {
            if (!in_array($name, $names)) {
                $io->warning(
                    sprintf(
                        $this->trans('commands.config.override.messages.invalid-name'),
                        $name
                    )
                );
                $name = null;
            }
        }
        if (!$name) {
            $name = $io->choiceNoList(
                $this->trans('commands.config.override.questions.name'),
                $names
            );
            $input->setArgument('name', $name);
        }
        $key = $input->getArgument('key');
        if (!$key) {
            $configStorage = $this->getDrupalService('config.storage');
            if ($configStorage->exists($name)) {
                $configuration = $configStorage->read($name);
            }
            $key = $io->choiceNoList(
                $this->trans('commands.config.override.questions.key'),
                array_keys($configuration)
            );
            $input->setArgument('key', $key);
        }
        $value = $input->getArgument('value');
        if (!$value) {
            $value = $io->ask(
                $this->trans('commands.config.override.questions.value')
            );
            $input->setArgument('value', $value);
        }
    }
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $configName = $input->getArgument('name');
        $key = $input->getArgument('key');
        $value = $input->getArgument('value');

        $config = $this->getDrupalService('config.factory')->getEditable($configName);

        $configurationOverrideResult = $this->overrideConfiguration($config, $key, $value);

        $config->save();

        $io->info($this->trans('commands.config.override.messages.configuration'), false);
        $io->comment($configName);

        $tableHeader = [
            $this->trans('commands.config.override.messages.configuration-key'),
            $this->trans('commands.config.override.messages.original'),
            $this->trans('commands.config.override.messages.updated'),
        ];
        $tableRows = $configurationOverrideResult;
        $io->table($tableHeader, $tableRows);

        $config->save();
    }


    protected function overrideConfiguration($config, $key, $value)
    {
        $result[] = [
            'configuration' => $key,
            'original' => $config->get($key),
            'updated' => $value,
        ];
        $config->set($key, $value);

        return $result;
    }
}
