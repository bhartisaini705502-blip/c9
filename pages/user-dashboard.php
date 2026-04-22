<?php
/**
 * User Dashboard - Access all features
 */

require '../config/db.php';
require '../includes/functions.php';

$page_title = 'User Dashboard';
include '../includes/header.php';
?>

<section class="user-dashboard-section">
    <div class="container">
        <h1>🎯 ConnectWith9 - User Dashboard</h1>
        <p class="subtitle">Access all features and services</p>
        
        <div class="dashboard-grid">
            <!-- SEARCH SECTION -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>🔍 Search Businesses</h2>
                    <p>Find businesses by category, location, and keywords</p>
                </div>
                <ul class="feature-list">
                    <li>✓ Search by keyword</li>
                    <li>✓ Filter by state & city</li>
                    <li>✓ Filter by category</li>
                    <li>✓ See featured & verified businesses</li>
                </ul>
                <a href="/pages/search-with-filters.php" class="cta-btn">Go to Search →</a>
            </div>

            <!-- CLAIM BUSINESS SECTION -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>📋 Claim Your Business</h2>
                    <p>Verify your business and unlock premium features</p>
                </div>
                <ul class="feature-list">
                    <li>✓ Get verified badge ✔</li>
                    <li>✓ Higher ranking in search</li>
                    <li>✓ Free plan included</li>
                    <li>✓ Edit business info</li>
                </ul>
                <a href="/pages/claim-business.php" class="cta-btn">Claim Business →</a>
            </div>

            <!-- UPGRADE PLAN SECTION -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>💰 Upgrade Plan</h2>
                    <p>Choose a plan to grow your business visibility</p>
                </div>
                <ul class="feature-list">
                    <li>✓ Basic Plan - ₹499/month</li>
                    <li>✓ Premium Plan - ₹999/month</li>
                    <li>✓ Featured listing option</li>
                    <li>✓ Priority support</li>
                </ul>
                <a href="/pages/upgrade-plan.php" class="cta-btn">View Plans →</a>
            </div>

            <!-- VOICE AI CHATBOT -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>🤖 AI Sales Assistant</h2>
                    <p>Talk to our intelligent chatbot</p>
                </div>
                <ul class="feature-list">
                    <li>✓ Voice & text chat</li>
                    <li>✓ Hindi & English support</li>
                    <li>✓ Smart responses</li>
                    <li>✓ Automatic lead capture</li>
                </ul>
                <p style="color: #666; margin-top: 15px; font-size: 12px;">💬 Click the chatbot button at bottom-right to start chatting</p>
            </div>

            <!-- PRICING PLANS PREVIEW -->
            <div class="dashboard-card plans-preview">
                <div class="card-header">
                    <h2>💳 Pricing Plans</h2>
                </div>
                <div class="plans-mini-grid">
                    <div class="plan-mini">
                        <h4>📌 Free</h4>
                        <div class="price">₹0</div>
                        <p>Basic listing</p>
                    </div>
                    <div class="plan-mini">
                        <h4>💼 Basic</h4>
                        <div class="price">₹499<span>/mo</span></div>
                        <p>Verified + higher rank</p>
                    </div>
                    <div class="plan-mini">
                        <h4>🔥 Premium</h4>
                        <div class="price">₹999<span>/mo</span></div>
                        <p>Featured + top rank</p>
                    </div>
                </div>
                <a href="/pages/upgrade-plan.php" class="cta-btn" style="margin-top: 15px;">See All Plans →</a>
            </div>

            <!-- INFORMATION -->
            <div class="dashboard-card info-card">
                <div class="card-header">
                    <h2>ℹ️ About ConnectWith9</h2>
                </div>
                <ul class="feature-list">
                    <li>📂 Largest business directory in India</li>
                    <li>🔍 Smart search technology</li>
                    <li>⭐ Verified & rated businesses</li>
                    <li>🤖 AI-powered recommendations</li>
                    <li>📱 Mobile-friendly platform</li>
                    <li>🚀 Growing SaaS platform</li>
                </ul>
            </div>
        </div>

        <!-- HOW IT WORKS -->
        <div class="how-it-works">
            <h2>📖 How It Works</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Search</h3>
                    <p>Find businesses by category and location</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Claim</h3>
                    <p>Own your business listing</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Upgrade</h3>
                    <p>Get featured and reach more customers</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3>Grow</h3>
                    <p>Track analytics and grow your business</p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .user-dashboard-section {
        padding: 40px 0;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }

    .container h1 {
        color: #0B1C3D;
        text-align: center;
        margin-bottom: 10px;
        font-size: 32px;
    }

    .subtitle {
        text-align: center;
        color: #666;
        margin-bottom: 40px;
        font-size: 16px;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
        margin-bottom: 40px;
    }

    .dashboard-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transition: transform 0.2s, box-shadow 0.2s;
        border-top: 4px solid #FF6A00;
    }

    .dashboard-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    }

    .card-header h2 {
        color: #0B1C3D;
        margin: 0 0 8px 0;
        font-size: 18px;
    }

    .card-header p {
        color: #666;
        margin: 0;
        font-size: 13px;
    }

    .feature-list {
        list-style: none;
        padding: 15px 0;
        margin: 0;
    }

    .feature-list li {
        color: #333;
        padding: 8px 0;
        font-size: 14px;
    }

    .cta-btn {
        display: inline-block;
        margin-top: 15px;
        padding: 12px 20px;
        background: linear-gradient(to right, #FF6A00, #FF8533);
        color: white;
        text-decoration: none;
        border-radius: 6px;
        font-weight: bold;
        transition: all 0.2s;
    }

    .cta-btn:hover {
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(255, 106, 0, 0.3);
    }

    .plans-mini-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin: 15px 0;
    }

    .plan-mini {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
    }

    .plan-mini h4 {
        color: #0B1C3D;
        margin: 0 0 8px 0;
        font-size: 14px;
    }

    .plan-mini .price {
        font-size: 20px;
        font-weight: bold;
        color: #FF6A00;
        margin: 8px 0;
    }

    .plan-mini .price span {
        font-size: 12px;
        color: #666;
    }

    .plan-mini p {
        font-size: 12px;
        color: #666;
        margin: 0;
    }

    .info-card {
        border-top-color: #1E3A8A;
    }

    .how-it-works {
        background: white;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        margin-top: 40px;
    }

    .how-it-works h2 {
        color: #0B1C3D;
        text-align: center;
        margin-bottom: 30px;
    }

    .steps {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    .step {
        text-align: center;
    }

    .step-number {
        display: inline-block;
        width: 50px;
        height: 50px;
        background: #FF6A00;
        color: white;
        border-radius: 50%;
        line-height: 50px;
        font-weight: bold;
        font-size: 18px;
        margin-bottom: 15px;
    }

    .step h3 {
        color: #0B1C3D;
        margin: 0 0 10px 0;
    }

    .step p {
        color: #666;
        font-size: 14px;
        margin: 0;
    }

    @media (max-width: 768px) {
        .plans-mini-grid {
            grid-template-columns: 1fr;
        }

        .dashboard-grid {
            grid-template-columns: 1fr;
        }

        .container h1 {
            font-size: 24px;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>
