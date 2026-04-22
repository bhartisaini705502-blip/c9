<?php
/**
 * AI Integration - Gemini API
 */

class GeminiAI {
    private $apiKey;
    private $apiUrl = 'https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent';
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    /**
     * Generate SEO-friendly business description
     */
    public function generateBusinessDescription($name, $category, $city) {
        $prompt = "Write a concise, SEO-friendly business description (2-3 sentences) for a {$category} business called '{$name}' in {$city}. Focus on unique value propositions and customer benefits.";
        return $this->generateContent($prompt);
    }
    
    /**
     * Generate SEO content for category page
     */
    public function generateCategoryContent($category, $city) {
        $prompt = "Write a professional, SEO-optimized paragraph (100-150 words) about {$category} services in {$city}. Include benefits of finding quality {$category}, what to look for, and why location matters. Make it conversational.";
        return $this->generateContent($prompt);
    }
    
    /**
     * Generate review summary
     */
    public function generateReviewSummary($name, $reviews) {
        $prompt = "Based on these reviews for {$name}: " . implode("; ", array_slice($reviews, 0, 5)) . ". Generate a JSON with: {\"pros\": [3 main pros], \"cons\": [2 main cons], \"summary\": \"one-sentence summary\"}";
        return $this->generateContent($prompt);
    }
    
    /**
     * Generate business tags
     */
    public function generateBusinessTags($name, $category, $description) {
        $prompt = "Generate 5 SEO-friendly tags for this {$category} business: {$name}. Description: {$description}. Return as comma-separated list. Tags like: 'affordable', 'premium', 'family-friendly', 'luxury', etc.";
        return $this->generateContent($prompt);
    }
    
    /**
     * Make API call to Gemini
     */
    public function generateContent($prompt) {
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 1024,
            ]
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . '?key=' . $this->apiKey,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $data['candidates'][0]['content']['parts'][0]['text'];
            }
        }
        
        return null; // Return null if API fails
    }
}

// Get API key from environment
function getGeminiAI() {
    $apiKey = getenv('GEMINI_API_KEY') ?: $_ENV['GEMINI_API_KEY'] ?? null;
    
    if (empty($apiKey)) {
        return null; // No API key configured
    }
    
    return new GeminiAI($apiKey);
}

function isGeminiConfigured() {
    return !empty(getenv('GEMINI_API_KEY') ?: $_ENV['GEMINI_API_KEY'] ?? null);
}
?>
