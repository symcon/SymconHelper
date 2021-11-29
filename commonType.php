<?php

declare(strict_types=1);

abstract class CommonType
{
    protected $instanceID = 0;
    protected $implementedCapabilities = [];
    protected $detectionRequiredCapabilities = null;
    protected $detectionMinimumCapabilities = 0;
    protected $capabilityPrefix = '';

    protected $displayStatusPrefix = false;
    protected $skipMissingStatus = false;
    protected $columnWidth = '';

    public function __construct(int $instanceID, string $capabilityPrefix)
    {
        $this->instanceID = $instanceID;
        $this->capabilityPrefix = $capabilityPrefix;
    }

    public function getColumns()
    {
        $columns = [];
        foreach ($this->implementedCapabilities as $capability) {
            $newColumns = $this->generateCapabilityObject($capability)->getColumns();
            if ($this->columnWidth !== '') {
                foreach ($newColumns as &$newColumn) {
                    $newColumn['width'] = $this->columnWidth;
                }
            }
            $columns = array_merge($columns, $newColumns);
        }
        return $columns;
    }

    public function getStatus($configuration)
    {
        if ($configuration['Name'] == '') {
            return 'No name';
        }

        $okFound = false;

        foreach ($this->implementedCapabilities as $capability) {
            $capabilityObject = $this->generateCapabilityObject($capability);
            $status = $capabilityObject->getStatus($configuration);
            if (($status != 'OK') && (($status != 'Missing') || !$this->skipMissingStatus)) {
                if ($this->displayStatusPrefix) {
                    return $capabilityObject->getStatusPrefix() . $status;
                } else {
                    return $status;
                }
            } elseif ($status == 'OK') {
                $okFound = true;
            }
        }

        if ($okFound) {
            return 'OK';
        } else {
            return 'Missing';
        }
    }

    public function getObjectIDs($configuration)
    {
        $result = [];
        foreach ($this->implementedCapabilities as $capability) {
            $result = array_unique(array_merge($result, $this->generateCapabilityObject($capability)->getObjectIDs($configuration)));
        }

        return $result;
    }

    public function getDetectedDevices()
    {
        if ($this->isExpertDevice()) {
            return [];
        }
        $requiredCapabilities = ($this->detectionRequiredCapabilities !== null) ? $this->detectionRequiredCapabilities : $this->implementedCapabilities;
        $result = [];
        foreach (IPS_GetInstanceList() as $instanceID) {
            $instanceResult = [];
            $detectedCapabilities = 0;
            foreach ($this->implementedCapabilities as $capability) {
                $detectedVariables = $this->generateCapabilityObject($capability)->getDetectedVariables($instanceID);
                if ($detectedVariables !== false) {
                    $detectedCapabilities++;
                    foreach ($detectedVariables as $name => $value) {
                        $instanceResult[$name] = $value;
                    }
                } elseif (in_array($capability, $requiredCapabilities)) {
                    $instanceResult = false;
                    break;
                }
            }

            if (($instanceResult !== false) && ($detectedCapabilities >= $this->detectionMinimumCapabilities)) {
                $result[$instanceID] = $instanceResult;
            }
        }
        return $result;
    }

    public function isExpertDevice()
    {
        return false;
    }

    abstract public function getPosition();
    abstract public function getCaption();
    abstract public function getTranslations();

    protected function generateCapabilityObject(string $capabilityName)
    {
        $capabilityClass = $this->capabilityPrefix . $capabilityName;
        $capabilityObject = new $capabilityClass($this->instanceID);
        return $capabilityObject;
    }
}
