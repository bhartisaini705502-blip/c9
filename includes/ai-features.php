<?php
/**
 * AI Features Helper Functions
 */

require_once dirname(__DIR__) . '/config/ai.php';

/**
 * Get AI description (cached in DB or generate new)
 */
function getAIDescription($businessId, $name, $category, $city) {
    global $conn;
    
    // Check if already cached
    $stmt = $conn->prepare("SELECT ai_description FROM extracted_businesses WHERE id = ?");
    $stmt->bind_param('i', $businessId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result && !empty($result['ai_description'])) {
        return $result['ai_description'];
    }
    
    // Try Gemini API first
    $ai = getGeminiAI();
    if ($ai) {
        $description = $ai->generateBusinessDescription($name, $category, $city);
        
        if ($description) {
            // Cache in database
            $stmt = $conn->prepare("UPDATE extracted_businesses SET ai_description = ? WHERE id = ?");
            $stmt->bind_param('si', $description, $businessId);
            $stmt->execute();
            
            return $description;
        }
    }
    
    // Fallback: Generate smart description without API
    $description = generateSmartDescription($name, $category, $city);
    
    // Cache fallback description
    $stmt = $conn->prepare("UPDATE extracted_businesses SET ai_description = ? WHERE id = ?");
    $stmt->bind_param('si', $description, $businessId);
    $stmt->execute();
    
    return $description;
}

/**
 * Generate smart description without API (fallback)
 */
function generateSmartDescription($name, $category, $city) {
    // Category-specific templates
    $templates = [
        'Hotel' => "{name} is a well-established hotel in {city} offering comfortable accommodations and excellent hospitality services. Whether you're visiting for business or leisure, {name} provides a welcoming environment with quality amenities to ensure a pleasant stay.",
        'Restaurant' => "{name} is a popular restaurant in {city} known for serving delicious {category} cuisine. With a diverse menu and attentive service, it's a favorite destination for food enthusiasts seeking authentic dining experiences.",
        'Cafe' => "{name} is a charming cafe located in {city}, perfect for coffee lovers and those looking for a cozy atmosphere. They offer a variety of beverages and snacks, making it an ideal spot to relax and meet friends.",
        'Gym' => "{name} is a well-equipped fitness center in {city} dedicated to helping members achieve their health goals. With modern equipment and professional trainers, it offers comprehensive fitness solutions for all fitness levels.",
        'Hospital' => "{name} is a trusted healthcare facility in {city} providing comprehensive medical services and professional care. Equipped with modern facilities and experienced staff, it's committed to delivering quality healthcare to the community.",
        'School' => "{name} is an educational institution in {city} dedicated to fostering academic excellence and personal development. With experienced faculty and modern learning facilities, it provides quality education to students.",
        'Shop' => "{name} is a well-known retail store in {city} offering a wide selection of quality products. It's a convenient shopping destination known for competitive prices and excellent customer service.",
        'Salon' => "{name} is a professional salon in {city} specializing in beauty and grooming services. With skilled stylists and premium products, it offers a relaxing experience and exceptional results.",
        'Bank' => "{name} is a reputable financial institution in {city} providing comprehensive banking services. From personal banking to business solutions, it's committed to meeting diverse financial needs with professional expertise.",
        'Pharmacy' => "{name} is a trusted pharmacy in {city} dedicated to providing quality medications and healthcare advice. With a wide range of products and knowledgeable staff, it's your reliable healthcare partner.",
    ];
    
    // Find matching template or use default
    $template = $templates[$category] ?? $templates['Shop']; // Default to Shop template
    
    // Replace placeholders
    $description = str_replace(
        ['{name}', '{category}', '{city}'],
        [$name, strtolower($category), $city],
        $template
    );
    
    return $description;
}

/**
 * Get AI category content (cached)
 */
function getAICategoryContent($category, $city) {
    global $conn;
    
    // Check if cached in static_pages
    $stmt = $conn->prepare("SELECT content FROM static_pages WHERE slug = ?");
    $slug = 'ai-' . sanitizeSlug($category);
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        return $result['content'];
    }
    
    // Generate new content
    $ai = getGeminiAI();
    if ($ai) {
        $content = $ai->generateCategoryContent($category, $city);
        
        if ($content) {
            // Cache in database
            $stmt = $conn->prepare("
                INSERT INTO static_pages (slug, title, content, meta_description) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE content = ?
            ");
            $title = ucfirst($category) . ' in ' . $city;
            $meta = "AI-generated guide for {$category} in {$city}";
            $stmt->bind_param('sssss', $slug, $title, $content, $meta, $content);
            $stmt->execute();
            
            return $content;
        }
    }
    
    return null;
}

/**
 * Helper to sanitize slug
 */
function sanitizeSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Generate tags for business
 */
