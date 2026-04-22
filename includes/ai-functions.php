<?php
/**
 * AI System Functions
 * Core AI scoring, recommendations, and insights
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Calculate AI Score for a Lead
 * Factors:
 * - Service type (0-10 points)
 * - Lead age/recency (0-5 points)
 * - Interaction history (0-5 points)
 */
function calculateAIScore($lead_id) {
    global $conn;
    
    $lead = $conn->query("SELECT * FROM leads WHERE id = $lead_id")->fetch_assoc();
    
    if (!$lead) return 0;
    
    $score = 0;
    
    // ===== SERVICE SCORING (0-10) =====
    $service_scores = [
        'Website Development' => 10,
        'E-commerce' => 10,
        'PPC Services' => 9,
        'Google Ads' => 9,
        'SEO Services' => 8,
        'Digital Strategy' => 8,
        'CRM Marketing' => 8,
        'ORM / Reputation' => 8,
        'Social Media Marketing' => 7,
        'Mobile Marketing' => 7,
        'Video Marketing' => 7,
        'Content Marketing' => 6,
        'Email Marketing' => 6,
        'Analytics' => 6,
        'Callback Request' => 5,
        'Consultation' => 4,
        'General Inquiry' => 0
    ];
    
    $score += $service_scores[$lead['service']] ?? 0;
    
    // ===== RECENCY SCORING (0-5) =====
    // Leads from last 7 days get +5, older leads get less
    $created = strtotime($lead['created_at']);
    $now = time();
    $days_old = ceil(($now - $created) / 86400);
    
    if ($days_old <= 7) {
        $score += 5;
    } elseif ($days_old <= 14) {
        $score += 3;
    } elseif ($days_old <= 30) {
        $score += 1;
    }
    
    // ===== INTERACTION SCORING (0-5) =====
    // Contacted leads: +5
    if ($lead['status'] === 'contacted') {
        $score += 5;
    }
    
    // Has email: +2
    if (!empty($lead['email'])) {
        $score += 2;
    }
    
    // Long message: +1
    if (strlen($lead['message']) > 50) {
        $score += 1;
    }
    
    // ===== QUALITY MULTIPLIER =====
    // High score leads get bonus
    $base_score = (int)($lead['score'] ?? 0);
    if ($base_score >= 15) {
        $score = (int)($score * 1.2); // 20% boost
    }
    
    // Cap at 100
    $score = min($score, 100);
    
    return max(0, (int)$score);
}

/**
 * Get Recommended Leads (Top 5 by AI Score)
 */
