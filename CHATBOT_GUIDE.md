# ConnectWith9 - AI Chatbot System
**Complete Implementation & User Guide**

---

## 📋 System Overview

ConnectWith9 now includes a **floating AI chatbot** powered by Google Gemini API that:
- ✅ Responds to customer questions
- ✅ Captures leads automatically
- ✅ Logs all conversations
- ✅ Integrates with WhatsApp
- ✅ Works on all pages

---

## 🎯 1. FLOATING CHATBOT WIDGET

### Visual Design
```
┌─────────────────┐
│ 💬 Chat Widget  │ ← Header with close button
├─────────────────┤
│ Bot: Hi! I'm... │
│ You: Hi there   │ ← Auto-scrolling messages
│ Bot: Great!...  │
│                 │
│ [Quick Replies] │ ← Website Dev | Pricing | SEO
├─────────────────┤
│ Type message... │ ← Input field
│              ➤  │ ← Send button (bottom right)
└─────────────────┘
```

### Location
- **Position:** Bottom-right corner
- **Size:** 380px wide × 600px tall
- **Mobile:** Full screen (adaptive)
- **Z-index:** Always visible above other elements

### Toggle Button
- **Icon:** 💬 (floating button)
- **Animation:** Smooth open/close
- **Hover:** Scales up slightly
- **Active State:** Color changes when open

### Features
✅ Floating toggle button  
✅ Auto-scroll to latest message  
✅ Typing indicator animation  
✅ Quick reply buttons  
✅ Smooth animations  
✅ Mobile responsive  
✅ Persistent conversation  

---

## 🤖 2. AI RESPONSE SYSTEM (Gemini)

### How It Works

**Step 1: User Message**
```
User types: "I need website development"
```

**Step 2: Frontend**
```javascript
- Capture message
- Display in chat
- Show typing indicator
- Send to /chatbot.php via API
```

**Step 3: Backend Processing**
```php
- Receive message
- Check for lead keywords
- Call Gemini API with system prompt
- Receive AI response
- Log conversation
```

**Step 4: AI Response**
```
Gemini generates: "Great! Website development is one of our 
specialties. Would you like to know more about our services?"
```

**Step 5: Display**
```
- Show response in chat
- Remove typing indicator
- Check if lead capture needed
- Auto-scroll to message
```

### System Prompt
The chatbot uses this system prompt:
```
"You are a professional AI business assistant for ConnectWith. 
You help users with services like:
- Website Development
- E-commerce Solutions
- SEO & Digital Marketing
- Social Media Marketing
- Google Ads & PPC
- CRM Marketing
- Video Marketing
- Content Marketing
- Email Marketing
- Analytics & Reporting

Always be helpful, professional, and encourage users to contact 
via WhatsApp or phone. Keep responses concise (1-2 sentences max 
unless detailed question). If user seems interested in services, 
ask for their name and phone number to connect them."
```

### API Configuration
**Provider:** Google Gemini API  
**Endpoint:** `https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent`  
**Model:** gemini-pro  
**Temperature:** 0.7 (balanced, creative)  
**Max Tokens:** 256 (concise responses)  

---

## 💬 3. LEAD CAPTURE VIA CHAT

### How It Works

**Detection:**
System monitors for lead keywords:
- "need", "interested", "pricing", "quote", "service", "help", "call", "contact", "whatsapp"

**Trigger:**
When user message contains these keywords:
```
User: "I need SEO services"
Bot: "Great! Would you like to share your name and 
phone number? I can connect you with our team..."
```

**Lead Form:**
```
Bot: What's your name?
User: John Doe
Bot: What's your phone number?
User: 09068899033
Bot: What's your email? (or type "skip")
User: john@example.com
Bot: Thank you! Our team will contact you shortly.
```

### Data Captured
- ✅ Name (required)
- ✅ Phone (required)
- ✅ Email (optional)
- ✅ User message (for context)
- ✅ Page URL (where chat happened)
- ✅ Session ID (tracking)

### Lead Storage
Leads are automatically stored in the `leads` table with:
- **service:** "Chat Inquiry"
- **source:** "chat-widget"
- **status:** "new"
- **score:** 10 (default)
- **ai_score:** 50 (from chatbot interaction)

### Lead Notifications
When lead is captured:
1. **Admin Email** sent to `connectwithddn@gmail.com`
2. **User Email** sent to customer (if email provided)
3. **Chat Log** created with lead marker

---

## 🌐 4. WHATSAPP INTEGRATION

### After Lead Capture
Once lead info is captured:
```
Bot: "Thank you! Our team will contact you shortly.
     Continue on WhatsApp →"
```

