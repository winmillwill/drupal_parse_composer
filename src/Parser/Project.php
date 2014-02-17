<?php

namespace Drupal\ParseComposer\Parser;

use Drupal\ParseComposer\Parser\HttpClient;

class Project {

    /**
     * @var \SimpleXMLElement
     */
    public $xml;
    private $types = array('Modules', 'Themes', 'Drupal core', 'Distributions');

    protected $terms;
    public static $inCore = array();

    /**
     * @var Release[]
     */
    protected $releases;
    private static $updateXmlPattern = 'http://updates.drupal.org/release-history/%s/%d.x';

    function __construct($xml, $version)
    {
        if (!$xml instanceof \SimpleXMLElement) {
            $xml = @simplexml_load_string($xml);
        }
        $this->version = $version;
        $this->xml = $xml;
    }

    static function create($projectName, $version)
    {
      $response = HttpClient::goGet(sprintf(static::$updateXmlPattern, $projectName, $version));
      $project = new Project($response, $version);
      return $project;
    }

    public function getPrettyName()
    {
        return 'drupal/'.$this->xml->short_name;
    }

    public function getComposerPackages(array &$found = array())
    {
        $found['composer/installers'] = true;
        $composerPackages = array();
        if (!count($releases = $this->getReleases())) {
            return $composerPackages;
        }
        $converter = new ComposerPackageConvert($this);
        foreach ($releases as $release) {
            $package = $converter->toComposerPackage($release);
            $found[$package->getPrettyName()] = true;
            $composerPackages[] = $package;
            foreach ($package->getReplaces() as $replaced) {
                $found[$replaced->getTarget()] = true;
            }
            foreach ($package->getRequires() as $required) {
                list($d, $project) = explode('/', $required->getTarget());
                if (!isset($found[$required->getTarget()]) && $d === 'drupal') {
                    $project = static::create($project, $this->version);
                    $composerPackages = array_merge(
                        $composerPackages,
                        $project->getComposerPackages($found)
                    );
                }
            }
        }
        return $composerPackages;
    }

    /**
     * @return Release[]
     */
    function getReleases() {

        if ($this->releases) {
            return $this->releases;
        }

        if (@!$this->xml->releases) {
            return $this->releases = array();
        }

        foreach ($this->xml->releases->release as $release) {
            $this->releases[] = new Release($release);
        }

        return $this->releases;
    }

    function getTerms() {

        if ($this->terms) {
            return $this->terms;
        }

        if(!$this->xml->terms) {
            return $this->terms = array();
        }

        $terms = array();

        foreach($this->xml->terms->term as $term) {
            $terms[(string) $term->name][] = (string) $term->value;
        }

        return $this->terms = $terms;

    }

    function getTerm($name, $default = null) {
        $terms = $this->getTerms();

        return isset($terms[$name]) ? $terms[$name] : $default;
    }

    function getProjectType() {
        $project_terms = $this->getTerm('Projects');

        foreach ($this->types as $type) {
          if (in_array($type, $project_terms)) {
            return $type;
          }
        }
    }

}
