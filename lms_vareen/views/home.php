<?php
// Home/Landing Page
?>
<div class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1>Welcome to VAREEN Academy</h1>
            <p>Learn anything, anytime, anywhere with world-class courses and expert instructors</p>
            
            <?php if (!isLoggedIn()): ?>
                <div class="hero-buttons">
                    <a href="<?php echo appBasePath(); ?>/index.php?page=signup" class="btn btn-primary btn-large">
                        <i class="fas fa-user-plus"></i> Get Started Free
                    </a>
                    <a href="<?php echo appBasePath(); ?>/index.php?page=courses" class="btn btn-outline-primary btn-large">
                        <i class="fas fa-graduation-cap"></i> Browse Courses
                    </a>
                </div>
            <?php else: ?>
                <div class="hero-buttons">
                    <a href="<?php echo appBasePath(); ?>/index.php?page=<?php echo getCurrentUserRole() === 'student' ? 'student-dashboard' : 'teacher-dashboard'; ?>" 
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
        <h2 class="section-title">Why Choose VAREEN Academy?</h2>
        
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
        <p>Join thousands of students already learning on VAREEN Academy</p>
        <?php if (!isLoggedIn()): ?>
            <a href="<?php echo appBasePath(); ?>/index.php?page=signup" class="btn btn-primary btn-large">
                Sign Up Now - It's Free!
            </a>
        <?php endif; ?>
    </div>
</section>

<!-- Popular Courses Section (VX-055) -->
<section class="courses-section">
    <div class="container">
        <h2 class="section-title">Popular Courses</h2>
        <p class="section-subtitle">Start learning with our most popular courses</p>
        <div class="courses-grid">
            <?php
            require_once 'src/classes/Course.php';
            $homeCourse = new Course();
            $featuredCourses = $homeCourse->getAllCourses(1, 6);
            if (!empty($featuredCourses)):
                foreach ($featuredCourses as $fc):
                    $price = (float)($fc['price'] ?? 0);
                    $currency = strtoupper(trim((string)($fc['currency'] ?? 'NGN')));
                    $symbol = ($currency === 'NGN') ? '₦' : $currency . ' ';
                    $formattedPrice = $price > 0 ? $symbol . number_format($price) : 'Free';
                    $modeLabel = [
                        'on_campus' => '<span class="mode-badge on-campus">On-Campus</span>',
                        'online' => '<span class="mode-badge online">Online</span>',
                        'hybrid' => '<span class="mode-badge hybrid">Hybrid</span>',
                    ];
            ?>
                <div class="course-card-home">
                    <div class="course-card-image">
                        <?php if (!empty($fc['thumbnail'])): ?>
                            <img src="<?php echo htmlspecialchars($fc['thumbnail']); ?>" alt="<?php echo htmlspecialchars($fc['title']); ?>">
                        <?php else: ?>
                            <div class="course-card-placeholder">
                                <i class="fas fa-book-open"></i>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($fc['delivery_mode']) && isset($modeLabel[$fc['delivery_mode']])): ?>
                            <span class="course-mode-badge"><?php echo $modeLabel[$fc['delivery_mode']]; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="course-card-body">
                        <h3><a href="<?php echo appBasePath(); ?>/index.php?page=course-detail&id=<?php echo (int)$fc['id']; ?>"><?php echo htmlspecialchars($fc['title']); ?></a></h3>
                        <p class="course-card-description"><?php echo htmlspecialchars(substr($fc['description'] ?? '', 0, 120)) . (strlen($fc['description'] ?? '') > 120 ? '...' : ''); ?></p>
                        <div class="course-card-meta">
                            <span class="course-instructor"><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($fc['instructor'] ?? 'Unassigned'); ?></span>
                            <?php if (!empty($fc['duration_weeks'])): ?>
                                <span class="course-duration"><i class="fas fa-calendar-alt"></i> <?php echo (int)$fc['duration_weeks']; ?> weeks</span>
                            <?php endif; ?>
                            <?php if (!empty($fc['level'])): ?>
                                <span class="course-level"><?php echo htmlspecialchars(ucfirst($fc['level'])); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="course-card-footer">
                            <span class="course-price"><?php echo $formattedPrice; ?></span>
                            <a href="<?php echo appBasePath(); ?>/index.php?page=course-detail&id=<?php echo (int)$fc['id']; ?>" class="btn btn-primary btn-small">View Course <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            <?php
                endforeach;
            else:
            ?>
                <div class="empty-courses">
                    <p>No courses available yet. Check back soon!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
    /* Features Section */
    .features-section {
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
    /* Home Courses Section (VX-055) */
    .home-courses-section{padding:80px 0;background:#fff}
    .section-subtitle{text-align:center;color:#6c757d;margin-bottom:40px;font-size:18px}
    .home-courses-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px}
    .home-course-card{background:#fff;border:1px solid #e9ecef;border-radius:12px;overflow:hidden;transition:box-shadow 0.3s,transform 0.3s;display:flex;flex-direction:column}
    .home-course-card:hover{box-shadow:0 8px 24px rgba(0,0,0,0.12);transform:translateY(-4px)}
    .home-course-thumb{position:relative;height:160px;background:#f8f9fa}
    .home-course-thumb img{width:100%;height:160px;object-fit:cover}
    .home-course-thumb-placeholder{display:flex;align-items:center;justify-content:center;height:160px;color:#ced4da;font-size:40px}
    .mode-badge{position:absolute;top:10px;right:10px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;color:#fff;text-transform:uppercase;letter-spacing:0.5px}
    .mode-badge.on-campus{background:#667eea}
    .mode-badge.online{background:#28a745}
    .mode-badge.hybrid{background:#fd7e14}
    .home-course-mode-badge{position:absolute;top:10px;right:10px;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;color:#fff;text-transform:uppercase;letter-spacing:0.5px}
    .home-course-body{padding:16px 20px 20px;flex:1;display:flex;flex-direction:column}
    .home-course-card h3,.home-course-card h3 a,.home-course-card h3 a:hover{color:#333;text-decoration:none;line-height:1.3;font-size:16px;margin:0 0 8px}
    .home-course-desc{color:#6c757d;font-size:13px;line-height:1.5;margin:0 0 12px;flex:1}
    .home-course-meta{display:flex;flex-wrap:wrap;gap:12px;font-size:12px;color:#868e96;margin-bottom:12px}
    .home-course-meta span{display:flex;align-items:center;gap:4px}
    .home-course-footer{display:flex;align-items:center;justify-content:space-between;padding-top:12px;border-top:1px solid #f1f3f5;margin-top:auto}
    .home-course-price{font-size:18px;font-weight:700;color:#667eea}
    .empty-courses-msg{text-align:center;padding:40px;color:#868e96;font-size:16px}
    @media(max-width:768px){.home-courses-section{padding:60px 0}.home-courses-grid{grid-template-columns:1fr}}
</style>