function getAIBusinessTags($businessId, $name, $category, $description = '') {
    global $conn;
    
    // Check if cached
    $stmt = $conn->prepare("SELECT ai_tags FROM extracted_businesses WHERE id = ?");
    $stmt->bind_param('i', $businessId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result && !empty($result['ai_tags'])) {
        return explode(',', $result['ai_tags']);
    }
    
    // Generate new tags
    $ai = getGeminiAI();
    if ($ai) {
        $tags = $ai->generateBusinessTags($name, $category, $description);
        
        if ($tags) {
            // Cache in database
            $stmt = $conn->prepare("UPDATE extracted_businesses SET ai_tags = ? WHERE id = ?");
            $stmt->bind_param('si', $tags, $businessId);
            $stmt->execute();
            
            return array_map('trim', explode(',', $tags));
        }
    }
    
    return [];
}

/**
 * Generate review summary (pros, cons, summary)
 */
function getAIReviewSummary($businessId, $name, $reviews) {
    global $conn;
    
    // Check if cached
    $stmt = $conn->prepare("SELECT ai_review_summary FROM extracted_businesses WHERE id = ?");
    $stmt->bind_param('i', $businessId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result && !empty($result['ai_review_summary'])) {
        return json_decode($result['ai_review_summary'], true);
    }
    
    // Generate new summary
    $ai = getGeminiAI();
    if ($ai && !empty($reviews)) {
        $summaryText = $ai->generateReviewSummary($name, $reviews);
        
        if ($summaryText) {
            // Parse JSON response
            $summary = json_decode($summaryText, true);
            
            if ($summary && isset($summary['pros'], $summary['cons'], $summary['summary'])) {
                // Cache in database
                $jsonSummary = json_encode($summary);
                $stmt = $conn->prepare("UPDATE extracted_businesses SET ai_review_summary = ? WHERE id = ?");
                $stmt->bind_param('si', $jsonSummary, $businessId);
                $stmt->execute();
                
                return $summary;
            }
        }
    }
    
    return null;
}

/**
 * Generate FAQs for a business
 */
function getAIFAQs($businessId, $name, $category, $description) {
    global $conn;
    
    // Check if cached
    $stmt = $conn->prepare("SELECT ai_faqs FROM extracted_businesses WHERE id = ?");
    $stmt->bind_param('i', $businessId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result && !empty($result['ai_faqs'])) {
        return json_decode($result['ai_faqs'], true);
    }
    
    // Try to generate with Gemini API
    $ai = getGeminiAI();
    if ($ai) {
        $prompt = "Generate 5 relevant FAQs for a {$category} business called '{$name}'. Description: {$description}. Return as JSON array: [{\"q\": \"Question?\", \"a\": \"Answer.\"}, ...]. Focus on service hours, pricing, booking, and common questions.";
        $faqText = $ai->generateContent($prompt);
        
        if ($faqText) {
            // Try to parse as JSON
            $faqs = json_decode($faqText, true);
            if (is_array($faqs) && !empty($faqs)) {
                // Cache in database
                $jsonFAQs = json_encode($faqs);
                $stmt = $conn->prepare("UPDATE extracted_businesses SET ai_faqs = ? WHERE id = ?");
                $stmt->bind_param('si', $jsonFAQs, $businessId);
                $stmt->execute();
                
                return $faqs;
            }
        }
    }
    
    // Fallback: Generate smart FAQs without API
    $faqs = generateSmartFAQs($name, $category);
    
    // Cache fallback FAQs
    $jsonFAQs = json_encode($faqs);
    $stmt = $conn->prepare("UPDATE extracted_businesses SET ai_faqs = ? WHERE id = ?");
    $stmt->bind_param('si', $jsonFAQs, $businessId);
    $stmt->execute();
    
    return $faqs;
}

/**
 * Generate smart FAQs without API (fallback)
 */
function generateSmartFAQs($name, $category) {
    $faqs = [
        [
            'q' => "What services does {$name} provide?",
            'a' => "{$name} specializes in {$category} services. Please contact them directly for detailed information about their current offerings and packages."
        ],
        [
            'q' => "What are the operating hours?",
            'a' => "For current operating hours, please contact {$name} directly or check their Google Business listing."
        ],
        [
            'q' => "How can I book a service?",
            'a' => "You can contact {$name} via phone, WhatsApp, or email to inquire about their services and make bookings."
        ],
        [
            'q' => "Do you offer online booking?",
            'a' => "Contact {$name} to ask about their booking methods and availability."
        ],
        [
            'q' => "What is the pricing?",
            'a' => "Pricing varies based on services. Contact {$name} directly for a detailed quote and pricing information."
        ]
    ];
    
    // Replace placeholders
    foreach ($faqs as &$faq) {
        $faq['q'] = str_replace('{name}', $name, $faq['q']);
        $faq['q'] = str_replace('{category}', strtolower($category), $faq['q']);
        $faq['a'] = str_replace('{name}', $name, $faq['a']);
        $faq['a'] = str_replace('{category}', strtolower($category), $faq['a']);
    }
    
    return $faqs;
}
?>
