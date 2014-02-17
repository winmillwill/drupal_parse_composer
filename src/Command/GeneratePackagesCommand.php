<?php

namespace Drupal\ParseComposer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Composer\Json\JsonFile;
use Composer\Package\Dumper\ArrayDumper;
use Drupal\ParseComposer\Parser\Project;

class GeneratePackagesCommand extends Command {

    protected function configure()
    {
      $this->setName('drupal-packagist')
        ->setDescription('Packagist generator out of updates.drupal.org')
        ->addArgument(
            'projects',
            InputArgument::IS_ARRAY,
            'list of drupal projects to search for'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach($input->getArgument('projects') as $projectInput) {
            @list($projectName, $versions) = explode(':', $projectInput);
            $versions = isset($versions) ? explode(',', $versions) : [7];
            foreach ($versions as $version) {
                $projects[$version][] = $projectName;
            }
        }
        $packages = array();
        foreach ($projects as $version => $drupalProjects) {
            $found = array();
            array_unshift($drupalProjects, 'drupal');
            foreach ($drupalProjects as $project) {
                $output->writeln(sprintf('working on %s-%dx', $project, $version));
                try {
                    $drupalProject  = Project::create($project, $version);
                    $packages = array_merge($packages, $drupalProject->getComposerPackages($found));
                } catch (\Exception $e) {
                    $output->writeln($e->getMessage());
                    var_dump($e->getTrace());
                }
            }
        }
        $writer = new JsonFile('packages.json');
        $dumper = new ArrayDumper();
        $composerPackages = array();
        foreach ($packages as $package) {
            $composerPackages[$package->getPrettyName()][$package->getVersion()] = $dumper->dump($package);
        }
        $writer->write(array('packages' => $composerPackages));
    }
}
