# ConnectWith9 - AI-Powered System
**Complete AI Integration Guide**

---

## 📋 System Overview

ConnectWith9 now includes **enterprise-grade AI features** that intelligently score leads, provide recommendations, and automate follow-ups.

---

## 🤖 1. AI LEAD SCORING SYSTEM

### How It Works
Each lead receives an **AI Score (0-100)** based on:

#### Service Scoring (0-10 points)
```
Website Development   → 10
E-commerce           → 10
PPC Services         → 9
Google Ads           → 9
SEO Services         → 8
Digital Strategy     → 8
CRM Marketing        → 8
ORM / Reputation     → 8
Social Media Mktg    → 7
Mobile Marketing     → 7
Video Marketing      → 7
Content Marketing    → 6
Email Marketing      → 6
Analytics            → 6
Callback Request     → 5
Consultation         → 4
General Inquiry      → 0
```

#### Recency Scoring (0-5 points)
- **Last 7 days** → +5 points (fresh leads)
- **7-14 days** → +3 points
- **14-30 days** → +1 point
- **30+ days** → 0 points

#### Interaction Scoring (0-5 points)
- Lead already contacted → +5
- Has email address → +2
- Long message (50+ chars) → +1

#### Quality Multiplier (20% boost)
- Leads with score ≥ 15 get +20% AI boost

### Score Calculation Example
```
Service: Website Development (10)
Age: 3 days (5)
Has email: Yes (2)
Long message: Yes (1)
Contacted: No (0)
Subtotal: 18 points

Quality boost (score ≥ 15): 18 × 1.2 = 21.6 → 21/100
```

### Recalculate All Scores
Go to `/admin/ai-insights.php` → Click **↻ Recalculate Scores** button to update all lead scores instantly.

---

## 🔥 2. AI LEAD RECOMMENDATION ENGINE

### Location
**URL:** `/admin/ai-insights.php` → **"🔥 Recommended Leads"** section

### What It Shows
Top 5 leads ranked by:
1. AI Score (highest first)
2. Regular Score (as tiebreaker)
3. Recency (newest first)

### Display Format
```
┌─────────────────────────────────────┐
│ John Doe                            │
│ Website Development                 │
│ 📱 09068899033                      │
│ Score: ⭐ 15  |  AI: 🤖 75/100     │
│ Status: New  |  Added: Mar 20      │
└─────────────────────────────────────┘
```

### Action Items
✅ Call these leads **first**  
✅ Highest conversion potential  
✅ Fresh + high-quality leads  

### Use Cases
- **Morning Routine** - Check recommended leads before calling
- **Daily Briefing** - See top 5 priority leads
- **Sales Target** - Focus on high-scoring leads for better ROI

---

## ⏰ 3. AI AUTO FOLLOW-UP SYSTEM

### How It Works
System automatically identifies leads needing follow-up:

**Criteria:**
- Status: `new` (not contacted)
- Age: More than 24 hours old
- Pipeline: `new` stage

### Location
**URL:** `/admin/ai-insights.php` → **"⏰ Follow-up Required"** section

### What It Shows
```
Lead Name | Service | Phone | Days Since | Action
--------------------------------------------------
John Doe  | Website | 9068  | 2 days     | View →
Jane Smith| SEO    | 8899  | 1 day      | View →
```

### Auto-Calculation
System shows:
- **Days Since Contact** - How long since lead submitted
- **Follow-up Status** - Pending/Completed
- **Priority Order** - Oldest first (most urgent)

### Manual Follow-up Setting
Use function in code:
```php
setFollowupDate($lead_id, 2); // Sets follow-up for 2 days from now
```

---

## 📊 4. AI BUSINESS INSIGHTS DASHBOARD

### Location
**URL:** `/admin/ai-insights.php`

### Key Metrics Cards
```
┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐
│ 📊 Total │  │ ⭐ Avg   │  │ 📈 Conv. │  │ 🔥 Today │
│   145    │  │  62/100  │  │  32.5%   │  │    8     │
│  Leads   │  │  Score   │  │   Rate   │  │  Leads   │
└──────────┘  └──────────┘  └──────────┘  └──────────┘
```

#### Metrics Explained
1. **Total Leads** - Cumulative count of all leads
2. **Avg AI Score** - Average score across all leads (0-100)
3. **Conversion Rate** - % of leads converted to deals
4. **Today's Leads** - Leads received today

### Business Insights Summaries

#### 🎯 Best Performing Service
```
"Website Development is generating the most leads"
25 leads from this service
```
**Action:** Allocate more budget to this service

