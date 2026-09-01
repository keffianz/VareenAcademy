<?php
// Home/Landing Page
?>
<div class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1>Welcome to VEREEN Academy</h1>
            <p>Learn anything, anytime, anywhere with world-class courses and expert instructors</p>
            
            <?php if (!isLoggedIn()): ?>
                <div class="hero-buttons">
                    <a href="/index.php?page=signup" class="btn btn-primary btn-large">
                        <i class="fas fa-user-plus"></i> Get Started Free
                    </a>
                    <a href="/index.php?page=courses" class="btn btn-outline-primary btn-large">
                        <i class="fas fa-graduation-cap"></i> Browse Courses
                    </a>
                </div>
            <?php else: ?>
                <div class="hero-buttons">
                    <a href="/index.php?page=<?php echo getCurrentUserRole() === 'student' ? 'student-dashboard' : 'teacher-dashboard'; ?>" 
                       class="btn btn-primary btn-large">
                        <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<section class="features-section">
    <div class="container">
        <h2 class="section-title">Why Choose VEREEN Academy?</h2>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-video"></i>
                </div>
                <h3>Quality Content</h3>
                <p>Learn from expertly crafted video lessons and interactive course materials.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Learn at Your Pace</h3>
                <p>Flexible learning schedule that fits your lifestyle. Pause, rewind, and replay as needed.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-certificate"></i>
                </div>
                <h3>Earn Certificates</h3>
                <p>Complete courses and earn recognized certificates to boost your career.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Community Support</h3>
                <p>Connect with instructors and peers in our active learning community.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3>Mobile Learning</h3>
                <p>Learn on any device - desktop, tablet, or smartphone. Seamless experience everywhere.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3>24/7 Support</h3>
                <p>Get help whenever you need it. Our dedicated support team is always ready to assist.</p>
            </div>
        </div>
    </div>
</section>

<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <h3>1000+</h3>
                <p>Active Students</p>
            </div>

            <div class="stat-card">
                <h3>50+</h3>
                <p>Expert Instructors</p>
            </div>

            <div class="stat-card">
                <h3>100+</h3>
                <p>Quality Courses</p>
            </div>

            <div class="stat-card">
                <h3>95%</h3>
                <p>Satisfaction Rate</p>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <h2>Ready to Start Learning?</h2>
        <p>Join thousands of students already learning on VEREEN Academy</p>
        <?php if (!isLoggedIn()): ?>
            <a href="/index.php?page=signup" class="btn btn-primary btn-large">
                Sign Up Now - It's Free!
            </a>
        <?php endif; ?>
    </div>
</section>

<style>
    /* Hero Section */
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 100px 0;
        text-align: center;
    }

    .hero-content h1 {
        font-size: 48px;
        font-weight: 700;
        margin-bottom: 20px;
        line-height: 1.2;
    }

    .hero-content p {
        font-size: 18px;
        margin-bottom: 40px;
        opacity: 0.95;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    .hero-buttons {
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .hero-buttons .btn {
        min-width: 200px;
    }

    /* Features Section */
    .features-section {
        padding: 80px 0;
        background: #f8f9fa;
    }

    .section-title {
        text-align: center;
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 60px;
        color: #333;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 30px;
    }

    .feature-card {
        background: white;
        padding: 40px 30px;
        border-radius: 8px;
        text-align: center;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .feature-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: white;
        font-size: 28px;
    }

    .feature-card h3 {
        font-size: 20px;
        margin-bottom: 15px;
        color: #333;
    }

    .feature-card p {
        color: #666;
        font-size: 14px;
        line-height: 1.6;
    }

    /* Stats Section */
    .stats-section {
        padding: 80px 0;
        background: white;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px;
        border-radius: 8px;
        text-align: center;
    }

    .stat-card h3 {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .stat-card p {
        font-size: 14px;
        opacity: 0.9;
    }

    /* CTA Section */
    .cta-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 80px 0;
        text-align: center;
    }

    .cta-section h2 {
        font-size: 40px;
        margin-bottom: 15px;
        font-weight: 700;
    }

    .cta-section p {
        font-size: 18px;
        margin-bottom: 30px;
        opacity: 0.95;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .hero-content h1 {
            font-size: 32px;
        }

        .hero-content p {
            font-size: 16px;
        }

        .hero-buttons {
            flex-direction: column;
        }

        .hero-buttons .btn {
            width: 100%;
        }

        .section-title {
            font-size: 28px;
            margin-bottom: 40px;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .cta-section h2 {
            font-size: 28px;
        }

        .hero-section,
        .features-section,
        .stats-section,
        .cta-section {
            padding: 50px 0;
        }
    }

    @media (max-width: 480px) {
        .hero-section {
            padding: 40px 0;
        }

        .hero-content h1 {
            font-size: 24px;
        }

        .hero-content p {
            font-size: 14px;
        }

        .features-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .feature-card {
            padding: 30px 20px;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
