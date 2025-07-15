<?php

declare(strict_types=1);

include_once __DIR__ . '/numberDevice.php';

trait HelperAssociationDevice
{
    use HelperNumberDevice;

    private static function getAssociationCompatibility($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        if (!HasAction($variableID)) {
            return 'Action required';
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != 1 /* Integer */) {
            return 'Int required';
        }

        $checkLegacy = function ($profileName)
        {
            if (!IPS_VariableProfileExists($profileName)) {
                return 'Profile required';
            }

            $profile = IPS_GetVariableProfile($profileName);

            if (($profile['StepSize'] != 0) || (count($profile['Associations']) == 0)) {
                return 'No association profile';
            }

            // Initialize minimum and maximum one above/below legal maximum
            $minimumAssociation = count($profile['Associations']) + 1;
            $maximumAssociation = -1;
            foreach ($profile['Associations'] as $association) {
                if ($association['Value'] < 0) {
                    return 'Negative associations not allowed';
                }

                if ($association['Value'] > $maximumAssociation) {
                    $maximumAssociation = $association['Value'];
                }

                if ($association['Value'] < $minimumAssociation) {
                    $minimumAssociation = $association['Value'];
                }
            }

            if (($maximumAssociation - $minimumAssociation + 1) != count($profile['Associations'])) {
                return 'Associations not enumerated';
            }
        };

        if (!function_exists('IPS_GetVariablePresentation')) {
            $profileName = '';
            if ($targetVariable['VariableCustomProfile'] != '') {
                $profileName = $targetVariable['VariableCustomProfile'];
            } else {
                $profileName = $targetVariable['VariableProfile'];
            }
            $result = $checkLegacy($profileName);
            if (!empty($result)) {
                return $result;
            }
        } else {
            $presentation = IPS_GetVariablePresentation($variableID);
            if (empty($presentation)) {
                return 'Presentation required';
            }

            switch ($presentation['PRESENTATION']) {
                case VARIABLE_PRESENTATION_LEGACY:
                    $result = $checkLegacy($presentation['PROFILE']);
                    if (!empty($result)) {
                        return $result;
                    }

                    break;
                case VARIABLE_PRESENTATION_ENUMERATION:
                    $options = json_decode($presentation['OPTIONS'], true);
                    // Initialize minimum and maximum one above/below legal maximum
                    $minimumOption = count($options) + 1;
                    $maximumOption = -1;
                    foreach ($options as $option) {
                        if ($option['Value'] < 0) {
                            return 'Negative option not allowed';
                        }

                        if ($option['Value'] > $maximumOption) {
                            $maximumOption = $option['Value'];
                        }

                        if ($option['Value'] < $minimumOption) {
                            $minimumOption = $option['Value'];
                        }
                    }

                    if (($maximumOption - $minimumOption + 1) != count($options)) {
                        return 'Options not enumerated';
                    }
                    break;

                default:
                    return 'Unknown presentation';
            }
        }

        return 'OK';
    }

    private static function getAssociationNumber($variableID)
    {
        return self::getNumberValue($variableID);
    }

    private static function getAssociationString($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        return GetValueFormatted($variableID);
    }

    private static function setAssociationNumber($variableID, $value)
    {
        return self::setNumberValue($variableID, $value);
    }

    private static function setAssociationString($variableID, $value)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $presentation = IPS_GetVariablePresentation($variableID);
        if (empty($presentation)) {
            return false;
        }

        switch ($presentation['PRESENTATION']) {
            case VARIABLE_PRESENTATION_LEGACY:
                $profileName = $presentation['PROFILE'];

                if (!IPS_VariableProfileExists($profileName)) {
                    return false;
                }

                $profile = IPS_GetVariableProfile($profileName);

                foreach ($profile['Associations'] as $association) {
                    if (strcasecmp($association['Name'], $value) == 0) {
                        return self::setAssociationNumber($variableID, intval($association['Value']));
                    }
                }
                break;

            case VARIABLE_PRESENTATION_ENUMERATION:
                $options = json_decode($presentation['OPTIONS'], true);
                foreach ($options as $option) {
                    if (strcasecmp($option['Caption'], $value) == 0) {
                        return self::setAssociationNumber($variableID, intval($option['Value']));
                    }
                }

                break;

            default:
                return false;
        }

        // Fail, if no association was found
        return false;
    }

    private static function isValidAssociation($variableID, $value, $field)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $presentation = IPS_GetVariablePresentation($variableID);
        if (empty($presentation)) {
            return false;
        }

        // Legacy associations and options have the same structure so we can handle them basically the same way
        $associations = [];
        switch ($presentation['PRESENTATION']) {
            case VARIABLE_PRESENTATION_LEGACY:
                $profileName = $presentation['PROFILE'];
                if (!IPS_VariableProfileExists($profileName)) {
                    return false;
                }
                $associations = IPS_GetVariableProfile($profileName)['Associations'];
                break;

            case VARIABLE_PRESENTATION_ENUMERATION:
                $associations = json_decode($presentation['OPTIONS'], true);
                break;

            default:
                return false;

        }
        if (empty($associations)) {
            return false;
        }

        foreach ($associations as $association) {
            if (is_string($association[$field])) {
                if (strcasecmp($association[$field], $value) == 0) {
                    return true;
                }
            } else {
                if ($association[$field] == $value) {
                    return true;
                }
            }
        }

        // Fail, if no association was found
        return false;
    }

    private static function isValidAssociationNumber($variableID, $value)
    {
        return self::isValidAssociation($variableID, $value, 'Value');
    }

    private static function isValidAssociationString($variableID, $value)
    {
        $presentation = IPS_GetVariablePresentation($variableID);
        return self::isValidAssociation($variableID, $value, ($presentation['PRESENTATION'] ?? VARIABLE_PRESENTATION_LEGACY) == VARIABLE_PRESENTATION_LEGACY ? 'Name' : 'Caption');
    }

    private static function incrementAssociation($variableID, $increment)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }
        $presentation = IPS_GetVariablePresentation($variableID);
        if (empty($presentation)) {
            return false;
        }

        // Legacy associations and options have the same structure so we can handle them basically the same way
        $associations = [];
        switch ($presentation['PRESENTATION']) {
            case VARIABLE_PRESENTATION_LEGACY:
                $profileName = $presentation['PROFILE'];
                if (!IPS_VariableProfileExists($profileName)) {
                    return false;
                }
                $associations = IPS_GetVariableProfile($profileName)['Associations'];
                break;

            case VARIABLE_PRESENTATION_ENUMERATION:
                $associations = json_decode($presentation['OPTIONS'], true);
                break;

            default:
                return false;

        }
        if (empty($associations)) {
            return false;
        }

        $oneBased = true; // Is the first association a 1 or a 0?

        foreach ($associations as $association) {
            if ($association['Value'] == 0) {
                $oneBased = false;
                break;
            }
        }

        $currentValue = GetValue($variableID);

        // Convert one-based to zero-based
        if ($oneBased) {
            $currentValue--;
        }

        $newValue = ($currentValue + $increment) % count($associations);

        if ($oneBased) {
            $newValue++;
        }

        return self::setAssociationNumber($variableID, $newValue);
    }
}
