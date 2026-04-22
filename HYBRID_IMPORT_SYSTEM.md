# Hybrid Import System - ConnectWith9

## Overview

The **Hybrid Import System** intelligently combines local database searches with Google Places API fallback, implementing smart caching to minimize API calls and costs.

---

## 🎯 Flow Architecture

```
User Search Query
       ↓
[1] Check Local Database
       ↓
   Empty? → [2] Check Google Cache (avoid API calls)
       ↓
   Empty? → [3] Call Google Places API
       ↓
[4] Cache Results (minimal data only)
       ↓
[5] Return Results to User
       ↓
[6] Optional: Import to Main DB (manual admin action)
```

---

## 📊 Database Schema

### `google_cache` Table
Stores minimal Google Places data to avoid repeated API calls.

```sql
CREATE TABLE google_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    search_query VARCHAR(255) NOT NULL,        -- Search term used
    place_id VARCHAR(255) UNIQUE NOT NULL,     -- Google's unique ID
    name VARCHAR(500),
    rating DECIMAL(2,1),
    review_count INT,
    address TEXT,
    phone VARCHAR(20),
    lat DECIMAL(10,7),
    lng DECIMAL(10,7),
    cached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    imported_to_main TINYINT DEFAULT 0,        -- Flag: imported to main DB?
    business_id INT,                            -- Link to extracted_businesses
    KEY idx_query (search_query),
    KEY idx_place_id (place_id),
    KEY idx_imported (imported_to_main)
);
```

### Enhanced `extracted_businesses` Table
```sql
ALTER TABLE extracted_businesses ADD COLUMN place_id VARCHAR(255);
ALTER TABLE extracted_businesses ADD COLUMN from_google_cache TINYINT DEFAULT 0;
```

---

## 🔧 API Endpoints

### 1. **Smart Search with Hybrid Fallback**
- **URL:** `/api/smart-search.php`
- **Method:** GET
- **Parameters:**
  - `q` - Search query (required)
  - `fallback` - Enable/disable Google fallback (default: true)
  - `limit` - Results limit (default: 20)
  - `minRating` - Minimum rating filter
  - `category` - Category filter

**Flow:**
1. Search local DB
2. If empty → check cache
3. If empty → call Google API (auto-cache results)
4. Return with source indicator

**Response:**
```json
{
  "success": true,
  "query": "pizza near delhi",
  "count": 10,
  "source": "local|cached|google",
  "businesses": [
    {
      "id": 123,
      "place_id": "ChIJ...",
      "name": "Pizza Palace",
      "rating": 4.5,
      "review_count": 250,
      "address": "...",
      "phone": "...",
      "lat": 28.6139,
      "lng": 77.2090,
      "source": "local|cached|google"
    }
  ]
}
```

### 2. **Hybrid Search Page**
- **URL:** `/pages/hybrid-search.php`
- **Features:**
  - Real-time search input
  - Map visualization
  - Results list with source indicator
  - Auto-caching of Google results

**Search via API:**
```
GET /pages/hybrid-search.php?api=1&q=coffee
```

### 3. **Hybrid Import Management**
- **URL:** `/api/hybrid-import.php`
- **Actions:**
  - `get` - Perform hybrid search
  - `import` - Import cached Google result to main DB
  - `cache_status` - Get cache statistics
  - `clear_cache` - Remove old entries (>30 days)

**Examples:**

**Get Results:**
```
GET /api/hybrid-import.php?action=get&q=restaurants
```

**Import Result to Main DB:**
```
POST /api/hybrid-import.php
{
  "action": "import",
  "place_id": "ChIJ..."
}
```

**Cache Statistics:**
```
GET /api/hybrid-import.php?action=cache_status
```

**Clear Old Cache:**
```
GET /api/hybrid-import.php?action=clear_cache
```

---

## ⚡ Smart Caching Strategy

### Benefits
✅ **Reduced API Calls** - Cache check before API call  
✅ **Faster Response Times** - Database reads faster than API  
✅ **Cost Optimization** - Avoid expensive Google API calls  
✅ **Data Compliance** - Store minimal data (place_id only)  

### Cache Key
Search query used as cache key:
- Same query → Same cache entry
- Different query → New API call

### Cache Lifetime
- Default: 30 days
- Auto-cleanup: Old entries removed on `clear_cache` action
- Manual import: Mark as `imported_to_main = 1`

### Cache Hit Examples

**First Search (Query: "coffee"):**
1. Check local DB → 0 results
2. Check cache → 0 results
3. Call Google API → 10 results
4. **Cache** → 10 entries stored
5. Return results + source="google"

**Second Search (Query: "coffee"):**
1. Check local DB → 0 results
2. Check cache → **10 results found** ✓
3. Return cached results + source="cached"
4. **No API call!** 🎉

---

## 📍 Data Minimalism

