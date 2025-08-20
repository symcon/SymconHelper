<?php

declare(strict_types=1);

abstract class CommonCapability
{
    protected $instanceID = 0;

    public function __construct(int $instanceID)
    {
        $this->instanceID = $instanceID;
    }

    public function getDetectedVariables($instanceID)
    {
        // Does the capability support automatic detection?
        $supportedProfileList = $this->getSupportedProfiles();
        if (!$supportedProfileList) {
            return false;
        }

        $supportedPresentationList = $this->getSupportedPresentations();
        if (!$supportedPresentationList) {
            return false;
        }

        $result = [];
        foreach ($supportedPresentationList as $name => $supportedPresentations) {
            $result[$name] = false;
        }

        foreach (IPS_GetChildrenIDs($instanceID) as $variableID) {
            if (!IPS_VariableExists($variableID) || !HasAction($variableID)) {
                continue;
            }

            $targetVariable = IPS_GetVariable($variableID);
            $presentation = IPS_GetVariablePresentation($variableID);
            if (!array_key_exists('PRESENTATION', $presentation)) {
                if (HasAction($variableID)) {
                    if ($targetVariable['VariableType'] == VARIABLETYPE_BOOLEAN) {
                        $presentation['PRESENTATION'] = VARIABLE_PRESENTATION_SWITCH;
                        $presentation['USAGE_TYPE'] = 0;
                    }
                    else {
                        $presentation['PRESENTATION'] = VARIABLE_PRESENTATION_VALUE_INPUT;
                    }
                }
                else {
                    $presentation['PRESENTATION'] = VARIABLE_PRESENTATION_VALUE_PRESENTATION;
                }
            }
            $profileName = '';
            if ($targetVariable['VariableCustomProfile'] != '') {
                $profileName = $targetVariable['VariableCustomProfile'];
            } else {
                $profileName = $targetVariable['VariableProfile'];

            }
            foreach ($supportedPresentationList as $name => $supportedPresentations) {
                // We check if our variable has one  of the supported presentations
                if (array_key_exists($presentation['PRESENTATION'], $supportedPresentations)) {
                    $supported = true;
                    // We check all parameters that are defined in the supported presentation
                    foreach ($supportedPresentations[$presentation['PRESENTATION']] as $parameter => $value) {
                        // If it is an array if the value is in the array
                        if (is_array($value)) {
                            if (!in_array($presentation[$parameter], $value)) {
                                $supported = false;
                                break;
                            }
                        } else {
                            if ($presentation[$parameter] != $value) {
                                $supported = false;
                                break;
                            }
                        }
                    }
                    if ($supported) {
                        // If there is more than one supported variable, we do not detect the instance as supported due to ambiguous use
                        if ($result[$name] === false) {
                            $result[$name] = $variableID;
                        } else {
                            return false;
                        }
                    } else {
                        break;
                    }
                }
            }
        }

        // Check if all properties were filled
        foreach ($result as $name => $value) {
            if ($value === false) {
                return false;
            }
        }

        return $result;
    }

    abstract public function getColumns();
    abstract public function getStatus($configuration);
    abstract public function getStatusPrefix();
    abstract public function getObjectIDs($configuration);

    protected function getSupportedProfiles()
    {
        return false;
    }
}
