<?php
/**
 * Internal Linking System
 * Creates and manages internal links between related pages for SEO
 */

class InternalLinking {
    private $conn;

    public function __construct(&$conn) {
        $this->conn = $conn;
    }

    /**
     * Get related pages for a given page
     */
    public function getRelatedPages($current_slug, $limit = 5) {
        try {
            $result = $this->conn->query("
                SELECT to_page, link_text 
                FROM seo_links 
                WHERE from_page = '$current_slug' 
                LIMIT $limit
            ");
            
            $links = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $links[] = $row;
                }
            }
            return $links;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Generate internal links between category-city pages
     */
    public function generateLinks($from_category, $from_city) {
        try {
            // Links to same category in nearby cities
            $similar_cities_result = $this->conn->query("
                SELECT DISTINCT search_location 
                FROM extracted_businesses 
                WHERE LOWER(REPLACE(types, ' ', '')) = LOWER(REPLACE('$from_category', ' ', ''))
                AND search_location != '$from_city'
                ORDER BY search_location 
                LIMIT 5
            ");

            if ($similar_cities_result) {
                while ($row = $similar_cities_result->fetch_assoc()) {
                    $from_slug = sanitize_slug("category-{$from_category}-{$from_city}");
                    $to_slug = sanitize_slug("category-{$from_category}-{$row['search_location']}");
                    $link_text = "See {$from_category} in {$row['search_location']}";
                    
                    $this->addLink($from_slug, $to_slug, $link_text, 'related_city');
                }
            }

            // Links to related categories in same city
            $similar_categories_result = $this->conn->query("
                SELECT DISTINCT types 
                FROM extracted_businesses 
                WHERE search_location = '$from_city'
                AND types != '$from_category'
                ORDER BY RAND()
                LIMIT 5
            ");

            if ($similar_categories_result) {
                while ($row = $similar_categories_result->fetch_assoc()) {
                    $from_slug = sanitize_slug("category-{$from_category}-{$from_city}");
                    $to_slug = sanitize_slug("category-{$row['types']}-{$from_city}");
                    $link_text = "See {$row['types']} in {$from_city}";
                    
                    $this->addLink($from_slug, $to_slug, $link_text, 'related_category');
                }
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Add a single internal link
     */
    private function addLink($from_page, $to_page, $link_text, $link_type) {
        try {
            // Check if link already exists
            $existing = $this->conn->query("
                SELECT id FROM seo_links 
                WHERE from_page = '$from_page' AND to_page = '$to_page'
            ");

            if ($existing && $existing->num_rows > 0) {
                return false; // Already exists
            }

            $stmt = $this->conn->prepare(
                "INSERT INTO seo_links (from_page, to_page, link_text, link_type) 
                 VALUES (?, ?, ?, ?)"
            );

            if ($stmt) {
                $stmt->bind_param('ssss', $from_page, $to_page, $link_text, $link_type);
                return $stmt->execute();
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get breadcrumb links for a page
     */
    public function getBreadcrumbs($current_slug) {
        $breadcrumbs = [
            ['url' => '/', 'text' => 'Home'],
            ['url' => '/pages/search-with-filters.php', 'text' => 'Browse All']
        ];

        // Add category and city specific breadcrumbs
        // This would be implemented based on URL parsing

        return $breadcrumbs;
    }
}
?>