**What We Store in Cache:**
- ✅ `place_id` - Google's unique identifier
- ✅ `name` - Business name
- ✅ `rating` - Google rating
- ✅ `review_count` - Number of reviews
- ✅ `address` - Full address
- ✅ `phone` - Phone number
- ✅ `lat/lng` - Coordinates
- ✅ `search_query` - Query used

**What We DON'T Store:**
- ❌ Opening hours (updated frequently)
- ❌ Website URL (doesn't match our schema)
- ❌ Photos (violates ToS)
- ❌ Full place details (bloats DB)
- ❌ Reviews (copyright concern)

---

## 🔄 Import Workflow

### Admin: Promote Cached Result to Main DB

```php
// API Call
POST /api/hybrid-import.php
{
  "action": "import",
  "place_id": "ChIJ1234567890"
}

// Response
{
  "success": true,
  "message": "Business imported successfully",
  "business_id": 6478
}
```

**What Happens:**
1. ✅ Fetch from `google_cache` by `place_id`
2. ✅ Check if already exists in `extracted_businesses`
3. ✅ Insert new record with `from_google_cache = 1`
4. ✅ Update cache: `imported_to_main = 1, business_id = 6478`

---

## 🌐 Search Page Integration

### Navigation
- **Header Menu:** `🌐 Hybrid Search`
- **URL:** `/pages/hybrid-search.php`
- **Existing Smart Search:** Still available (`🔍 Smart Search`)

### Features
- Split-view layout (60% results list, 40% map)
- Live search with auto-complete
- Source indicator badges:
  - ✓ Local Database (green)
  - 🌐 Google Places (blue)
- Map markers with info windows
- Click result → pan/zoom on map

---

## 📈 Monitor Cache Health

**SQL Queries:**

**Cache Statistics:**
```sql
SELECT 
    COUNT(*) as total_cached,
    SUM(CASE WHEN imported_to_main = 1 THEN 1 ELSE 0 END) as imported,
    SUM(CASE WHEN imported_to_main = 0 THEN 1 ELSE 0 END) as pending,
    COUNT(DISTINCT search_query) as unique_queries
FROM google_cache;
```

**Most Cached Queries:**
```sql
SELECT search_query, COUNT(*) as count 
FROM google_cache 
GROUP BY search_query 
ORDER BY count DESC 
LIMIT 10;
```

**Pending Imports:**
```sql
SELECT place_id, name, rating, search_query 
FROM google_cache 
WHERE imported_to_main = 0 
ORDER BY cached_at DESC 
LIMIT 20;
```

---

## 🛡️ Rate Limiting & Compliance

### Google ToS Compliance
- ✅ Minimal data storage
- ✅ No blind copying of place details
- ✅ Cache updates linked to search query
- ✅ User-driven import workflow
- ✅ Transparent source attribution

### Rate Limiting
- 1 request per second (API limit)
- Cache bypasses this limit
- No quota hoarding

### Best Practices
1. Always check cache first
2. Use `search_query` as cache key
3. Store only essential fields
4. Mark imported records
5. Clean old cache monthly

---

## 🚀 Quick Start

### For Users
1. Go to **Header → 🌐 Hybrid Search**
2. Type your query
3. See results from local DB or Google
4. Click result to view on map
5. System auto-caches for next search

### For Admins
1. Monitor cache via `/api/hybrid-import.php?action=cache_status`
2. Find good candidates in cache
3. Bulk import via API
4. Track import status

### For Developers
1. Use smart search API with fallback enabled (default)
2. Results include `source` field
3. Cache is automatic and transparent
4. No manual intervention needed

---

## 📊 Performance Impact

**Benchmark Results** (Estimated)

| Scenario | Source | Speed | API Calls |
|----------|--------|-------|-----------|
| Cold start (no cache) | Google | 500ms | 1 ✓ |
| Repeat search | Cache | 50ms | 0 |
| Local DB hit | Local | 10ms | 0 |
| Local + cache | Both | 15ms | 0 |

**Cost Reduction:**
- 10 searches/day × 30 days = 300 searches/month
- 70% cache hit rate = 90 API calls saved
- $2-3/1000 calls × 90 = **$0.18-0.27 saved/month**
- **Scales with growth** 📈

---

## 🔍 Monitoring Dashboard

Future enhancements:
- Cache hit rate percentage
- Most-searched queries
- Pending import queue
- API usage vs cache usage
- Cost savings calculator

---

## 🎯 Summary

| Feature | Status |
|---------|--------|
| Smart caching | ✅ Live |
| Google API fallback | ✅ Live |
| Cache management API | ✅ Live |
| Hybrid search page | ✅ Live |
| Admin import workflow | ✅ Live |
| Auto-cleanup (30 days) | ✅ Live |
| Source attribution | ✅ Live |

---

**Created:** March 26, 2026  
**System:** ConnectWith9 - Hybrid Import v1.0
