<?php

namespace Drupal\ParseComposer\Parser;

use Composer\Package\Package;
use Composer\Package\Loader\ArrayLoader;
use Composer\Json\JsonFile;
use Composer\Package\Version\VersionParser;
use Composer\Package\Link;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

class ComposerPackageConvert {

    protected $xml;
    protected $version_parser;

    protected $project_types = array(
        'Modules' => 'drupal-module',
        'Themes' => 'drupal-theme',
        'Distributions' => 'drupal-profile',
        'Drupal core' => 'drupal-core'
    );

    function __construct(Project $project)
    {
        $this->project = $project;
        $this->version_parser = new VersionParser();
        $this->requires = $this->version_parser->parseLinks(
          null,
          null,
          'requires',
          [
              'composer/installers' => '*',
              'drupal/drupal' => $project->version.'.*'
          ]
        );
    }

    public function drupalVersionToComposer(Release $release)
    {
        $version = $release->getVersion();
        try {
            $prettyVersion = $this->version_parser->normalize($version);
        }
        catch (\Exception $e) {
            $prettyVersion = $version;
        }
        return array($version, $prettyVersion);
    }

    public function toComposerPackage(Release $release)
    {
        $type = $this->project->getProjectType();
        if (!isset($this->project_types[$type])) {
            throw new \RuntimeException('Unknown project type of ' . $type);
        }
        $short_name = $this->project->xml->short_name;
        $name       = $this->project->getPrettyName();
        list($version, $prettyVersion) = $this->drupalVersionToComposer($release);
        $package = new Package($name, $prettyVersion, $version);

        $package->setType($this->project_types[$type]);
        $package->setDistType('tar');
        $package->setDistUrl($release->getDownload());
        $package->setDistReference($release->getReference());

        $temp = tempnam(sys_get_temp_dir(), $name);
        file_put_contents(
            $tmp = $temp.'.tar.gz',
            HttpClient::goGet($release->getDownload())
        );
        unlink($temp);
        $archive = new \PharData($tmp);
        $dir = tempnam(sys_get_temp_dir(), "{$name}_dir");
        if (file_exists($dir) && unlink($dir) && mkdir($dir)) {
            $archive->extractTo($dir);
        }
        $fs = new Filesystem();
        $finder = new Finder();
        $composerFiles = $finder->files()->in($dir)->name('composer.json');
        $requires = array();
        foreach ($composerFiles as $composerFile) {
            $config = new JsonFile($composerFile->getPathName());
            $depPackage = $config->read();
            $required = isset($depPackage['require']) ? $depPackage['require'] : array();
            $requires = array_merge(
                $requires,
                $this->version_parser->parseLinks(
                    $package->getPrettyName(),
                    $package->getPrettyVersion(),
                    'requires',
                    $required
                )
            );
        }
        $infoFiles = $finder->files()->in($dir)->name('*.info');
        $subProjects = array();
        foreach($infoFiles as $infoFile) {
            if (($subProject = $infoFile->getBasename('.info')) != $short_name) {
                $subProjects[] = $subProject;
            }
        }
        foreach($infoFiles as $infoFile) {
            $info = \drupal_parse_info_file($infoFile->getPathName());
            if (isset($info['package']) && $info['package'] !== 'Testing') {
                $deps = isset($info['dependencies']) ? $info['dependencies'] : [];
                foreach ($deps as $dep) {
                    array_push($subProjects, $short_name);
                    if (!in_array($dep, $subProjects)) {
                        $requires[] = $this->link($dep, $package->getPrettyVersion(), 'requires', $release->getCoreVersion().'.*');
                    }
                }
            }
        }
        $replaces = array();
        foreach ($subProjects as $subProject) {
            $replaces[] = $this->link($subProject, $package->getPrettyVersion(), 'replaces', 'self.version');
        }
        $fs->remove([$dir, $tmp]);
        $package->setRequires(array_merge($this->requires, $requires));
        $package->setReplaces($replaces);
        return $package;
    }

    public function link($drupalProject, $sourceVersion, $description, $constraint)
    {
        return $this->version_parser->parseLinks(
            'drupal/'.$this->project->xml->short_name,
            $sourceVersion,
            $description,
            ["drupal/$drupalProject" => $constraint]
        )["drupal/$drupalProject"];
    }
}
