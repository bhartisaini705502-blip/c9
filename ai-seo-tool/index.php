<?php
/**
 * AI-Powered SEO Tool
 * Main entry point for the integrated Asset Manager tool
 */

// Set page title and metadata
$page_title = "AI-Powered SEO Tool | ConnectWith9";
$meta_description = "Advanced AI-powered SEO analysis, optimization, and asset management tool for businesses.";
$meta_keywords = "SEO tool, AI analysis, keyword research, content optimization, asset manager";

// Include header
require_once dirname(__FILE__) . '/../includes/header.php';
?>

<div class="container" style="padding: 40px 20px;">
    <div class="premium-card">
        <h1 style="text-align: center; margin-bottom: 10px;">✨ AI-Powered SEO Tool</h1>
        <p style="text-align: center; color: #666; margin-bottom: 40px;">
            Advanced AI analysis, optimization, and asset management for your business
        </p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0;">
            <!-- Keyword Research Card -->
            <a href="/ai-seo-tool/keyword-research.php" style="text-decoration: none; color: inherit;">
                <div style="padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; color: white; cursor: pointer; transition: transform 0.3s ease;">
                    <div style="font-size: 32px; margin-bottom: 10px;">🔍</div>
                    <h3 style="margin: 0 0 8px 0;">Keyword Research</h3>
                    <p style="margin: 0; font-size: 14px; opacity: 0.9;">AI-powered keyword analysis & suggestions</p>
                </div>
            </a>
            
            <!-- Content Optimizer Card -->
            <a href="/ai-seo-tool/content-optimizer.php" style="text-decoration: none; color: inherit;">
                <div style="padding: 20px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 8px; color: white; cursor: pointer; transition: transform 0.3s ease;">
                    <div style="font-size: 32px; margin-bottom: 10px;">✍️</div>
                    <h3 style="margin: 0 0 8px 0;">Content Optimizer</h3>
                    <p style="margin: 0; font-size: 14px; opacity: 0.9;">Optimize content for SEO & readability</p>
                </div>
            </a>
            
            <!-- Backlink Analyzer Card -->
            <a href="/ai-seo-tool/backlink-analyzer.php" style="text-decoration: none; color: inherit;">
                <div style="padding: 20px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 8px; color: white; cursor: pointer; transition: transform 0.3s ease;">
                    <div style="font-size: 32px; margin-bottom: 10px;">🔗</div>
                    <h3 style="margin: 0 0 8px 0;">Backlink Analyzer</h3>
                    <p style="margin: 0; font-size: 14px; opacity: 0.9;">Analyze backlinks & link quality</p>
                </div>
            </a>
            
            <!-- Asset Manager Card -->
            <a href="/ai-seo-tool/asset-manager.php" style="text-decoration: none; color: inherit;">
                <div style="padding: 20px; background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); border-radius: 8px; color: white; cursor: pointer; transition: transform 0.3s ease;">
                    <div style="font-size: 32px; margin-bottom: 10px;">📦</div>
                    <h3 style="margin: 0 0 8px 0;">Asset Manager</h3>
                    <p style="margin: 0; font-size: 14px; opacity: 0.9;">Manage digital assets & media</p>
                </div>
            </a>
            
            <!-- SEO Audit Card -->
            <a href="/ai-seo-tool/seo-audit.php" style="text-decoration: none; color: inherit;">
                <div style="padding: 20px; background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); border-radius: 8px; color: white; cursor: pointer; transition: transform 0.3s ease;">
                    <div style="font-size: 32px; margin-bottom: 10px;">📊</div>
                    <h3 style="margin: 0 0 8px 0;">SEO Audit</h3>
                    <p style="margin: 0; font-size: 14px; opacity: 0.9;">Comprehensive website SEO audit</p>
                </div>
            </a>
            
            <!-- Performance Monitor Card -->
            <a href="/ai-seo-tool/performance-monitor.php" style="text-decoration: none; color: inherit;">
                <div style="padding: 20px; background: linear-gradient(135deg, #ff9a56 0%, #ff6a88 100%); border-radius: 8px; color: white; cursor: pointer; transition: transform 0.3s ease;">
                    <div style="font-size: 32px; margin-bottom: 10px;">⚡</div>
                    <h3 style="margin: 0 0 8px 0;">Performance Monitor</h3>
                    <p style="margin: 0; font-size: 14px; opacity: 0.9;">Track SEO metrics & rankings</p>
                </div>
            </a>
        </div>
        
        <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin-top: 30px;">
            <h3 style="margin-top: 0;">Getting Started</h3>
            <ul style="line-height: 1.8;">
                <li>🔍 <strong>Keyword Research:</strong> Discover high-volume keywords for your niche</li>
                <li>✍️ <strong>Content Optimization:</strong> Improve your content's SEO score</li>
                <li>🔗 <strong>Backlink Analysis:</strong> Monitor your link profile</li>
                <li>📦 <strong>Asset Management:</strong> Organize and manage digital resources</li>
                <li>📊 <strong>SEO Audit:</strong> Get actionable recommendations</li>
                <li>⚡ <strong>Performance Tracking:</strong> Monitor your SEO progress</li>
            </ul>
        </div>
    </div>
</div>

<style>
.premium-card a {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.premium-card a:hover > div {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

@media (max-width: 768px) {
    .premium-card {
        padding: 16px;
    }
}
</style>

<?php require_once dirname(__FILE__) . '/../includes/footer.php'; ?>