### WhatsApp Link
```
https://wa.me/919068899033?text=Hi%20ConnectWith
```

### User Experience
1. Click "Continue on WhatsApp" button
2. Opens WhatsApp app/web
3. Pre-filled message starts
4. User can continue conversation

---

## 📊 5. ADMIN CHAT LOGS

### Access
**URL:** `/admin/chat-logs.php`  
**Menu:** Admin > 💬 Chat Logs  
**Access:** Admin/Manager users only  

### Dashboard Metrics
```
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│ Total Chats  │  │ Leads Cap.   │  │    Today     │
│     145      │  │      23      │  │      8       │
└──────────────┘  └──────────────┘  └──────────────┘
```

### Filter Options
1. **All Chats** - All conversations
2. **With Leads** - Only conversations where leads captured
3. **Today** - Conversations from today

### Chat Log Display
Each conversation shows:

**Header:**
- Date & time (e.g., "Mar 28, 2026 14:30")
- Badges (✓ Lead Captured)

**Lead Info (if captured):**
```
👤 John Doe | 📱 09068899033 | 📧 john@example.com
```

**Messages:**
- User message (first 300 chars)
- Bot response (first 300 chars)

**Footer:**
- Page where chat occurred
- Delete button

### Actions
- **Delete Chat** - Removes conversation
- **Filter** - View specific types
- **Pagination** - Navigate results (20 per page)

### Data Stored
**Table:** `chat_logs`

**Columns:**
- id
- user_message
- bot_response
- user_name (if lead)
- user_phone (if lead)
- user_email (if lead)
- lead_captured (boolean)
- page_url
- session_id
- created_at

---

## 🎯 6. QUICK REPLY BUTTONS

### Default Buttons
```
🌐 Website Dev  |  💰 Pricing  |  📈 SEO Services
```

### How They Work
1. User clicks button
2. Message auto-fills in input
3. Message auto-sends
4. Bot responds

### Quick Reply Messages
```
"Tell me about Website Development"
"What is your pricing?"
"I need SEO services"
```

### Customization
Edit in `/includes/chatbot-widget.php`:

```html
<button class="quick-reply-btn" onclick="quickReply('Your message')">
    Icon Your Text
</button>
```

---

## ⚡ 7. FALLBACK RESPONSES

### When API Fails
If Gemini API is unavailable:

```
"Our AI is temporarily unavailable. 
Please contact us on WhatsApp: https://wa.me/919068899033"
```

### No API Key Set
```
"Our AI is temporarily unavailable. 
Please contact us on WhatsApp: https://wa.me/919068899033"
```

### Network Error
```
"Connection error. Please try again."
```

---

## 🚀 8. SETUP INSTRUCTIONS

### Step 1: Run Migration
Navigate to `/config/migrate-chatbot.php` in browser
- Creates `chat_logs` table
- Adds all necessary columns
- Indexes for performance

### Step 2: Set Gemini API Key
Add to environment variables:
```
GEMINI_API_KEY=your_api_key_here
```

### Step 3: Verify Integration
1. Chatbot widget should appear (💬 button bottom-right)
2. Click button to open
3. Try sending a message
4. Should see AI response
5. Go to `/admin/chat-logs.php` to see logs

### Step 4: Test Lead Capture
1. Type: "I need website development"
2. Bot asks for name and phone
3. Enter details
4. Check `/admin/chat-logs.php` for lead
5. Check email for admin notification

---

## 🎨 9. UI/UX FEATURES

### Animations
- ✅ Smooth open/close
- ✅ Message slide-in
- ✅ Typing indicator dots
- ✅ Hover effects
- ✅ Button scale on interaction

### Responsive Design
- **Desktop:** 380px fixed width, bottom-right
- **Mobile:** Full screen, optimized layout
- **Tablet:** Responsive width

### Accessibility
- ✅ Keyboard support (Enter to send)
- ✅ Clear button states
- ✅ Readable fonts
- ✅ Color contrast
- ✅ ARIA labels

### User Experience
- ✅ Auto-scroll to latest message
- ✅ Clear typing indicator
- ✅ Quick reply buttons
- ✅ Persistent conversation
- ✅ Easy close/reopen

---

## 📈 10. WORKFLOW & USAGE

### Customer Journey
```
1. Visit website
2. See chatbot (💬 button)
3. Click to open
4. Ask question
5. Receive AI response
6. Share contact info (optional)
7. Continue on WhatsApp
8. Get contacted by team
```

### Admin Workflow
```
1. Check Admin → 💬 Chat Logs
2. Filter by "With Leads"
3. Review captured leads
4. Delete old chats
5. Follow up on leads
```

