<?php
/**
 * Chatbot Widget
 * Include this in header/footer for floating chatbot
 */

// Check if chatbot is enabled in admin settings
$chatbotEnabled = true;
if (!function_exists('get_db_setting')) {
    try {
        if (!isset($conn)) {
            require_once dirname(__FILE__) . '/../config/db.php';
        }
        
        // Ensure table exists
        $createTableSQL = "CREATE TABLE IF NOT EXISTS admin_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        @$conn->query($createTableSQL);
        
        // Get setting
        $result = @$conn->query("SELECT setting_value FROM admin_settings WHERE setting_key = 'chatbot_enabled'");
        if ($result && $result->num_rows > 0) {
            $chatbotEnabled = $result->fetch_assoc()['setting_value'] == '1';
        }
    } catch (Exception $e) {
        $chatbotEnabled = true;
    }
}

// Exit early if chatbot is disabled
if (!$chatbotEnabled) {
    return;
}
?>

<style>
    /* ====== CHATBOT WIDGET STYLES ====== */
    .chatbot-toggle {
        position: fixed;
        bottom: 20px;
        left: 20px;
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        font-size: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999;
        transition: all 0.3s;
    }

    .chatbot-toggle:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
    }

    .chatbot-toggle.active {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    }

    .chatbot-container {
        position: fixed;
        bottom: 90px;
        left: 20px;
        width: 380px;
        height: 600px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 5px 40px rgba(0,0,0,0.16);
        display: none;
        flex-direction: column;
        z-index: 998;
        opacity: 0;
        transform: translateY(20px) scale(0.95);
        transition: all 0.3s;
    }

    .chatbot-container.active {
        display: flex;
        opacity: 1;
        transform: translateY(0) scale(1);
    }

    .chatbot-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 12px 12px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chatbot-header h3 {
        margin: 0;
        font-size: 16px;
    }

    .chatbot-header button {
        background: rgba(255,255,255,0.3);
        border: none;
        color: white;
        cursor: pointer;
        font-size: 20px;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        transition: all 0.3s;
    }

    .chatbot-header button:hover {
        background: rgba(255,255,255,0.5);
    }

    .chatbot-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        background: #f8f9fa;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .message {
        display: flex;
        gap: 8px;
        animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .message.user {
        justify-content: flex-end;
    }

    .message-bubble {
        max-width: 80%;
        padding: 12px 16px;
        border-radius: 12px;
        word-wrap: break-word;
        font-size: 14px;
        line-height: 1.4;
    }

    .message.user .message-bubble {
        background: #667eea;
        color: white;
        border-radius: 12px 0 12px 12px;
    }

    .message.bot .message-bubble {
        background: white;
        color: #333;
        border: 1px solid #ddd;
        border-radius: 0 12px 12px 12px;
    }

    .message-bubble a {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
    }

    .message.user .message-bubble a {
        color: white;
    }

    .chatbot-typing {
        display: flex;
        gap: 4px;
        padding: 12px 16px;
    }

    .typing-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #999;
        animation: typing 1.4s infinite;
    }

    .typing-dot:nth-child(2) {
        animation-delay: 0.2s;
    }

    .typing-dot:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes typing {
        0%, 60%, 100% {
            opacity: 0.5;
            transform: translateY(0);
        }
        30% {
            opacity: 1;
            transform: translateY(-10px);
        }
    }

    .chatbot-quick-replies {
        padding: 0 20px;
        display: flex;
        gap: 8px;
        overflow-x: auto;
        margin-bottom: 10px;
    }

    .quick-reply-btn {
        padding: 8px 12px;
        background: #f0f0f0;
        border: 1px solid #ddd;
        border-radius: 20px;
        cursor: pointer;
        font-size: 12px;
        white-space: nowrap;
        transition: all 0.3s;
    }

    .quick-reply-btn:hover {
        background: #667eea;
        color: white;
        border-color: #667eea;
    }

    .chatbot-input-area {
        padding: 15px;
        border-top: 1px solid #eee;
        display: flex;
        gap: 10px;
    }

    .chatbot-input {
        flex: 1;
        border: 1px solid #ddd;
        border-radius: 20px;
        padding: 10px 15px;
        font-size: 14px;
        font-family: inherit;
        resize: none;
        max-height: 100px;
    }

    .chatbot-input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .chatbot-send, .chatbot-voice, .chatbot-stop {
        width: 40px;
        height: 40px;
        background: #667eea;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        color: white;
        font-size: 18px;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .chatbot-send:hover, .chatbot-voice:hover {
        background: #5568d3;
        transform: scale(1.05);
    }

    .chatbot-send:active, .chatbot-voice:active, .chatbot-stop:active {
        transform: scale(0.95);
    }

    .chatbot-stop {
        background: #f44336;
    }

    .chatbot-stop:hover {
        background: #d32f2f;
    }

    .listening-indicator {
        display: none;
        text-align: center;
        padding: 10px;
        background: #e3f2fd;
        color: #1976d2;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        margin: 0 15px 10px;
        animation: pulse 1.5s infinite;
    }

    .listening-indicator.active {
        display: block;
    }

    @keyframes pulse {
        0%, 100% { opacity: 0.6; }
        50% { opacity: 1; }
    }

    .lead-capture-form {
        background: #f0f0f0;
        padding: 15px;
        border-radius: 8px;
        margin: 10px 0;
    }

    .lead-capture-form input {
        width: 100%;
        padding: 8px 12px;
        margin-bottom: 8px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 13px;
        box-sizing: border-box;
    }

    .lead-capture-form button {
        width: 100%;
        padding: 10px;
        background: #25D366;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        font-size: 13px;
    }

    .lead-capture-form button:hover {
        background: #20B759;
    }

    @media (max-width: 480px) {
        .chatbot-container {
            width: 100%;
            height: 100%;
            bottom: 0;
            left: 0;
            border-radius: 0;
        }

        .message-bubble {
            max-width: 90%;
        }
    }
</style>

<!-- Chatbot Toggle Button -->
<button id="chatbot-toggle" class="chatbot-toggle" title="Chat with us">💬</button>

<!-- Chatbot Container -->
<div id="chatbot-container" class="chatbot-container">
    <div class="chatbot-header">
        <h3>💬 Chat with ConnectWith</h3>
        <div style="display: flex; gap: 10px; align-items: center;">
            <button id="voice-toggle-btn" onclick="toggleVoice()" type="button" title="Enable/Disable Voice" style="background: none; border: none; color: white; font-size: 18px; cursor: pointer; padding: 5px;">🎤</button>
            <button onclick="toggleChatbot()" type="button" style="background: none; border: none; color: white; font-size: 20px; cursor: pointer; padding: 5px;">✕</button>
        </div>
    </div>

    <div class="chatbot-messages" id="chatbot-messages">
        <div class="message bot">
            <div class="message-bubble" id="welcome-message">
                👋 Hi! I'm here to help. Ask me about our services, or anything about this portal!
            </div>
        </div>
    </div>

    <div class="chatbot-quick-replies" id="quick-replies">
        <!-- Dynamic quick replies will be loaded here -->
        <button class="quick-reply-btn" onclick="quickReply('How to add my business?')">➕ Add Business</button>
        <button class="quick-reply-btn" onclick="quickReply('Tell me about services')">📋 Services</button>
        <button class="quick-reply-btn" onclick="quickReply('Contact information')">📞 Contact</button>
    </div>

    <div class="listening-indicator" id="listening-indicator">
        🎤 Listening...
    </div>

    <div class="chatbot-input-area">
        <input 
            type="text" 
            id="chatbot-input" 
            class="chatbot-input" 
            placeholder="Type or speak..."
            onkeypress="handleKeyPress(event)"
        >
        <button class="chatbot-voice" id="voice-btn" onclick="startListening()" title="Speak to chat">🎤</button>
        <button class="chatbot-send" onclick="sendChatbotMessage()" title="Send message">➤</button>
    </div>
</div>

<script>
let isCapturingLead = false;
let capturedLeadData = {};
let voiceEnabled = localStorage.getItem('chatbot_voice_enabled') !== 'false'; // Default true

// Initialize voice state on load
function initVoiceState() {
    const voiceBtn = document.getElementById('voice-toggle-btn');
    if (voiceBtn) {
        voiceBtn.textContent = voiceEnabled ? '🎤' : '🔇';
        voiceBtn.style.opacity = voiceEnabled ? '1' : '0.5';
    }
}

function toggleVoice() {
    voiceEnabled = !voiceEnabled;
    localStorage.setItem('chatbot_voice_enabled', voiceEnabled);
    
    const voiceBtn = document.getElementById('voice-toggle-btn');
    voiceBtn.textContent = voiceEnabled ? '🎤' : '🔇';
    voiceBtn.style.opacity = voiceEnabled ? '1' : '0.5';
    
    // Show feedback message
    const message = voiceEnabled ? 'Voice enabled ✓' : 'Voice disabled ✓';
    addMessage('bot', message);
}

// Initialize on page load
window.addEventListener('DOMContentLoaded', initVoiceState);

function toggleChatbot() {
    const toggle = document.getElementById('chatbot-toggle');
    const container = document.getElementById('chatbot-container');
    
    toggle.classList.toggle('active');
    container.classList.toggle('active');
    
    if (container.classList.contains('active')) {
        document.getElementById('chatbot-input').focus();
    }
}

document.getElementById('chatbot-toggle').addEventListener('click', toggleChatbot);

function handleKeyPress(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendChatbotMessage();
    }
}

