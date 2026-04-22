<?php
/**
 * Chatbot API Endpoint
 * Handles user messages and Gemini API integration
 */

session_start();
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', '0');

try {
    require_once './config/db.php';
    
    // Auto-create chat_logs table if doesn't exist
    if ($conn && !$conn->connect_error) {
        $check = @$conn->query("SHOW TABLES LIKE 'chat_logs'");
        if (!$check || $check->num_rows === 0) {
            @$conn->query("CREATE TABLE IF NOT EXISTS chat_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_message TEXT,
                bot_response TEXT,
                user_name VARCHAR(100),
                user_phone VARCHAR(20),
                user_email VARCHAR(100),
                lead_captured BOOLEAN DEFAULT FALSE,
                page_url VARCHAR(255),
                session_id VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_created_at (created_at),
                INDEX idx_session (session_id),
                INDEX idx_lead_captured (lead_captured)
            )");
        }
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $user_message = trim($input['message'] ?? '');
    $page_url = $input['page'] ?? $_SERVER['HTTP_REFERER'] ?? '';
    $session_id = session_id();
    
    if (empty($user_message)) {
        echo json_encode(['success' => false, 'reply' => 'Please enter a message.']);
        exit;
    }
    
    // ====== LANGUAGE DETECTION ======
    $language = detectLanguage($user_message);
    
    // ====== INTENT DETECTION ======
    $intent = detectIntent($user_message);
    
    // ====== GET SMART RESPONSE ======
    $bot_reply = getSmartResponse($intent, $language, $user_message);
    
    // Lead capture logic
    $should_capture_lead = ($intent !== 'greeting' && $intent !== 'general');
    $has_whatsapp_button = false;
    
    // Add lead capture prompt if needed
    if ($should_capture_lead && stripos($bot_reply, 'phone') === false && stripos($bot_reply, 'name') === false) {
        $bot_reply .= "\n\nWould you like to share your details? I can connect you with our team.";
    }
    
    // Log chat message
    if ($conn && !$conn->connect_error) {
        $stmt = $conn->prepare("INSERT INTO chat_logs (user_message, bot_response, page_url, session_id) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('ssss', $user_message, $bot_reply, $page_url, $session_id);
            @$stmt->execute();
            $chat_log_id = @$stmt->insert_id;
            $stmt->close();
        }
    }
    
    // Handle lead capture
    $lead_data = $input['lead_data'] ?? null;
    if ($lead_data && isset($lead_data['name']) && isset($lead_data['phone']) && $conn && !$conn->connect_error) {
        $name = trim($lead_data['name']);
        $phone = trim($lead_data['phone']);
        $email = trim($lead_data['email'] ?? '');
        $service = 'Chat Inquiry';
        
        $check_leads = @$conn->query("SHOW TABLES LIKE 'leads'");
        if ($check_leads && $check_leads->num_rows > 0) {
            $check = @$conn->query("SELECT id FROM leads WHERE phone = '$phone' AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
            
            if ($check && $check->num_rows === 0) {
                $message = "Chat inquiry: " . substr($user_message, 0, 200);
                $stmt = $conn->prepare("INSERT INTO leads (name, phone, email, service, message, source, status, score, ai_score) VALUES (?, ?, ?, ?, ?, 'chat-widget', 'new', 10, 50)");
                if ($stmt) {
                    $stmt->bind_param('sssss', $name, $phone, $email, $service, $message);
                    if (@$stmt->execute()) {
                        @$conn->query("UPDATE chat_logs SET lead_captured = TRUE, user_name = '$name', user_phone = '$phone', user_email = '$email' WHERE id = $chat_log_id");
                        sendAdminNotification($name, $phone, $email, $service, $user_message);
                        if (!empty($email)) {
                            sendUserEmail($name, $email);
                        }
                    }
                    $stmt->close();
                }
            }
        }
    }
    
    // Response
    echo json_encode([
        'success' => true,
        'reply' => $bot_reply,
        'should_capture_lead' => $should_capture_lead,
        'has_whatsapp_button' => $has_whatsapp_button,
        'language' => $language,
        'intent' => $intent
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'reply' => 'Something went wrong. Please try again.'
    ]);
}

// Gemini API call
function callGeminiAPI($user_message, $system_prompt, $api_key) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $api_key;
    
    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $system_prompt . "\n\nUser: " . $user_message]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.7,
            "maxOutputTokens" => 256
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = @curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && $response) {
        $json = json_decode($response, true);
        if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            return $json['candidates'][0]['content']['parts'][0]['text'];
        }
    }
    
    return '';
}

// Send admin notification
function sendAdminNotification($name, $phone, $email, $service, $message) {
    $admin_email = "connectwithddn@gmail.com";
    $subject = "New Chat Lead: $name";
    $body = "New lead from chat:\nName: $name\nPhone: $phone\nEmail: $email\nService: $service\nMessage: " . substr($message, 0, 150);
    $headers = "From: noreply@connectwith.in\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    @mail($admin_email, $subject, $body, $headers);
}

