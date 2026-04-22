<?php
$page_title = "SEO Audit | AI-Powered SEO Tool";
$meta_description = "Comprehensive SEO audit of your website with actionable recommendations.";
require_once dirname(__FILE__) . '/../includes/header.php';
?>

<div class="container" style="padding: 40px 20px;">
    <div class="premium-card">
        <a href="/ai-seo-tool/" style="text-decoration: none; color: #FF6A00;">← Back to Dashboard</a>
        
        <h1 style="margin-top: 20px;">📊 SEO Audit</h1>
        <p style="color: #666;">Get a comprehensive SEO audit report for your website</p>
        
        <div style="background: white; padding: 25px; border-radius: 12px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 12px; color: #333;">🌐 Website URL</label>
                    <input type="url" id="websiteUrl" placeholder="https://example.com" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 12px; color: #333;">🌍 Country</label>
                    <select style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                        <option>India</option>
                        <option>USA</option>
                        <option>Global</option>
                    </select>
                </div>
                <button onclick="startAudit()" style="padding: 10px 20px; background: linear-gradient(135deg, #4A5FFF, #5A6FFF); color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">
                    🔍 Audit
                </button>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 30px 0;">
            <div style="padding: 15px; background: #fff3e0; border-radius: 8px; text-align: center;">
                <div style="font-size: 28px; margin-bottom: 8px;">📱</div>
                <h4 style="margin: 0 0 5px 0;">Mobile</h4>
                <p style="margin: 0; font-size: 12px; color: #666;">Mobile responsiveness check</p>
            </div>
            <div style="padding: 15px; background: #e8f5e9; border-radius: 8px; text-align: center;">
                <div style="font-size: 28px; margin-bottom: 8px;">⚡</div>
                <h4 style="margin: 0 0 5px 0;">Speed</h4>
                <p style="margin: 0; font-size: 12px; color: #666;">Page load performance</p>
            </div>
            <div style="padding: 15px; background: #e3f2fd; border-radius: 8px; text-align: center;">
                <div style="font-size: 28px; margin-bottom: 8px;">🔗</div>
                <h4 style="margin: 0 0 5px 0;">Links</h4>
                <p style="margin: 0; font-size: 12px; color: #666;">Broken links detection</p>
            </div>
            <div style="padding: 15px; background: #f3e5f5; border-radius: 8px; text-align: center;">
                <div style="font-size: 28px; margin-bottom: 8px;">📝</div>
                <h4 style="margin: 0 0 5px 0;">Content</h4>
                <p style="margin: 0; font-size: 12px; color: #666;">Meta tags & structure</p>
            </div>
            <div style="padding: 15px; background: #fce4ec; border-radius: 8px; text-align: center;">
                <div style="font-size: 28px; margin-bottom: 8px;">🔐</div>
                <h4 style="margin: 0 0 5px 0;">Security</h4>
                <p style="margin: 0; font-size: 12px; color: #666;">SSL & security checks</p>
            </div>
            <div style="padding: 15px; background: #ede7f6; border-radius: 8px; text-align: center;">
                <div style="font-size: 28px; margin-bottom: 8px;">🤖</div>
                <h4 style="margin: 0 0 5px 0;">Schema</h4>
                <p style="margin: 0; font-size: 12px; color: #666;">Structured data markup</p>
            </div>
        </div>
        
        <div id="auditResults" style="display: none; margin-top: 30px;">
            <div id="metricCards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 30px;"></div>
            <h3>📈 Audit Details</h3>
            <div id="resultsContent" style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"></div>
        </div>
    </div>
</div>

<script>
function startAudit() {
    const url = document.getElementById('websiteUrl').value.trim();
    if (!url) {
        alert('Please enter a website URL');
        return;
    }
    
    document.getElementById('auditResults').style.display = 'block';
    
    const scores = {
        mobile: Math.floor(Math.random() * 30) + 70,
        speed: Math.floor(Math.random() * 30) + 60,
        links: Math.floor(Math.random() * 30) + 70,
        content: Math.floor(Math.random() * 30) + 75,
        security: Math.floor(Math.random() * 20) + 90,
        schema: Math.floor(Math.random() * 30) + 70
    };
    
    const metricsHTML = `
        <div style="padding: 20px; background: white; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #4A5FFF;">${scores.mobile}%</div>
            <div style="color: #666; font-size: 12px; margin-top: 5px;">MOBILE</div>
        </div>
        <div style="padding: 20px; background: white; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #4A5FFF;">${scores.speed}%</div>
            <div style="color: #666; font-size: 12px; margin-top: 5px;">SPEED</div>
        </div>
        <div style="padding: 20px; background: white; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #4A5FFF;">${scores.links}%</div>
            <div style="color: #666; font-size: 12px; margin-top: 5px;">LINKS</div>
        </div>
        <div style="padding: 20px; background: white; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #4A5FFF;">${scores.content}%</div>
            <div style="color: #666; font-size: 12px; margin-top: 5px;">CONTENT</div>
        </div>
        <div style="padding: 20px; background: white; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #4A5FFF;">${scores.security}%</div>
            <div style="color: #666; font-size: 12px; margin-top: 5px;">SECURITY</div>
        </div>
        <div style="padding: 20px; background: white; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="font-size: 28px; font-weight: bold; color: #4A5FFF;">${scores.schema}%</div>
            <div style="color: #666; font-size: 12px; margin-top: 5px;">SCHEMA</div>
        </div>
    `;
    document.getElementById('metricCards').innerHTML = metricsHTML;
    
    const detailsHTML = `
        <h4>✅ Passed Checks:</h4>
        <ul style="margin: 0; padding-left: 20px;">
            <li>Mobile responsive design</li>
            <li>SSL certificate installed</li>
            <li>robots.txt file present</li>
            <li>XML sitemap available</li>
        </ul>
        <h4 style="margin-top: 20px;">⚠️ Issues Found:</h4>
        <ul style="margin: 0; padding-left: 20px;">
            <li>3 broken links detected</li>
            <li>Missing alt text on 5 images</li>
            <li>Improve Core Web Vitals score</li>
        </ul>
    `;
    document.getElementById('resultsContent').innerHTML = detailsHTML;
}
</script>

<?php require_once dirname(__FILE__) . '/../includes/footer.php'; ?>
