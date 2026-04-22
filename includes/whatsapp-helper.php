<?php
/**
 * WhatsApp Helper Functions
 */

/**
 * Generate WhatsApp message link with pre-filled message
 * @param string $name - Customer name
 * @param string $phone - Customer phone
 * @param string $service - Service interested in
 * @return string - WhatsApp link
 */
function getWhatsAppLink($name = '', $phone = '', $service = 'Services') {
    $message = "Hello, I am interested in your $service. ";
    
    if (!empty($name)) {
        $message .= "Name: $name. ";
    }
    
    if (!empty($phone)) {
        $message .= "Phone: $phone. ";
    }
    
    $message .= "Please get back to me soon.";
    
    $whatsapp_number = '919068899033';
    $encoded_message = urlencode($message);
    
    return "https://wa.me/{$whatsapp_number}?text={$encoded_message}";
}

/**
 * Generate WhatsApp button with analytics
 * @param string $service - Service name
 * @param string $name - Customer name (optional)
 * @param string $phone - Customer phone (optional)
 * @return string - HTML button
 */
function getWhatsAppButton($service = 'Services', $name = '', $phone = '') {
    $link = getWhatsAppLink($name, $phone, $service);
    $onclick = "gtag('event','conversion',{'send_to':'AW-XXXXXXXXX/whatsapp'})";
    
    return "<a href=\"{$link}\" target=\"_blank\" style=\"background: #25D366; color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s; display: inline-block;\" onclick=\"{$onclick}\">💬 WhatsApp Now</a>";
}

/**
 * Get tracking onclick for call button
 * @return string - onclick code
 */
function getCallTracking() {
    return "gtag('event','conversion',{'send_to':'AW-XXXXXXXXX/call'})";
}

/**
 * Get tracking onclick for form submit
 * @return string - onclick code
 */
function getFormTracking() {
    return "gtag('event','conversion',{'send_to':'AW-XXXXXXXXX/form_submit'})";
}
?>
