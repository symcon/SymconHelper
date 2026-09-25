<?php

declare(strict_types=1);

include_once __DIR__ . '/numberDevice.php';
include_once __DIR__ . '/variablePresentation.php';

trait HelperAssociationDevice
{
    use HelperNumberDevice;
    use HelperVariablePresentation;

    private static function getAssociationCompatibility($variableID)
    {
        if (!IPS_VariableExists($variableID)) {
            return 'Missing';
        }

        if (!HasAction($variableID)) {
            return 'Action required';
        }

        $targetVariable = IPS_GetVariable($variableID);

        if ($targetVariable['VariableType'] != VARIABLETYPE_INTEGER) {
            return 'Integer required';
        }

        $presentation = self::resolvePresentation($variableID);
        if ($presentation === false) {
            return 'Presentation required';
        }

        $checkEnumerated = function ($options, $negativeError, $enumerationError)
        {
            // Initialize minimum and maximum one above/below legal maximum
            $minimumOption = count($options) + 1;
            $maximumOption = -1;
            foreach ($options as $option) {
                if ($option['Value'] < 0) {
                    return $negativeError;
                }

                if ($option['Value'] > $maximumOption) {
                    $maximumOption = $option['Value'];
                }

                if ($option['Value'] < $minimumOption) {
                    $minimumOption = $option['Value'];
                }
            }

            if (($maximumOption - $minimumOption + 1) != count($options)) {
                return $enumerationError;
            }
        };

        switch ($presentation['kind']) {
            case 'legacy':
                if (!$presentation['profileExists']) {
                    return 'Profile required';
                }

                if (($presentation['stepSize'] != 0) || (count($presentation['options']) == 0)) {
                    return 'No association profile';
                }

                $result = $checkEnumerated($presentation['options'], 'Negative associations not allowed', 'Associations not enumerated');
                if (!empty($result)) {
                    return $result;
                }
                break;

            case 'enumeration':
                $result = $checkEnumerated($presentation['options'], 'Negative option not allowed', 'Options not enumerated');
                if (!empty($result)) {
                    return $result;
                }
                break;

            default:
                return 'Unsupported presentation';
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

        $associations = self::getAssociations($variableID);
        if (($associations === false) || empty($associations)) {
            return false;
        }

        $field = self::getAssociationField($variableID);
        foreach ($associations as $association) {
            if (strcasecmp($association[$field], $value) == 0) {
                return self::setAssociationNumber($variableID, intval($association['Value']));
            }
        }

        // Fail, if no association was found
        return false;
    }

    private static function isValidAssociation($variableID, $value, $field)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $associations = self::getAssociations($variableID);
        if ($associations === false) {
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
        return self::isValidAssociation($variableID, $value, self::getAssociationField($variableID));
    }

    private static function incrementAssociation($variableID, $increment)
    {
        if (!IPS_VariableExists($variableID)) {
            return false;
        }

        $associations = self::getAssociations($variableID);
        if ($associations === false) {
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

        // Double modulo, so negative increments wrap around properly as well
        $count = count($associations);
        $newValue = ((($currentValue + $increment) % $count) + $count) % $count;

        if ($oneBased) {
            $newValue++;
        }

        return self::setAssociationNumber($variableID, $newValue);
    }

    //Legacy associations use 'Name' as their caption field, while enumeration options use 'Caption'
    private static function getAssociationField($variableID)
    {
        $presentation = self::resolvePresentation($variableID);

        if (($presentation === false) || ($presentation['kind'] == 'legacy')) {
            return 'Name';
        }

        return 'Caption';
    }

    // Legacy associations and options have the same structure so we can handle them basically the same way
    private static function getAssociations($variableID)
    {
        $presentation = self::resolvePresentation($variableID);
        if ($presentation === false) {
            return false;
        }

        if (!in_array($presentation['kind'], ['legacy', 'enumeration']) || ($presentation['options'] === null)) {
            return false;
        }

        return $presentation['options'];
    }
}