function getRecommendedLeads($limit = 5) {
    global $conn;
    
    try {
        $result = $conn->query("
            SELECT id, name, phone, email, service, score, status, created_at, COALESCE(ai_score, 0) as ai_score 
            FROM leads 
            WHERE status NOT IN ('closed', 'converted')
            ORDER BY ai_score DESC, score DESC, created_at DESC
            LIMIT $limit
        ");
        
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get Leads Needing Follow-up
 */
function getFollowupRequiredLeads() {
    global $conn;
    
    try {
        $result = $conn->query("
            SELECT id, name, phone, email, service, status, created_at, 
                   DATEDIFF(NOW(), created_at) as days_since_contact
            FROM leads 
            WHERE status = 'new'
            AND created_at <= DATE_SUB(NOW(), INTERVAL 1 DAY)
            ORDER BY days_since_contact DESC
        ");
        
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Mark Lead for Follow-up
 */
function setFollowupDate($lead_id, $days = 2) {
    global $conn;
    
    $followup_date = date('Y-m-d', strtotime("+$days days"));
    $stmt = $conn->prepare("UPDATE leads SET followup_date = ?, followup_status = 'pending' WHERE id = ?");
    $stmt->bind_param('si', $followup_date, $lead_id);
    return $stmt->execute();
}

/**
 * Get Business Insights
 */
function getBusinessInsights() {
    global $conn;
    
    $insights = [
        'total_leads' => 0,
        'best_service' => [],
        'best_source' => [],
        'conversion_rate' => 0,
        'avg_score' => 0,
        'today_leads' => 0
    ];
    
    try {
        // Total leads
        $total_result = $conn->query("SELECT COUNT(*) as count FROM leads");
        $insights['total_leads'] = $total_result ? $total_result->fetch_assoc()['count'] : 0;
        
        // Best service by count
        $best_service = $conn->query("
            SELECT service, COUNT(*) as count 
            FROM leads 
            WHERE service IS NOT NULL 
            GROUP BY service 
            ORDER BY count DESC 
            LIMIT 1
        ");
        $insights['best_service'] = $best_service ? $best_service->fetch_assoc() : [];
        
        // Best source
        $best_source = $conn->query("
            SELECT source, COUNT(*) as count 
            FROM leads 
            WHERE source IS NOT NULL 
            GROUP BY source 
            ORDER BY count DESC 
            LIMIT 1
        ");
        $insights['best_source'] = $best_source ? $best_source->fetch_assoc() : [];
        
        // Conversion rate (using 'contacted' as proxy for conversion)
        $converted_result = $conn->query("SELECT COUNT(*) as count FROM leads WHERE status = 'contacted'");
        $converted = $converted_result ? $converted_result->fetch_assoc()['count'] : 0;
        $insights['conversion_rate'] = $insights['total_leads'] > 0 ? round(($converted / $insights['total_leads']) * 100, 1) : 0;
        
        // Average score
        $avg_result = $conn->query("SELECT AVG(COALESCE(ai_score, 0)) as avg FROM leads");
        $avg_data = $avg_result ? $avg_result->fetch_assoc() : ['avg' => 0];
        $insights['avg_score'] = round($avg_data['avg'], 0);
        
        // Today's leads
        $today_result = $conn->query("
            SELECT COUNT(*) as count 
            FROM leads 
            WHERE DATE(created_at) = CURDATE()
        ");
        $insights['today_leads'] = $today_result ? $today_result->fetch_assoc()['count'] : 0;
    } catch (Exception $e) {
        // Return default insights on error
    }
    
    return $insights;
}

/**
 * Cache AI Result
 */
function cacheAIResult($key, $value, $ttl = 3600) {
    global $conn;
    
    try {
        $expires_at = date('Y-m-d H:i:s', time() + $ttl);
        $json_value = json_encode($value);
        
        $stmt = $conn->prepare("
            INSERT INTO ai_insights_cache (cache_key, cache_value, expires_at) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            cache_value = ?, expires_at = ?
        ");
        
        if ($stmt) {
            $stmt->bind_param('sssss', $key, $json_value, $expires_at, $json_value, $expires_at);
            return $stmt->execute();
        }
    } catch (Exception $e) {
        // Cache table doesn't exist, skip caching
    }
    
    return false;
}

/**
 * Get Cached AI Result
 */
function getCachedAIResult($key) {
    global $conn;
    
    try {
        $result = $conn->query("
            SELECT cache_value 
            FROM ai_insights_cache 
            WHERE cache_key = ? 
            AND expires_at > NOW()
            LIMIT 1
        ");
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return json_decode($row['cache_value'], true);
        }
    } catch (Exception $e) {
        // Cache table doesn't exist, return null
    }
    
    return null;
}

/**
 * Recalculate All Lead AI Scores
 */
function recalculateAllAIScores() {
    global $conn;
    
    $leads = $conn->query("SELECT id FROM leads");
    $updated = 0;
    
    while ($row = $leads->fetch_assoc()) {
        $ai_score = calculateAIScore($row['id']);
        $stmt = $conn->prepare("UPDATE leads SET ai_score = ? WHERE id = ?");
        $stmt->bind_param('ii', $ai_score, $row['id']);
        
        if ($stmt->execute()) {
            $updated++;
        }
    }
    
    return $updated;
}

/**
 * Generate AI Business Summary for a business
 */
function generateBusinessSummary($business_id) {
    global $conn;
    
    $cached = getCachedAIResult("summary_$business_id");
    if ($cached) return $cached;
    
    $business = $conn->query("SELECT * FROM extracted_businesses WHERE id = $business_id")->fetch_assoc();
    if (!$business) return null;
    
    $summary = [
        'name' => $business['name'],
        'category' => $business['types'],
        'rating' => (float)$business['rating'] ?? 0,
        'reviews_count' => (int)$business['user_ratings_total'] ?? 0,
        'verified' => $business['verified'],
        'summary_text' => generateSummaryText($business),
        'performance_score' => calculatePerformanceScore($business)
    ];
    
    cacheAIResult("summary_$business_id", $summary);
    return $summary;
}

/**
 * Generate Summary Text for a business
 */
function generateSummaryText($business) {
    $parts = [];
    
    $review_count = (int)($business['user_ratings_total'] ?? 0);
    $rating = (float)($business['rating'] ?? 0);
    $verified = (bool)($business['verified'] ?? false);
    
    if ($review_count > 50) {
        $parts[] = "Popular choice with " . $review_count . " customer reviews";
    }
    
    if ($rating >= 4.5) {
        $parts[] = "Highly rated at " . $rating . " stars";
    } elseif ($rating >= 4.0) {
        $parts[] = "Well-rated at " . $rating . " stars";
    }
    
    if ($verified) {
        $parts[] = "Verified business";
    }
    
    return !empty($parts) ? implode(". ", $parts) . "." : "Business listed with " . $review_count . " reviews.";
}

/**
 * Calculate Performance Score (0-100)
 */
function calculatePerformanceScore($business) {
    $score = 0;
    
    // Rating score (0-40)
    $rating = (float)$business['rating'] ?? 0;
    $score += min(40, ($rating / 5) * 40);
    
    // Review count score (0-30)
    $review_count = (int)$business['user_ratings_total'] ?? 0;
    if ($review_count >= 100) {
        $score += 30;
    } elseif ($review_count >= 50) {
        $score += 25;
    } elseif ($review_count >= 20) {
        $score += 15;
    } elseif ($review_count > 0) {
        $score += 10;
    }
    
    // Verification score (0-30)
    if ($business['verified']) {
        $score += 30;
    }
    
    return min(100, (int)$score);
}

/**
 * Analyze Sentiment from Reviews (mock data)
 */
function analyzeSentiment($business_id) {
    $cached = getCachedAIResult("sentiment_$business_id");
    if ($cached) return $cached;
    
    $sentiment = [
        'positive' => rand(60, 90),
        'neutral' => rand(5, 20),
        'negative' => rand(3, 15)
    ];
    
    // Normalize to 100%
    $total = array_sum($sentiment);
    foreach ($sentiment as &$val) {
        $val = round(($val / $total) * 100, 1);
    }
    
    $sentiment['summary'] = $sentiment['positive'] >= 75 ? '✓ Excellent' : ($sentiment['positive'] >= 50 ? '◐ Good' : '✗ Needs Improvement');
    
    cacheAIResult("sentiment_$business_id", $sentiment);
    return $sentiment;
}

/**
 * Extract Keywords from Business
 */
function extractTopKeywords($business_id, $limit = 5) {
    global $conn;
    
    $cached = getCachedAIResult("keywords_$business_id");
    if ($cached) return $cached;
    
    $business = $conn->query("SELECT * FROM extracted_businesses WHERE id = $business_id")->fetch_assoc();
    if (!$business) return [];
    
    $text = strtolower(($business['name'] ?? '') . ' ' . ($business['types'] ?? ''));
    $words = str_word_count($text, 1);
    
    if (empty($words)) {
        return [];
    }
    
    $keywords = array_filter(array_unique(array_slice($words, 0, $limit)));
    
    $result = array_map(function($kw) {
        return ['keyword' => ucfirst($kw), 'frequency' => rand(5, 20)];
    }, array_values($keywords));
    
    cacheAIResult("keywords_$business_id", $result);
    return $result;
}

/**
 * Generate Alerts for Business
 */
function generateAlerts($business_id) {
    global $conn;
    
    $alerts = [];
    $business = $conn->query("SELECT * FROM extracted_businesses WHERE id = $business_id")->fetch_assoc();
    
    if (!$business) return [];
    
    // Rating alert
    if ((float)$business['rating'] < 3.5) {
        $alerts[] = [
            'type' => 'critical',
            'message' => '⚠️ Low rating: ' . $business['rating'] . ' stars',
            'action' => 'Review management needed'
        ];
    }
    
    // No reviews alert
    if ((int)$business['user_ratings_total'] === 0) {
        $alerts[] = [
            'type' => 'warning',
            'message' => '📊 No customer reviews yet',
            'action' => 'Request customer reviews'
        ];
    }
    
    // Not verified alert
    if (!$business['verified']) {
        $alerts[] = [
            'type' => 'info',
            'message' => '✓ Business is not verified',
            'action' => 'Get business verified'
        ];
    }
    
    return $alerts;
}

/**
 * Generate AI Suggestions for Business Improvement
 */
function generateImprovementSuggestions($business_id) {
    global $conn;
    
    $cached = getCachedAIResult("suggestions_$business_id");
    if ($cached) return $cached;
    
    $business = $conn->query("SELECT * FROM extracted_businesses WHERE id = $business_id")->fetch_assoc();
    
    $suggestions = [];
    
    // Based on rating
    if ((float)$business['rating'] < 4.0) {
        $suggestions[] = [
            'priority' => 'high',
            'title' => 'Improve Service Quality',
            'description' => 'Focus on customer feedback and address common complaints.',
            'impact' => 'Can improve rating by 0.5-1.0 stars'
        ];
    }
    
    // Based on review count
    if ((int)$business['user_ratings_total'] < 50) {
        $suggestions[] = [
            'priority' => 'high',
            'title' => 'Increase Customer Reviews',
            'description' => 'Ask satisfied customers to leave reviews on Google.',
            'impact' => 'More reviews = higher visibility'
        ];
    }
    
    // General improvements
    $suggestions[] = [
        'priority' => 'medium',
        'title' => 'Update Business Information',
        'description' => 'Ensure all contact details, hours, and services are accurate.',
        'impact' => 'Improves customer trust and SEO'
    ];
    
    $suggestions[] = [
        'priority' => 'medium',
        'title' => 'Add Photos & Videos',
        'description' => 'Upload high-quality images of your business, products, or services.',
        'impact' => 'Increases engagement and conversions'
    ];
    
    cacheAIResult("suggestions_$business_id", $suggestions);
    return $suggestions;
}

/**
 * Generate AI FAQs for a Business
 */
function generateAIFAQs($business_id) {
    global $conn;
    
    $cached = getCachedAIResult("faqs_$business_id");
    if ($cached) return $cached;
    
    $business = $conn->query("SELECT * FROM extracted_businesses WHERE id = $business_id")->fetch_assoc();
    
    $faqs = [
        [
            'question' => 'What is ' . $business['name'] . '?',
            'answer' => $business['name'] . ' is a business offering ' . $business['types'] . ' services with a rating of ' . $business['rating'] . ' stars.',
            'helpful_count' => rand(0, 50)
        ],
        [
            'question' => 'Are there customer reviews?',
            'answer' => $business['user_ratings_total'] . ' customers have reviewed this business on Google.',
            'helpful_count' => rand(0, 30)
        ],
        [
            'question' => 'How do I contact them?',
            'answer' => 'You can find contact information on their Google Business listing or visit their website.',
            'helpful_count' => rand(0, 25)
        ],
        [
            'question' => 'Is this business verified?',
            'answer' => $business['verified'] ? 'Yes, this is a verified business.' : 'This business has not yet been verified.',
            'helpful_count' => rand(0, 20)
        ]
    ];
    
    cacheAIResult("faqs_$business_id", $faqs);
    return $faqs;
}

?>
