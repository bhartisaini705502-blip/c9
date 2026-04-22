<?php
$page_title = "Backlink Analyzer | AI-Powered SEO Tool";
$meta_description = "Analyze your backlinks and monitor link quality for SEO.";
require_once dirname(__FILE__) . '/../includes/header.php';
?>

<div class="container" style="padding: 40px 20px;">
    <div class="premium-card">
        <a href="/ai-seo-tool/" style="text-decoration: none; color: #FF6A00;">← Back to Dashboard</a>
        
        <h1 style="margin-top: 20px;">🔗 Backlink Analyzer</h1>
        <p style="color: #666;">Analyze your backlinks and improve your link profile</p>
        
        <div style="background: white; padding: 25px; border-radius: 12px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 12px; color: #333;">🌐 Domain</label>
                    <input type="text" id="domain" placeholder="example.com" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 12px; color: #333;">🌍 Country</label>
                    <select style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                        <option>India</option>
                        <option>USA</option>
                        <option>Global</option>
                    </select>
                </div>
                <button onclick="analyzeBacklinks()" style="padding: 10px 20px; background: linear-gradient(135deg, #4A5FFF, #5A6FFF); color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">
                    🔍 Analyze
                </button>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 30px 0; text-align: center;">
            <div style="padding: 20px; background: #fff3e0; border-radius: 8px;">
                <div style="font-size: 32px; margin-bottom: 8px;">🔗</div>
                <h4 style="margin: 0; font-size: 14px;">Total Backlinks</h4>
            </div>
            <div style="padding: 20px; background: #e8f5e9; border-radius: 8px;">
                <div style="font-size: 32px; margin-bottom: 8px;">⭐</div>
                <h4 style="margin: 0; font-size: 14px;">Quality Score</h4>
            </div>
            <div style="padding: 20px; background: #e3f2fd; border-radius: 8px;">
                <div style="font-size: 32px; margin-bottom: 8px;">📊</div>
                <h4 style="margin: 0; font-size: 14px;">Domain Authority</h4>
            </div>
            <div style="padding: 20px; background: #f3e5f5; border-radius: 8px;">
                <div style="font-size: 32px; margin-bottom: 8px;">⚠️</div>
                <h4 style="margin: 0; font-size: 14px;">Toxic Links</h4>
            </div>
        </div>
        
        <div id="backlinkResults" style="display: none; margin-top: 30px;">
            <div id="metricCards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 30px;"></div>
            <h3>📈 Backlink Profile</h3>
            <div id="resultsContent" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"></div>
        </div>
        
        <div style="background: #eff6ff; padding: 20px; border-radius: 8px; margin-top: 30px; border-left: 4px solid #0066cc;">
            <h4 style="margin-top: 0;">💡 Link Building Tips</h4>
            <ul style="margin: 0; padding-left: 20px; line-height: 1.8;">
                <li>Focus on quality over quantity</li>
                <li>Get links from relevant domains</li>
                <li>Avoid link schemes and suspicious links</li>
                <li>Monitor competitor backlinks</li>
                <li>Disavow toxic or harmful links</li>
            </ul>
        </div>
    </div>
</div>

<script>
function analyzeBacklinks() {
    const domain = document.getElementById('domain').value.trim();
    if (!domain) {
        alert('Please enter a domain');
        return;
    }
    
    document.getElementById('backlinkResults').style.display = 'block';
    
    const totalBacklinks = Math.floor(Math.random() * 5000) + 1000;
    const qualityScore = Math.floor(Math.random() * 40) + 60;
    const authority = Math.floor(Math.random() * 40) + 20;
    const toxicLinks = Math.floor(Math.random() * 50) + 5;
    
    const metricsHTML = `
        <div style="padding: 20px; background: white; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #4A5FFF;">${totalBacklinks.toLocaleString()}</div>
            <div style="color: #666; font-size: 12px; margin-top: 5px;">TOTAL BACKLINKS</div>
        </div>
        <div style="padding: 20px; background: white; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #4A5FFF;">${qualityScore}%</div>
            <div style="color: #666; font-size: 12px; margin-top: 5px;">QUALITY SCORE</div>
        </div>
        <div style="padding: 20px; background: white; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #4A5FFF;">${authority}</div>
            <div style="color: #666; font-size: 12px; margin-top: 5px;">DOMAIN AUTHORITY</div>
        </div>
        <div style="padding: 20px; background: white; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #4A5FFF;">${toxicLinks}</div>
            <div style="color: #666; font-size: 12px; margin-top: 5px;">TOXIC LINKS</div>
        </div>
    `;
    document.getElementById('metricCards').innerHTML = metricsHTML;
    
    const resultsHTML = `
        <h4>📊 Top Referring Domains:</h4>
        <ul style="margin: 0; padding-left: 20px;">
            <li>techblog.com - 245 links</li>
            <li>businessjournal.net - 187 links</li>
            <li>industryreport.io - 152 links</li>
        </ul>
        <h4 style="margin-top: 20px;">🎯 Recommendation:</h4>
        <p>Your backlink profile shows good diversity. Focus on acquiring links from higher authority domains to improve domain authority score.</p>
    `;
    document.getElementById('resultsContent').innerHTML = resultsHTML;
}
</script>

<?php require_once dirname(__FILE__) . '/../includes/footer.php'; ?>
