<?php
/**
 * AI Content Generator using Gemini API
 * Programmatically generates SEO content for pages
 */

class AIContentGenerator {
    private $gemini_api_key;
    private $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
    private $cache_dir = '../tmp/seo_cache';

    public function __construct() {
        $this->gemini_api_key = getenv('GEMINI_API_KEY') ?: $_ENV['GEMINI_API_KEY'] ?? '';
        
        // Create cache directory if it doesn't exist
        if (!file_exists($this->cache_dir)) {
            @mkdir($this->cache_dir, 0755, true);
        }
    }

    /**
     * Generate page content using Gemini AI
     */
    public function generatePageContent($page_type, $category, $city) {
        $cache_key = md5("{$page_type}-{$category}-{$city}");
        $cache_file = $this->cache_dir . "/{$cache_key}.json";

        // Check local cache first
        if (file_exists($cache_file)) {
            $cached = json_decode(file_get_contents($cache_file), true);
            if ($cached && isset($cached['timestamp']) && (time() - $cached['timestamp']) < 2592000) { // 30 days
                return $cached['content'];
            }
        }

        // Generate with Gemini AI
        $content = $this->callGeminiAPI($page_type, $category, $city);
        
        if ($content) {
            // Cache the result
            $cache_data = [
                'timestamp' => time(),
                'content' => $content
            ];
            file_put_contents($cache_file, json_encode($cache_data), LOCK_EX);
        }

        return $content;
    }

    /**
     * Call Gemini API to generate content
     */
    private function callGeminiAPI($page_type, $category, $city) {
        if (!$this->gemini_api_key) {
            return $this->getFallbackContent($page_type, $category, $city);
        }

        $prompt = $this->buildPrompt($page_type, $category, $city);

        $request_body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 2000
            ]
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->api_url . '?key=' . urlencode($this->gemini_api_key),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($request_body),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($http_code !== 200 || !$response) {
            return $this->getFallbackContent($page_type, $category, $city);
        }

        $data = json_decode($response, true);
        
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return $this->getFallbackContent($page_type, $category, $city);
        }

        $generated_text = $data['candidates'][0]['content']['parts'][0]['text'];

        return [
            'title' => $this->generateTitle($page_type, $category, $city),
            'description' => $this->generateDescription($page_type, $category, $city),
            'content' => '<div class="ai-generated-content">' . nl2br(htmlspecialchars($generated_text)) . '</div>'
        ];
    }

    /**
     * Build AI prompt for content generation
     */
    private function buildPrompt($page_type, $category, $city) {
        return <<<PROMPT
You are an expert SEO content writer specializing in local business directories in India.

Write compelling, unique SEO-optimized content for a "{$page_type}" page about {$category} services in {$city}.

Include:
1. An engaging introduction paragraph (2-3 sentences)
2. Why choosing {$category} services is important
3. Key benefits of using professional {$category} services
4. How to find the best {$category} provider in {$city}
5. Common questions about {$category} services
6. 3-4 practical tips for choosing {$category} services

Keep content:
- Unique and original
- 300-400 words
- Friendly and professional tone
- Optimized for Google search ranking
- Relevant to {$city} location

Write in HTML-friendly format with clear sections.
PROMPT;
    }

    /**
     * Generate title dynamically
     */
    private function generateTitle($page_type, $category, $city) {
        $titles = [
            'best' => "Best {$category} in {$city} | Verified & Rated",
            'top' => "Top {$category} Services in {$city} | Find Trusted Providers",
            'affordable' => "Affordable {$category} in {$city} | Budget-Friendly Options",
            'near' => "{$category} Near Me in {$city} | Connect Instantly"
        ];

        return $titles[$page_type] ?? "Best {$category} in {$city} | ConnectWith9";
    }

    /**
     * Generate description dynamically
     */
    private function generateDescription($page_type, $category, $city) {
        $descriptions = [
            'best' => "Discover the best {$category} services in {$city}. Compare verified providers, ratings, and reviews. Connect instantly with trusted professionals.",
            'top' => "Find top-rated {$category} services in {$city}. Browse verified businesses, check reviews, and contact instantly.",
            'affordable' => "Looking for affordable {$category} services in {$city}? Find budget-friendly options with verified ratings and reviews.",
            'near' => "Find {$category} services near you in {$city}. Verified businesses, ratings, and instant contact options."
        ];

        return $descriptions[$page_type] ?? "Find the best {$category} in {$city} on ConnectWith9 - trusted local business directory.";
    }

    /**
     * Fallback content if API is unavailable
     */
    private function getFallbackContent($page_type, $category, $city) {
        $content = <<<HTML
<h2>About {$category} in {$city}</h2>
<p>Welcome to our comprehensive guide for finding quality {$category} services in {$city}. Whether you're looking for professional, affordable, or highly-rated options, we help you discover the best providers in your area.</p>

<h2>Why Choose Professional {$category} Services?</h2>
<p>Professional {$category} services offer expertise, quality, and peace of mind. Our directory features verified businesses with genuine customer reviews and ratings to help you make the right choice.</p>

<h2>How to Find the Best {$category} Provider</h2>
<p>When searching for {$category} services in {$city}, consider these factors:</p>
<ul>
<li>Check verified ratings and customer reviews</li>
<li>Look for certified professionals with experience</li>
<li>Compare pricing and service offerings</li>
<li>Read recent customer feedback</li>
<li>Verify contact information and location</li>
</ul>

<h2>Connect with {$category} Professionals Today</h2>
<p>Browse our list of verified {$category} providers in {$city}. Connect instantly via phone or WhatsApp to discuss your needs and get quotes.</p>
HTML;

        return [
            'title' => $this->generateTitle($page_type, $category, $city),
            'description' => $this->generateDescription($page_type, $category, $city),
            'content' => $content
        ];
    }
}
?>