function quickReply(message) {
    document.getElementById('chatbot-input').value = message;
    sendChatbotMessage();
}

function addMessage(sender, text, hasButton = false) {
    const messagesDiv = document.getElementById('chatbot-messages');
    const messageEl = document.createElement('div');
    messageEl.className = `message ${sender}`;
    
    const bubble = document.createElement('div');
    bubble.className = 'message-bubble';
    bubble.innerHTML = text.replace(/\n/g, '<br>');
    
    messageEl.appendChild(bubble);
    
    // Add WhatsApp button if needed
    if (hasButton) {
        const buttonDiv = document.createElement('div');
        buttonDiv.style.marginTop = '12px';
        buttonDiv.innerHTML = '<a href="https://wa.me/919068899033?text=Hi%20ConnectWith" target="_blank" style="display: inline-block; background: #25D366; color: white; padding: 10px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px; cursor: pointer;">💬 Contact on WhatsApp</a>';
        messageEl.appendChild(buttonDiv);
    }
    
    messagesDiv.appendChild(messageEl);
    
    // Auto scroll to bottom
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function showTyping() {
    const messagesDiv = document.getElementById('chatbot-messages');
    const typingEl = document.createElement('div');
    typingEl.className = 'message bot';
    typingEl.id = 'typing-indicator';
    typingEl.innerHTML = '<div class="chatbot-typing"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div>';
    messagesDiv.appendChild(typingEl);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function removeTyping() {
    const typing = document.getElementById('typing-indicator');
    if (typing) typing.remove();
}

function sendChatbotMessage() {
    const input = document.getElementById('chatbot-input');
    const message = input.value.trim();
    
    if (!message) return;
    
    // Add user message
    addMessage('user', message);
    input.value = '';
    
    // Show typing indicator
    showTyping();
    
    // Handle lead capture form
    if (isCapturingLead) {
        if (message.includes('@')) {
            capturedLeadData.email = message;
            isCapturingLead = false;
            removeTyping();
            
            // Show confirmation
            addMessage('bot', '📞 Thank you! Our team will contact you shortly.\n\n<a href="https://wa.me/919068899033?text=Hi%20ConnectWith" target="_blank">Continue on WhatsApp →</a>');
            
            // Send to backend
            sendLeadCapture();
            
            return;
        } else if (!capturedLeadData.name) {
            capturedLeadData.name = message;
            removeTyping();
            addMessage('bot', 'Thanks ' + message + '! What\'s your phone number?');
            return;
        } else if (!capturedLeadData.phone) {
            capturedLeadData.phone = message;
            removeTyping();
            addMessage('bot', 'What\'s your email address? (or type "skip")');
            return;
        }
    }
    
    // Get business_id if on business listing page
    const businessId = new URLSearchParams(window.location.search).get('id');
    
    // Try AI API first, fallback to chatbot.php
    const aiPayload = new FormData();
    aiPayload.append('message', message);
    if (businessId) aiPayload.append('business_id', businessId);
    
    // Try AI API endpoint
    fetch('/api/ai-chat.php', {
        method: 'POST',
        body: aiPayload
    })
    .then(res => res.json())
    .then(data => {
        removeTyping();
        if (data.success || data.response) {
            addMessage('bot', data.response || 'How can I help?');
        } else {
            // Fallback to regular chatbot
            fallbackChatbot(message);
        }
    })
    .catch(() => {
        // Fallback to regular chatbot.php
        fallbackChatbot(message);
    });
}

function fallbackChatbot(message) {
    const businessId = new URLSearchParams(window.location.search).get('id');
    
    fetch('/chatbot.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            message: message,
            page: window.location.href,
            business_id: businessId || null,
            lead_data: Object.keys(capturedLeadData).length > 0 ? capturedLeadData : null
        })
    })
    .then(res => res.json())
    .then(data => {
        removeTyping();
        
        if (data.success) {
            // Update current language
            if (data.language) {
                currentLanguage = data.language;
            }
            
            addMessage('bot', data.reply, data.has_whatsapp_button || false);
            
            // Check if we should capture lead
            if (data.should_capture_lead && !isCapturingLead) {
                isCapturingLead = true;
                const leadPrompt = currentLanguage === 'hi' 
                    ? 'क्या आप अपना नाम और नंबर दे सकते हैं? हमारी टीम आपसे संपर्क करेगी।'
                    : 'Would you like to share your details? I can connect you with our team.';
                setTimeout(() => {
                    addMessage('bot', leadPrompt);
                }, 500);
            }
        } else {
            addMessage('bot', 'Something went wrong. Please try again.');
        }
    })
    .catch(err => {
        removeTyping();
        addMessage('bot', 'Connection error. Please try again.');
        console.error('Chatbot error:', err);
    });
}