#### 🌐 Best Traffic Source
```
"Contact Page is your top-performing source"
89 leads from this source
```
**Action:** Focus marketing on this channel

### Service Breakdown
Query groups leads by service:
```sql
SELECT service, COUNT(*) 
FROM leads 
GROUP BY service 
ORDER BY COUNT(*) DESC
```

### Source Analysis
Query shows traffic source performance:
```sql
SELECT source, COUNT(*) 
FROM leads 
GROUP BY source 
ORDER BY COUNT(*) DESC
```

---

## ✨ 5. AI CONTENT GENERATOR (Gemini API)

### Setup Required
1. Get Google Gemini API key
2. Set environment variable: `GEMINI_API_KEY`
3. Access `/admin/ai-content-generator.php`

### Available Templates

#### 📄 Content Category
- **Business Description** - Professional 150-word summary
- **Blog Ideas** - 5 topic suggestions with descriptions
- **Email Subject** - 5 clickable subject lines

#### 📈 SEO Category
- **Meta Title** - 5 optimized titles (50-60 chars each)

### How to Use

**Step 1:** Select Template
- Click a template from left panel
- Generator form loads with template title

**Step 2:** Fill Details
- **Business Type** - e.g., "Salon", "Tech Startup"
- **Keyword/Focus** - e.g., "Digital Marketing", "Fitness"
- **Service** - e.g., "SEO", "Website Dev" (optional)

**Step 3:** Generate
- Click **🤖 Generate Content** button
- AI processes and returns custom content

**Step 4:** Use Output
- Content appears in green box
- Click **📋 Copy to Clipboard**
- Paste into website/marketing material

### Example Workflow
```
Template: "Business Description"
Business Type: "Web Design Agency"
Keyword: "Full-stack Development"
Service: "Website Development"

Generated:
"[Full text describing a professional web design agency 
focusing on full-stack development solutions...]"
```

### API Details
- **Provider:** Google Gemini API
- **Model:** gemini-pro
- **Temperature:** 0.7 (balanced creativity)
- **Max Tokens:** 1024 characters

### Error Handling
If you see "API key not configured":
1. Go to environment settings
2. Add `GEMINI_API_KEY=your_key_here`
3. Restart application
4. Try generating again

---

## 🧠 6. AI INTEGRATION ARCHITECTURE

### Database Tables

#### ai_insights_cache
```sql
-- Stores cached AI results to avoid repeated API calls
id          INT PRIMARY KEY
cache_key   VARCHAR(255) UNIQUE
cache_value LONGTEXT
expires_at  TIMESTAMP
```

#### ai_prompts
```sql
-- Stores AI prompt templates for content generation
id         INT PRIMARY KEY
title      VARCHAR(255)
prompt     TEXT
category   VARCHAR(50)
created_by INT
```

### AI Functions Library
**File:** `/includes/ai-functions.php`

#### Core Functions
```php
// Calculate AI score for a single lead
calculateAIScore($lead_id) → int (0-100)

// Get top 5 recommended leads
getRecommendedLeads($limit) → array

// Get leads needing follow-up
getFollowupRequiredLeads() → array

// Set reminder for follow-up
setFollowupDate($lead_id, $days) → bool

// Get business insights
getBusinessInsights() → array

// Cache AI results (1 hour default)
cacheAIResult($key, $value, $ttl) → bool

// Get cached result if exists
getCachedAIResult($key) → array|null

// Recalculate all lead scores
recalculateAllAIScores() → int (count updated)
```

### Caching Strategy
- Results cached for **1 hour** by default
- Automatic expiration after TTL
- Prevents excessive API calls
- Improves performance

---

## 🚀 7. SETUP INSTRUCTIONS

### Step 1: Run Migration
Navigate to `/config/migrate-ai-system.php` in browser
- Creates ai_insights_cache table
- Creates ai_prompts table
- Adds ai_score column to leads
- Adds followup_date column to leads
- Inserts default AI prompts

### Step 2: Configure Gemini API
```bash
# Set environment variable
GEMINI_API_KEY=your_api_key_here
```

### Step 3: Access AI Dashboard
1. Go to Admin → 🤖 AI Insights
2. Click "↻ Recalculate Scores" to initialize
3. View recommended leads and insights

### Step 4: Generate Content
1. Go to Admin → ✨ AI Content Gen
2. Select a template
3. Fill in details
4. Generate and copy content

---

## 📈 8. WORKFLOW & BEST PRACTICES

