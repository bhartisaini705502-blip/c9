<?php
/**
 * Google Places API Configuration
 * Get your API key from: https://console.cloud.google.com/
 */

// Get API key from environment variable
define('GOOGLE_PLACES_API_KEY', getenv('GOOGLE_API_KEY2') ?: getenv('GOOGLE_API_KEY') ?: '');

/**
 * Generate Google Places Photo URL
 * @param string $photoReference - Photo reference from Google Places API
 * @param int $maxWidth - Max width of the image (default 400)
 * @return string - Full URL to the photo
 */
function getGooglePlacesPhotoUrl($photoReference, $maxWidth = 400) {
    $apiKey = GOOGLE_PLACES_API_KEY;
    
    if (empty($apiKey) || empty($photoReference)) {
        return null;
    }
    
    return "https://maps.googleapis.com/maps/api/place/photo?maxwidth=" . $maxWidth . "&photo_reference=" . urlencode($photoReference) . "&key=" . $apiKey;
}

/**
 * Parse photo references from database field
 * @param string $photoReferencesStr - Pipe-separated photo references
 * @return array - Array of photo references
 */
function parsePhotoReferences($photoReferencesStr) {
    if (empty($photoReferencesStr)) {
        return [];
    }
    
    $references = explode('|', $photoReferencesStr);
    return array_filter(array_map('trim', $references));
}

?>