function sendLeadCapture() {
    fetch('/chatbot.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            message: 'Lead capture completed',
            lead_data: capturedLeadData
        })
    });
}

// ====== VOICE AI FUNCTIONS ======
let recognition = null;
let isListening = false;

function initSpeechRecognition() {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
        console.warn('Speech Recognition not supported');
        return false;
    }
    
    recognition = new SpeechRecognition();
    recognition.lang = 'en-IN';
    recognition.continuous = false;
    recognition.interimResults = false;
    
    recognition.onstart = () => {
        isListening = true;
        document.getElementById('listening-indicator').classList.add('active');
        document.getElementById('voice-btn').style.background = '#f44336';
    };
    
    recognition.onend = () => {
        isListening = false;
        document.getElementById('listening-indicator').classList.remove('active');
        document.getElementById('voice-btn').style.background = '#667eea';
    };
    
    recognition.onresult = (event) => {
        let transcript = '';
        for (let i = event.resultIndex; i < event.results.length; i++) {
            transcript += event.results[i][0].transcript;
        }
        
        if (event.results[0].isFinal) {
            document.getElementById('chatbot-input').value = transcript.trim();
            recognition.stop();
            sendChatbotMessage();
        }
    };
    
    recognition.onerror = (event) => {
        console.error('Speech recognition error:', event.error);
        isListening = false;
        document.getElementById('listening-indicator').classList.remove('active');
    };
    
    return true;
}