// Send user email
function sendUserEmail($name, $email) {
    $subject = "Thank You for Contacting ConnectWith!";
    $body = "Hi $name,\n\nThank you for chatting with us! Our team will reach out shortly.\n\nContact us:\n📞 09068899033\n💬 https://wa.me/919068899033\n\nBest regards,\nConnectWith Team";
    $headers = "From: noreply@connectwith.in\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    @mail($email, $subject, $body, $headers);
}

// ====== LANGUAGE DETECTION FUNCTION ======
function detectLanguage($text) {
    $hindi_words = ['hain', 'mujhe', 'chahiye', 'kya', 'nahi', 'hai', 'ke', 'ko', 'yeh', 'vah'];
    
    $text_lower = strtolower($text);
    $hindi_count = 0;
    
    foreach ($hindi_words as $word) {
        if (stripos($text_lower, $word) !== false) {
            $hindi_count++;
        }
    }
    
    return ($hindi_count >= 2) ? 'hi' : 'en';
}

// ====== INTENT DETECTION FUNCTION ======
function detectIntent($text) {
    $text_lower = strtolower($text);
    
    // Website intent
    if (preg_match('/website|website.*develop|develop.*website|online.*presence|new.*website|ecommerce/', $text_lower)) {
        return 'website';
    }
    
    // SEO intent
    if (preg_match('/seo|search.*engine|google.*rank|rank.*website|organic.*lead|website.*traffic/', $text_lower)) {
        return 'seo';
    }
    
    // Ads intent
    if (preg_match('/google.*ads|paid.*ads|ads.*campaign|facebook.*ads|advertising|google.*adwords/', $text_lower)) {
        return 'ads';
    }
    
    // Pricing intent
    if (preg_match('/price|cost|how.*much|budget|rate|plan|package/', $text_lower)) {
        return 'pricing';
    }
    
    // Contact intent
    if (preg_match('/contact|call.*me|phone|whatsapp|talk|connect/', $text_lower)) {
        return 'contact';
    }
    
    // Greeting
    if (preg_match('/hi|hello|hey|namaste|assalam/', $text_lower)) {
        return 'greeting';
    }
    
    return 'general';
}

// ====== SMART RESPONSE FUNCTION ======
function getSmartResponse($intent, $language, $user_message) {
    $responses = [
        'en' => [
            'website' => "Great! We create professional websites starting at ₹10,000 with 4-year validity. Our team will design, develop, and deploy your site. Interested?",
            'seo' => "Perfect! We help businesses rank #1 on Google and generate qualified leads through ethical SEO. Would you like a free 30-minute consultation?",
            'ads' => "Excellent choice! Our Google Ads experts create high-converting campaigns that deliver instant results. Shall I connect you with our specialist?",
            'pricing' => "Our services are priced competitively. Website: ₹10,000 | SEO: ₹5,000-15,000/month | Ads: Custom quotes. Which interests you?",
            'contact' => "I'd love to help! Can I get your name and phone number so our team can reach out with the perfect solution?",
            'greeting' => "Hello! 👋 I'm ConnectWith's AI assistant. I can help you with Website Development, SEO, Google Ads, or any digital service. What do you need?",
            'general' => "I can assist with Website Development, SEO, Google Ads, Digital Marketing, and more. What are you looking for?"
        ],
        'hi' => [
            'website' => "बहुत अच्छा! हम पेशेवर वेबसाइट बनाते हैं सिर्फ ₹10,000 में 4 साल की वैधता के साथ। क्या आप शुरुआत करना चाहते हैं?",
            'seo' => "परफेक्ट! हम आपकी वेबसाइट को Google पर #1 पर रैंक करवाते हैं। क्या आप एक फ्री परामर्श चाहते हैं?",
            'ads' => "बेहतरीन! हमारे Google Ads विशेषज्ञ आपको तुरंत परिणाम देते हैं। क्या मैं आपको विशेषज्ञ से जोड़ूं?",
            'pricing' => "वेबसाइट: ₹10,000 | SEO: ₹5,000-15,000/माह | Ads: कस्टम। कौन सा आपको चाहिए?",
            'contact' => "आपका नाम और फोन नंबर दे सकते हैं? हमारी टीम आपको बेहतरीन समाधान प्रदान करेगी।",
            'greeting' => "नमस्ते! 👋 मैं ConnectWith का AI सहायक हूं। वेबसाइट, SEO, Google Ads में मदद कर सकता हूं। आपको क्या चाहिए?",
            'general' => "मैं वेबसाइट, SEO, Google Ads, डिजिटल मार्केटिंग में मदद कर सकता हूं। आपको क्या चाहिए?"
        ]
    ];
    
    $lang = ($language === 'hi') ? 'hi' : 'en';
    return $responses[$lang][$intent] ?? $responses[$lang]['general'];
}

@$conn->close();
?>