### Daily AI Routine
```
1. Check 🤖 AI Insights Dashboard
2. Review 🔥 Recommended Leads (top 5)
3. Follow up on ⏰ Leads needing attention
4. Generate content as needed
```

### Lead Management
```
New Lead → AI Scores (auto)
    ↓
Display in Recommended (if high score)
    ↓
Mark as Contacted → Score updates
    ↓
Move in CRM Pipeline
    ↓
Convert to Client → Update insights
```

### Content Generation
```
Need content? (Blog, Email, Meta title)
    ↓
Go to AI Content Generator
    ↓
Select template
    ↓
Fill business/keyword
    ↓
Copy generated text
    ↓
Use in website/marketing
```

### Score Management
- **Scores auto-calculate** when leads are created
- **Scores update** when status changes
- **Recalculate all** weekly using button

---

## 🔮 9. ADVANCED FEATURES

### Custom Scoring
You can modify scoring in `/includes/ai-functions.php`:

```php
// Edit service scores
$service_scores = [
    'Your Service' => 10,  // Change these values
    'Another Service' => 8,
];

// Adjust recency weights
if ($days_old <= 7) {
    $score += 5;  // Change boost amount
}

// Add new scoring factors
// ... your custom logic
```

### Custom Prompts
Add prompts via database:

```php
INSERT INTO ai_prompts (title, prompt, category) VALUES (
    'Product Description',
    'Write a product description for {business_type}...',
    'content'
);
```

### API Integration
Connect to other services:
```php
// Call Gemini API directly
callGeminiAPI($prompt, $api_key) → array
```

---

## 📋 10. ADMIN MENU

### New Menu Items
```
Admin Panel
├── 📞 Leads Management
├── 📊 Lead Analytics
├── 🎯 CRM Pipeline
├── 💰 Invoices
├── 💬 WhatsApp Templates
├── 🤖 AI Insights          (NEW)
├── ✨ AI Content Gen       (NEW)
└── ... (other items)
```

---

## ✅ 11. TESTING CHECKLIST

### AI Scoring
- [ ] Submit new lead
- [ ] Check AI score calculated
- [ ] Score appears in leads table
- [ ] High-value leads get higher scores

### Recommendations
- [ ] Go to AI Insights
- [ ] See top 5 recommended leads
- [ ] Leads sorted by AI score
- [ ] Format displays correctly

### Follow-up System
- [ ] Create lead 25+ hours ago
- [ ] Go to AI Insights
- [ ] Should appear in "Follow-up Required"
- [ ] Shows days since contact

### Insights Dashboard
- [ ] All metrics display correctly
- [ ] Total leads count accurate
- [ ] Conversion rate calculated
- [ ] Best service/source visible

### Content Generator
- [ ] Set Gemini API key
- [ ] Select template
- [ ] Fill form fields
- [ ] Click Generate
- [ ] Content appears
- [ ] Copy to clipboard works

---

## 🎯 12. COMMON USE CASES

### Morning Sales Call
1. Go to AI Insights
2. See recommended 5 leads
3. Call in order of AI score
4. Mark as contacted
5. Score refreshes

### Content Creation
1. Need website copy
2. Go to AI Content Gen
3. Select "Business Description"
4. Enter industry
5. Generate and copy

### Weekly Review
1. Check conversion rate
2. Identify best service
3. Review daily trends
4. Plan next week

### Lead Follow-up
1. View follow-up alerts
2. Contact old leads
3. Mark as contacted
4. Move in pipeline

---

## 🔐 13. SECURITY & PRIVACY

### API Security
- API key stored in environment variable
- Never hardcoded in files
- Requests over HTTPS
- Rate limiting respected

### Data Handling
- Only lead data used for scoring
- No PII sent to external APIs
- Cache auto-expires
- Scores stored locally

### Compliance
- GDPR compliant
- User data not shared
- Local processing preferred
- Transparent AI usage

---

## 📞 SUPPORT

**Docs:**
- `/SAAS_PLATFORM_GUIDE.md` - CRM & Invoicing
- `/SYSTEM_UPGRADES.md` - Lead Management
- `/ADVANCED_AUTOMATION_GUIDE.md` - Automation
- `/AI_SYSTEM_GUIDE.md` - This guide

**Contact:**
- Email: connectwithddn@gmail.com
- Phone: 09068899033
- WhatsApp: https://wa.me/919068899033

---

**Version:** 4.0 - AI-Powered  
**Status:** Production Ready  
**Last Updated:** March 28, 2026