### Lead Workflow
```
Chat Lead → Lead Table → CRM Pipeline → Invoice → Payment
```

---

## 🔧 11. CUSTOMIZATION

### Change Chatbot Messages
Edit system prompt in `/chatbot.php`:

```php
$system_prompt = "Your custom prompt here...";
```

### Change Colors
Edit CSS in `/includes/chatbot-widget.php`:

```css
.chatbot-toggle {
    background: linear-gradient(your colors);
}
```

### Change Quick Replies
Edit buttons in `/includes/chatbot-widget.php`:

```html
<button class="quick-reply-btn" onclick="quickReply('New message')">
    Icon New Text
</button>
```

### Change API Model
Edit in `/chatbot.php`:

```php
"models/gemini-pro:generateContent" 
// Change to: models/gemini-1.5-pro, etc.
```

---

## 🔐 12. SECURITY & PRIVACY

### Data Protection
- ✅ Messages logged securely
- ✅ Lead data encrypted in transit
- ✅ No sensitive data exposed
- ✅ HTTPS only

### Privacy Features
- ✅ User can delete chats
- ✅ Admin can delete logs
- ✅ No PII in URLs
- ✅ Session-based tracking

### Compliance
- ✅ GDPR compliant
- ✅ User consent implied by usage
- ✅ Clear data collection
- ✅ Admin audit logs

---

## ⚙️ 13. PERFORMANCE

### Optimization
- ✅ Lightweight widget (~15KB)
- ✅ Lazy loading messages
- ✅ Efficient API calls
- ✅ Client-side rendering

### Caching
- ✅ Message caching (local)
- ✅ No repeated API calls per session
- ✅ Fast response times

### Load Impact
- Minimal: Chatbot is asynchronous
- Doesn't block page load
- Runs independently

---

## 📱 14. MOBILE EXPERIENCE

### Mobile Layout
- **Full screen** chat window
- **Bottom input** for thumb accessibility
- **Large buttons** for touch
- **Optimized fonts** for readability

### Touch Interactions
- ✅ Tap to open/close
- ✅ Easy quick reply buttons
- ✅ One-handed messaging
- ✅ Clear input field

---

## ✅ 15. TESTING CHECKLIST

### Widget Display
- [ ] Toggle button appears (💬)
- [ ] Appears in bottom-right
- [ ] Opens on click
- [ ] Closes on X button
- [ ] Mobile responsive

### Message Functionality
- [ ] Can type message
- [ ] Enter key sends
- [ ] Message appears in chat
- [ ] Typing indicator shows
- [ ] AI response arrives
- [ ] Auto-scrolls to bottom

### Lead Capture
- [ ] Typing keyword triggers offer
- [ ] Lead form appears
- [ ] Can enter name
- [ ] Can enter phone
- [ ] Can enter email
- [ ] Lead saved to database

### Admin Functions
- [ ] Chat logs appear
- [ ] Can filter logs
- [ ] Can delete chats
- [ ] Pagination works
- [ ] Stats are accurate

### API Integration
- [ ] Gemini API responds
- [ ] Fallback works (test by disabling API key)
- [ ] Admin email sent
- [ ] User email sent
- [ ] No console errors

### Mobile Testing
- [ ] Widget opens full screen
- [ ] Messages readable
- [ ] Input accessible
- [ ] Buttons tappable
- [ ] No layout issues

---

## 🎯 16. COMMON USE CASES

### Customer Asking About Service
```
User: "Do you do website development?"
Bot: "Yes! Website development is one of our specialties. 
     Would you like to know more?"
User: "Yes, I'm interested"
Bot: "Great! Can I get your name and phone number?"
→ Lead captured
```

### Quick Question
```
User: "What's your pricing?"
Bot: "Pricing depends on your needs. [Answer]"
→ No lead capture (pricing question)
```

### Service Inquiry
```
User: "I need SEO services"
Bot: "[SEO explanation] Would you like our team to contact you?"
User: "Yes"
→ Lead capture triggered
```

---

## 📞 SUPPORT

**Docs:**
- `/SAAS_PLATFORM_GUIDE.md` - CRM & Invoicing
- `/ADVANCED_AUTOMATION_GUIDE.md` - Lead automation
- `/AI_SYSTEM_GUIDE.md` - AI features
- `/CHATBOT_GUIDE.md` - This guide

**Contact:**
- Email: connectwithddn@gmail.com
- Phone: 09068899033
- WhatsApp: https://wa.me/919068899033

---

**Version:** 5.0 - Chatbot System  
**Status:** Production Ready  
**Last Updated:** March 28, 2026
