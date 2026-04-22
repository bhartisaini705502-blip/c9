<?php
/**
 * Blog Automation System
 * Auto-generates blog articles targeting long-tail keywords
 */

require_once 'ai-content-generator.php';

class BlogAutomation {
    private $conn;
    private $ai_gen;

    public function __construct(&$conn) {
        $this->conn = $conn;
        $this->ai_gen = new AIContentGenerator();
    }

    /**
     * Generate blog article for a category and city combination
     */
    public function generateBlogArticle($category, $city) {
        $slug = sanitize_slug("how-to-choose-best-{$category}-in-{$city}");
        
        // Check if already exists
        $existing = getRow(
            "SELECT id FROM seo_blogs WHERE slug = ?",
            [$slug],
            's'
        );
        
        if ($existing) {
            return ['success' => false, 'message' => 'Blog already exists'];
        }

        // Generate title
        $title = "How to Choose the Best {$category} Services in {$city}";

        // Generate content using AI
        $content = $this->generateBlogContent($category, $city);

        // Extract excerpt (first 150 characters)
        $excerpt = substr(strip_tags($content), 0, 150) . '...';

        // Create blog record
        $stmt = $this->conn->prepare(
            "INSERT INTO seo_blogs (slug, title, content, excerpt, category, seo_keywords, published) 
             VALUES (?, ?, ?, ?, ?, ?, 1)"
        );

        if ($stmt) {
            $keywords = "{$category}, {$city}, services, how to choose";
            $stmt->bind_param(
                'ssssss',
                $slug,
                $title,
                $content,
                $excerpt,
                $category,
                $keywords
            );

            if ($stmt->execute()) {
                $stmt->close();
                return ['success' => true, 'message' => 'Blog created successfully', 'slug' => $slug];
            }
            $stmt->close();
        }

        return ['success' => false, 'message' => 'Failed to create blog'];
    }

    /**
     * Generate blog content
     */
    private function generateBlogContent($category, $city) {
        $prompt = <<<PROMPT
Write a comprehensive blog article titled "How to Choose the Best {$category} Services in {$city}".

The article should:
1. Start with a compelling introduction
2. Include 5-7 practical tips for choosing {$category} services
3. Address common mistakes to avoid
4. Provide actionable advice
5. End with a strong CTA to use our directory

Format in HTML with proper headings and paragraphs.
Length: 800-1200 words
Tone: Professional, helpful, and engaging
PROMPT;

        // For now, return fallback content (can be enhanced with API)
        $content = <<<HTML
<h1>How to Choose the Best {$category} Services in {$city}</h1>

<p>Finding the right {$category} service provider in {$city} can be challenging. With so many options available, it's important to know what to look for. In this guide, we'll walk you through the key factors to consider when choosing {$category} services.</p>

<h2>1. Check Ratings and Reviews</h2>
<p>Always look at verified customer reviews and ratings. Real feedback from previous customers gives you insight into the quality of service and customer satisfaction.</p>

<h2>2. Verify Credentials and Experience</h2>
<p>Ensure the provider has proper certifications, licenses, and relevant experience in {$category} services. Don't hesitate to ask about their qualifications.</p>

<h2>3. Compare Pricing</h2>
<p>Get quotes from multiple {$category} providers in {$city}. Compare pricing, but remember that the cheapest option isn't always the best. Look for value for money.</p>

<h2>4. Check Their Portfolio</h2>
<p>Review previous work or case studies. This helps you understand the quality of service and whether they can meet your specific needs.</p>

<h2>5. Read Recent Reviews</h2>
<p>Focus on recent customer feedback. Current reviews are more relevant than older ones as they reflect the current service quality.</p>

<h2>6. Verify Contact Information</h2>
<p>Make sure the provider has a legitimate business location, phone number, and address in {$city}. Verify this information before hiring.</p>

<h2>7. Ask Questions</h2>
<p>Don't be shy about asking questions about their services, process, timeline, and pricing. Good providers are happy to clarify.</p>

<h2>Common Mistakes to Avoid</h2>
<ul>
<li>Choosing based solely on price</li>
<li>Not reading reviews thoroughly</li>
<li>Hiring without verifying credentials</li>
<li>Ignoring red flags in communication</li>
</ul>

<h2>Find the Best {$category} Services in {$city}</h2>
<p>Use our directory to find verified {$category} providers in {$city}. Browse ratings, reviews, and contact information to make an informed choice.</p>
HTML;

        return $content;
    }

    /**
     * Generate blog articles in bulk
     */
    public function generateBlogsBulk($limit = 20) {
        try {
            $categories_result = $this->conn->query("SELECT DISTINCT types FROM extracted_businesses LIMIT {$limit}");
            $cities_result = $this->conn->query("SELECT DISTINCT search_location FROM extracted_businesses LIMIT {$limit}");
            
            $generated = 0;
            $skipped = 0;

            if ($categories_result && $cities_result) {
                while ($cat = $categories_result->fetch_assoc()) {
                    $cities_result->data_seek(0);
                    while ($city = $cities_result->fetch_assoc()) {
                        $result = $this->generateBlogArticle($cat['types'], $city['search_location']);
                        if ($result['success']) {
                            $generated++;
                        } else {
                            $skipped++;
                        }
                    }
                }
            }

            return ['generated' => $generated, 'skipped' => $skipped];
        } catch (Exception $e) {
            return ['generated' => 0, 'skipped' => 0, 'error' => $e->getMessage()];
        }
    }
}
?>
