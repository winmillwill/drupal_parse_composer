<?php

namespace Drupal\ParseComposer\Parser;

class Release
{

    public function __construct(\SimpleXMLElement $release) 
    {
        // convert to array for better handle
        $this->release = json_decode(json_encode($release), true);
    }

    public function getDownload() 
    {
        return isset($this->release['download_link']) ? $this->release['download_link'] : null;
    }

    public function getHomepage() 
    {
        return $this->release['release_link'];
    }

    public function getName() 
    {
        return $this->release['name'];
    }

    public function getReference()
    {
        return $this->release['mdhash'];
    }

    public function getTime()
    {
        return $this->release['date'];
    }

    public function getCoreVersion()
    {
        return $this->release['version'][0];
    }

    public function getVersion()
    {
        $versionNumbers = array_filter(
            preg_split('/[-.x]+/', $this->release['version']),
            'intval'
        );
        $versionNumbers = array_pad($versionNumbers, 3, 0);
        $version = implode('.', $versionNumbers);
        $version .= isset($this->release['version_extra'])
            ? '-'.$this->release['version_extra']
            : '';
        return $version;
    }
}
