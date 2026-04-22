<?php
$page_title = "Contact ConnectWith – Let's Grow Your Business";
$meta_description = "Contact ConnectWith for digital marketing services. Call, WhatsApp, or email us for business growth solutions across India.";
$meta_keywords = "contact us, ConnectWith, digital marketing support, business growth";

require_once '../includes/header.php';
?>

<main>
    <!-- Hero Section -->
    <section style="background: linear-gradient(135deg, #0B1C3D 0%, #1E3A8A 100%); color: white; padding: 60px 0;">
        <div class="container">
            <div style="max-width: 800px;">
                <h1 style="font-size: 42px; font-weight: 700; margin-bottom: 20px; line-height: 1.2;">
                    Contact ConnectWith – Let's Grow Your Business
                </h1>
                <p style="font-size: 18px; margin-bottom: 30px; line-height: 1.6; color: rgba(255, 255, 255, 0.9);">
                    Have questions or want to grow your business? Get in touch with us today. Our team is ready to help you with the best solutions.
                </p>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <a href="tel:09068899033" onclick="gtag('event','conversion',{'send_to':'AW-XXXXXXXXX/call'})" style="background: #FF6A00; color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s;">📞 Call Now</a>
                    <a href="https://wa.me/919068899033" target="_blank" onclick="gtag('event','conversion',{'send_to':'AW-XXXXXXXXX/whatsapp'})" style="background: #25D366; color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s;">💬 WhatsApp Now</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content Area -->
    <section style="padding: 60px 0;">
        <div class="container">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px;">
                <!-- Left Content -->
                <div>
                    <!-- Contact Details -->
                    <section style="margin-bottom: 50px;">
                        <h2 style="font-size: 32px; color: #0B1C3D; margin-bottom: 30px; font-weight: 700;">Get in Touch</h2>
                        <div style="background: #f8f9fa; padding: 30px; border-radius: 12px; border: 1px solid #ddd;">
                            <p style="font-size: 16px; margin-bottom: 20px; color: #333;">
                                <strong style="color: #0B1C3D; display: block; margin-bottom: 8px;">📞 Phone</strong>
                                <a href="tel:09068899033" style="color: #FF6A00; text-decoration: none; font-weight: 600;">09068899033</a>
                            </p>
                            <p style="font-size: 16px; margin-bottom: 20px; color: #333;">
                                <strong style="color: #0B1C3D; display: block; margin-bottom: 8px;">💬 WhatsApp</strong>
                                <a href="https://wa.me/919068899033" target="_blank" style="color: #25D366; text-decoration: none; font-weight: 600;">09068899033</a>
                            </p>
                            <p style="font-size: 16px; color: #333;">
                                <strong style="color: #0B1C3D; display: block; margin-bottom: 8px;">📧 Email</strong>
                                <a href="mailto:info@connectwith.in" style="color: #FF6A00; text-decoration: none; font-weight: 600;">info@connectwith.in</a>
                            </p>
                        </div>
                    </section>

                    <!-- Contact Form -->
                    <section style="margin-bottom: 50px;">
                        <h2 style="font-size: 32px; color: #0B1C3D; margin-bottom: 30px; font-weight: 700;">Send Us a Message</h2>
                        <form method="POST" action="send-mail.php" style="background: #f8f9fa; padding: 30px; border-radius: 12px; border: 1px solid #ddd;">
                            <div style="margin-bottom: 20px;">
                                <input type="text" name="name" placeholder="Your Name" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; font-family: inherit; box-sizing: border-box;" required>
                            </div>
                            <div style="margin-bottom: 20px;">
                                <input type="text" name="phone" placeholder="Phone Number" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; font-family: inherit; box-sizing: border-box;" required>
                            </div>
                            <div style="margin-bottom: 20px;">
                                <input type="email" name="email" placeholder="Email Address" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; font-family: inherit; box-sizing: border-box;">
                            </div>
                            <div style="margin-bottom: 20px;">
                                <textarea name="message" placeholder="Your Message" rows="6" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; font-family: inherit; box-sizing: border-box; resize: vertical;"></textarea>
                            </div>
                            <input type="hidden" name="service" value="General Inquiry">
                            <input type="hidden" name="source" value="contact-page">
                            <button type="submit" style="background: #FF6A00; color: white; padding: 12px 30px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; width: 100%; transition: all 0.3s;" onclick="gtag('event','conversion',{'send_to':'AW-XXXXXXXXX/form_submit'})">Send Message</button>
                        </form>
                    </section>

                    <!-- Location Section -->
                    <section style="margin-bottom: 50px;">
                        <h2 style="font-size: 32px; color: #0B1C3D; margin-bottom: 25px; font-weight: 700;">Our Location</h2>
                        <p style="font-size: 16px; line-height: 1.8; color: #333;">
                            Serving clients across India with professional digital marketing and business growth solutions. Whether you're in major cities or tier-2 towns, we're here to support your business growth journey.
                        </p>
                    </section>

                    <!-- CTA Section -->
                    <section style="background: #f8f9fa; padding: 40px; border-radius: 12px; border: 1px solid #ddd; text-align: center;">
                        <h2 style="font-size: 28px; color: #0B1C3D; margin-bottom: 15px; font-weight: 700;">Need Immediate Assistance?</h2>
                        <p style="font-size: 16px; color: #666; margin-bottom: 25px;">
                            Call or WhatsApp us now for quick support. Our team is available to answer your questions.
                        </p>
                        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                            <a href="tel:09068899033" style="background: #FF6A00; color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s;">📞 Call Now</a>
                            <a href="https://wa.me/919068899033" target="_blank" style="background: #25D366; color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s;">💬 WhatsApp Now</a>
                        </div>
                    </section>
                </div>

                <!-- Right Sidebar -->
                <div>
                    <!-- Quick Contact Card -->
                    <div style="background: linear-gradient(135deg, #FF6A00 0%, #E55A00 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; position: sticky; top: 20px;">
                        <h3 style="font-size: 22px; font-weight: 700; margin-bottom: 20px; text-align: center;">📞 Quick Contact</h3>
                        <a href="tel:09068899033" onclick="gtag('event','conversion',{'send_to':'AW-XXXXXXXXX/call'})" style="display: block; background: white; color: #FF6A00; padding: 12px; border-radius: 6px; text-decoration: none; font-weight: 600; text-align: center; margin-bottom: 10px; transition: all 0.3s;">📞 Call Now</a>
                        <a href="https://wa.me/919068899033" target="_blank" onclick="gtag('event','conversion',{'send_to':'AW-XXXXXXXXX/whatsapp'})" style="display: block; background: #25D366; color: white; padding: 12px; border-radius: 6px; text-decoration: none; font-weight: 600; text-align: center; transition: all 0.3s;">💬 WhatsApp Now</a>
                    </div>

                    <!-- Callback Form -->
                    <div style="background: #f8f9fa; padding: 25px; border-radius: 12px; border: 1px solid #ddd;">
                        <h3 style="font-size: 18px; color: #0B1C3D; font-weight: 700; margin-bottom: 20px;">Request Callback</h3>
                        <form method="POST" action="send-mail.php" style="display: flex; flex-direction: column; gap: 15px;">
                            <input type="text" name="name" placeholder="Your Name" style="padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; font-family: inherit;" required>
                            <input type="text" name="phone" placeholder="Phone Number" style="padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; font-family: inherit;" required>
                            <input type="hidden" name="service" value="Callback Request">
                            <input type="hidden" name="source" value="contact-page-sidebar">
                            <button type="submit" style="background: #FF6A00; color: white; padding: 12px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; transition: all 0.3s;" onclick="gtag('event','conversion',{'send_to':'AW-XXXXXXXXX/form_submit'})">Submit</button>
                        </form>
                    </div>

                    <!-- Response Time Info -->
                    <div style="background: linear-gradient(135deg, #1E3A8A 0%, #0B1C3D 100%); color: white; padding: 20px; border-radius: 12px; margin-top: 30px; text-align: center;">
                        <p style="font-size: 14px; margin: 0;">
                            <strong>⚡ Response Time</strong><br>
                            We typically respond within 2 hours during business hours.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA Section -->
    <section style="background: linear-gradient(135deg, #0B1C3D 0%, #1E3A8A 100%); color: white; padding: 60px 0; text-align: center;">
        <div class="container">
            <h2 style="font-size: 36px; font-weight: 700; margin-bottom: 20px;">Ready to Grow Your Business?</h2>
            <p style="font-size: 18px; margin-bottom: 30px; color: rgba(255, 255, 255, 0.9);">
                Connect with ConnectWith today and let's take your business to the next level.
            </p>
            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <a href="tel:09068899033" onclick="gtag('event','conversion',{'send_to':'AW-XXXXXXXXX/call'})" style="background: #FF6A00; color: white; padding: 14px 35px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s; display: inline-block;">📞 Call Now</a>
                <a href="https://wa.me/919068899033" target="_blank" onclick="gtag('event','conversion',{'send_to':'AW-XXXXXXXXX/whatsapp'})" style="background: #25D366; color: white; padding: 14px 35px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s; display: inline-block;">💬 WhatsApp Now</a>
                <a href="/pages/about.php" style="background: rgba(255, 255, 255, 0.2); color: white; padding: 14px 35px; border-radius: 6px; text-decoration: none; font-weight: 600; border: 2px solid white; transition: all 0.3s; display: inline-block;">Learn More</a>
            </div>
        </div>
    </section>
</main>

<?php require_once '../includes/footer.php'; ?>