function startListening() {
    if (!voiceEnabled) {
        addMessage('bot', 'Voice is disabled. Click 🎤 in the header to enable it.');
        return;
    }
    
    if (!recognition) {
        if (!initSpeechRecognition()) {
            alert('Speech recognition not supported in this browser. Please use Chrome, Edge, or Safari.');
            return;
        }
    }
    
    if (isListening) {
        recognition.stop();
    } else {
        document.getElementById('chatbot-input').value = '';
        recognition.start();
    }
}

function speak(text, language = 'en') {
    // Stop any ongoing speech
    if (window.speechSynthesis.speaking) {
        window.speechSynthesis.cancel();
    }
    
    const SpeechSynthesisUtterance = window.SpeechSynthesisUtterance || window.webkitSpeechSynthesisUtterance;
    if (!SpeechSynthesisUtterance) {
        console.warn('Text-to-Speech not supported');
        return false;
    }
    
    // Clean text for speech (remove emojis and URLs)
    let cleanText = text.replace(/[^\w\s\.,\?\!\u0900-\u097F]/gi, ' ')
                       .replace(/http[s]?:\/\/[^\s]+/g, '')
                       .replace(/\s+/g, ' ')
                       .trim();
    
    if (!cleanText) return false;
    
    const utterance = new SpeechSynthesisUtterance(cleanText);
    utterance.lang = (language === 'hi') ? 'hi-IN' : 'en-IN';
    utterance.rate = 1.0;
    utterance.pitch = 1.0;
    utterance.volume = 1.0;
    
    window.speechSynthesis.speak(utterance);
    return true;
}

// Store current language
let currentLanguage = 'en';

// Override addMessage to include voice
const originalAddMessage = addMessage;
addMessage = function(sender, text, hasButton = false) {
    originalAddMessage.call(this, sender, text, hasButton);
    
    // Speak bot responses
    if (sender === 'bot') {
        setTimeout(() => {
            speak(text, currentLanguage);
        }, 500);
    }
};

// Initialize speech recognition
initSpeechRecognition();

// Initialize
console.log('✓ Chatbot widget loaded with Voice AI');
</script>
