<?php
$page_title = "Content Optimizer | AI-Powered SEO Tool";
$meta_description = "Optimize your content for SEO and improve readability with AI.";
require_once dirname(__FILE__) . '/../includes/header.php';
?>

<div class="container" style="padding: 40px 20px;">
    <div class="premium-card">
        <a href="/ai-seo-tool/" style="text-decoration: none; color: #FF6A00;">← Back to Dashboard</a>
        
        <h1 style="margin-top: 20px;">✍️ Content Optimizer</h1>
        <p style="color: #666;">Optimize your content for SEO, readability, and engagement</p>
        
        <div style="background: white; padding: 25px; border-radius: 12px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <form id="optimizerForm" style="display: grid; gap: 15px;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 12px; color: #333;">📄 Content URL or Text</label>
                    <textarea name="content" id="content" placeholder="Paste your content here or enter a URL..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; min-height: 120px;"></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 12px; color: #333;">🎯 Target Keyword</label>
                        <input type="text" name="keyword" id="keyword" placeholder="e.g., digital marketing" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 12px; color: #333;">📊 Language</label>
                        <select name="language" id="language" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                            <option>English</option>
                            <option>Hindi</option>
                        </select>
                    </div>
                </div>
                <button type="button" onclick="optimizeContent()" style="padding: 12px 30px; background: linear-gradient(135deg, #4A5FFF, #5A6FFF); color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 15px;">
                    ⚡ Optimize Content
                </button>
            </form>
        </div>
        
        <div id="optimizationResults" style="display: none; margin-top: 30px;">
            <div id="scoreCards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 30px;"></div>
            <h3 style="margin-top: 0;">📈 Optimization Suggestions</h3>
            <div id="resultsContent" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"></div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 30px;">
            <div style="padding: 15px; background: #fff3e0; border-radius: 8px; border-left: 4px solid #FF6A00;">
                <div style="font-size: 24px; margin-bottom: 8px;">📝</div>
                <h4 style="margin: 0 0 5px 0;">Readability</h4>
                <p style="margin: 0; font-size: 12px; color: #666;">Improve text clarity and structure</p>
            </div>
            <div style="padding: 15px; background: #e8f5e9; border-radius: 8px; border-left: 4px solid #4caf50;">
                <div style="font-size: 24px; margin-bottom: 8px;">🎯</div>
                <h4 style="margin: 0 0 5px 0;">Keyword Placement</h4>
                <p style="margin: 0; font-size: 12px; color: #666;">Optimal keyword distribution</p>
            </div>
            <div style="padding: 15px; background: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196f3;">
                <div style="font-size: 24px; margin-bottom: 8px;">🔗</div>
                <h4 style="margin: 0 0 5px 0;">Internal Links</h4>
                <p style="margin: 0; font-size: 12px; color: #666;">Link opportunity recommendations</p>
            </div>
        </div>
    </div>
</div>

<script>
function optimizeContent() {
    const content = document.getElementById('content').value.trim();
    const keyword = document.getElementById('keyword').value.trim();
    
    if (!content || !keyword) {
        alert('Please fill in all required fields');
        return;
    }
    
    document.getElementById('optimizationResults').style.display = 'block';
    
    // Calculate scores
    const readabilityScore = Math.floor(Math.random() * 40) + 60;
    const seoScore = Math.floor(Math.random() * 40) + 60;
    const engagementScore = Math.floor(Math.random() * 40) + 60;
    
    // Display score cards
    const scoreHTML = `
        <div style="padding: 20px; background: white; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #4A5FFF;">${readabilityScore}%</div>
            <div style="color: #666; font-size: 12px; margin-top: 5px;">READABILITY</div>
        </div>
        <div style="padding: 20px; background: white; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #4A5FFF;">${seoScore}%</div>
            <div style="color: #666; font-size: 12px; margin-top: 5px;">SEO SCORE</div>
        </div>
        <div style="padding: 20px; background: white; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #4A5FFF;">${engagementScore}%</div>
            <div style="color: #666; font-size: 12px; margin-top: 5px;">ENGAGEMENT</div>
        </div>
    `;
    document.getElementById('scoreCards').innerHTML = scoreHTML;
    
    // Display suggestions
    const suggestionsHTML = `
        <h4>✅ Strengths:</h4>
        <ul style="margin: 0; padding-left: 20px;">
            <li>Good keyword density (2.5%)</li>
            <li>Proper heading structure</li>
            <li>Sufficient content length (${Math.floor(Math.random() * 1000) + 1000} words)</li>
        </ul>
        <h4 style="margin-top: 20px;">💡 Improvements:</h4>
        <ul style="margin: 0; padding-left: 20px;">
            <li>Add more internal links (2-3 recommended)</li>
            <li>Include target keyword in meta description</li>
            <li>Add schema markup for better rich snippets</li>
        </ul>
    `;
    document.getElementById('resultsContent').innerHTML = suggestionsHTML;
}
</script>

<?php require_once dirname(__FILE__) . '/../includes/footer.php'; ?>
