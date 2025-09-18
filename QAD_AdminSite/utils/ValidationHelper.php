<?php 


// admin/utils/ValidationHelper.php - Input validation utilities

class ValidationHelper {
    public static function validateRequired($value, $fieldName = 'Field') {
        if (empty(trim($value))) {
            throw new InvalidArgumentException("$fieldName is required");
        }
        return trim($value);
    }
    
    public static function validateEmail($email, $fieldName = 'Email') {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("$fieldName must be a valid email address");
        }
        return $email;
    }
    
    public static function validateInteger($value, $min = null, $max = null, $fieldName = 'Value') {
        if (!is_numeric($value) || (int)$value != $value) {
            throw new InvalidArgumentException("$fieldName must be an integer");
        }
        
        $intValue = (int)$value;
        
        if ($min !== null && $intValue < $min) {
            throw new InvalidArgumentException("$fieldName must be at least $min");
        }
        
        if ($max !== null && $intValue > $max) {
            throw new InvalidArgumentException("$fieldName must be at most $max");
        }
        
        return $intValue;
    }
    
    public static function validateString($value, $minLength = 0, $maxLength = null, $fieldName = 'String') {
        $value = trim($value);
        $length = strlen($value);
        
        if ($length < $minLength) {
            throw new InvalidArgumentException("$fieldName must be at least $minLength characters long");
        }
        
        if ($maxLength !== null && $length > $maxLength) {
            throw new InvalidArgumentException("$fieldName must be at most $maxLength characters long");
        }
        
        return $value;
    }
    
    public static function validateIP($ip, $fieldName = 'IP Address') {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException("$fieldName must be a valid IP address");
        }
        return $ip;
    }
    
    public static function sanitizeHtml($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    
    public static function sanitizeFilename($filename) {
        // Remove potentially dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '', $filename);
        return $filename;
    }
}

?>