<?php
/**
 * AI Chat Assistant - Interactive Business Discovery
 */

require_once '../config/db.php';
require_once '../config/auth.php';
require_once '../includes/functions.php';
require_once '../config/ai.php';

$page_title = 'AI Business Assistant | ConnectWith9';
$meta_description = 'Ask our AI assistant to find the perfect business for your needs. Get personalized recommendations powered by AI.';

include '../includes/header.php';
?>

<style>
.chat-container {
    max-width: 900px;
    margin: 30px auto;
    padding: 0 20px;
    min-height: calc(100vh - 300px);
    display: flex;
    flex-direction: column;
}

.chat-header {
    text-align: center;
    margin-bottom: 30px;
}

.chat-header h1 {
    color: #0B1C3D;
    font-size: 32px;
    margin: 0 0 10px 0;
}

.chat-header p {
    color: #666;
    font-size: 16px;
    margin: 0;
}

.chat-box {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: 500px;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #F9F9F9;
}

.chat-message {
    margin-bottom: 15px;
    display: flex;
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

.chat-message.user {
    justify-content: flex-end;
}

.chat-message.ai {
    justify-content: flex-start;
}

.message-content {
    max-width: 70%;
    padding: 12px 15px;
    border-radius: 10px;
    font-size: 14px;
    line-height: 1.5;
}

.message-content.user-msg {
    background: #667eea;
    color: white;
}

.message-content.ai-msg {
    background: #E8E8FF;
    color: #333;
}

.chat-input-area {
    padding: 15px;
    border-top: 1px solid #ddd;
    background: white;
    display: flex;
    gap: 10px;
}

.chat-input-area input {
    flex: 1;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
}

.chat-input-area input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.chat-input-area button {
    padding: 12px 25px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.chat-input-area button:hover {
    background: #5568d3;
    transform: translateY(-2px);
}

.chat-input-area button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.suggestion-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    margin-top: 20px;
}

.suggestion-btn {
    padding: 12px 15px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.3s;
}

.suggestion-btn:hover {
    border-color: #667eea;
    background: #F9F9FF;
    color: #667eea;
}

.results-container {
    margin-top: 20px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.result-card {
    background: white;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #ddd;
    transition: all 0.3s;
}

.result-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.result-card h3 {
    margin: 0 0 8px 0;
    color: #0B1C3D;
    font-size: 15px;
}

.result-card .rating {
    color: #FFB800;
    font-size: 13px;
    margin-bottom: 8px;
}

.result-card p {
    margin: 0;
    color: #666;
    font-size: 13px;
    line-height: 1.4;
}

.result-card .action-link {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
    margin-top: 10px;
    display: inline-block;
}

.typing-indicator {
    display: flex;
    gap: 4px;
    padding: 12px 15px;
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
</style>

<div class="chat-container">
    <div class="chat-header">
        <h1>🤖 AI Business Assistant</h1>
        <p>Ask me anything to find the perfect business for your needs</p>
    </div>

    <div class="chat-box">
        <div class="chat-messages" id="chatMessages">
            <div class="chat-message ai">
                <div class="message-content ai-msg">
                    👋 Hi! I'm your AI assistant. Ask me to find businesses like:<br><br>
                    • "Best restaurants near me"<br>
                    • "Cheap gym in Ahmedabad"<br>
                    • "Family-friendly cafes"<br>
                    • "Top-rated hair salons"
                </div>
            </div>
        </div>

        <div class="chat-input-area">
            <input type="text" id="chatInput" placeholder="Ask me anything..." />
            <button onclick="sendMessage()" id="sendBtn">Send</button>
        </div>
    </div>

    <div class="suggestion-buttons" id="suggestions">
        <button class="suggestion-btn" onclick="askQuestion('Find best restaurants in Ahmedabad')">Best Restaurants</button>
        <button class="suggestion-btn" onclick="askQuestion('Show me affordable gyms')">Affordable Gyms</button>
        <button class="suggestion-btn" onclick="askQuestion('Family-friendly cafes near me')">Family Cafes</button>
        <button class="suggestion-btn" onclick="askQuestion('Top rated hair salons')">Hair Salons</button>
    </div>
</div>

<script>
const chatMessages = document.getElementById('chatMessages');
const chatInput = document.getElementById('chatInput');
const sendBtn = document.getElementById('sendBtn');

// Auto-focus on input
chatInput.focus();

// Send on Enter key
chatInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

function addMessage(text, isUser = false) {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chat-message ' + (isUser ? 'user' : 'ai');
    
    const contentDiv = document.createElement('div');
    contentDiv.className = 'message-content ' + (isUser ? 'user-msg' : 'ai-msg');
    contentDiv.textContent = text;
    
    messageDiv.appendChild(contentDiv);
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function showTyping() {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chat-message ai';
    messageDiv.id = 'typingIndicator';
    
    const typingDiv = document.createElement('div');
    typingDiv.className = 'typing-indicator';
    typingDiv.innerHTML = '<div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>';
    
    messageDiv.appendChild(typingDiv);
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function removeTyping() {
    const typing = document.getElementById('typingIndicator');
    if (typing) typing.remove();
}

function askQuestion(question) {
    chatInput.value = question;
    sendMessage();
}

async function sendMessage() {
    const message = chatInput.value.trim();
    if (!message) return;
    
    // Clear input
    chatInput.value = '';
    chatInput.focus();
    
    // Add user message
    addMessage(message, true);
    
    // Show typing
    showTyping();
    
    try {
        // Call backend to process with AI
        const response = await fetch('/api/ai-chat.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ query: message })
        });
        
        const data = await response.json();
        removeTyping();
        
        if (data.success) {
            // Add AI response
            addMessage(data.response);
            
            // Add results if any
            if (data.results && data.results.length > 0) {
                displayResults(data.results);
            }
        } else {
            addMessage('Sorry, I encountered an error. Please try again.');
        }
    } catch (error) {
        removeTyping();
        addMessage('Sorry, I encountered an error. Please try again.');
    }
}

function displayResults(results) {
    const resultsContainer = document.createElement('div');
    resultsContainer.className = 'results-container';
    
    results.forEach(business => {
        const card = document.createElement('div');
        card.className = 'result-card';
        card.innerHTML = `
            <h3>${business.name}</h3>
            <div class="rating">⭐ ${business.rating}/5 (${business.reviews} reviews)</div>
            <p>${business.category}</p>
            <a href="/pages/business-detail.php?id=${business.id}" class="action-link">View Details →</a>
        `;
        resultsContainer.appendChild(card);
    });
    
    chatMessages.appendChild(resultsContainer);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}
</script>

<?php include '../includes/footer.php'; ?>
