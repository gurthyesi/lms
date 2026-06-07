<?php
require 'includes/config.php';
require 'includes/layout.php';
$user = requireLogin();
$db   = getDB();

// Determine accessible degrees
$degrees = ['Public'];
if (in_array($user['status'], ['Member','Founder','Administrator'])) {
    $degrees = ['Public','Apprentice','Fellowcraft','Master Mason'];
}
$placeholders = implode(',', array_fill(0, count($degrees), '?'));

// Active courses accessible to this user
$stmt = $db->prepare("SELECT c.*, 
    (SELECT COUNT(*) FROM chapters ch WHERE ch.course_id = c.id AND ch.is_active=1) AS chapter_count
    FROM courses c 
    WHERE c.is_active = 1 AND c.degree IN ($placeholders)
    ORDER BY c.created_at DESC");
$stmt->execute($degrees);
$courses = $stmt->fetchAll();

// For each course, compute progress
foreach ($courses as &$course) {
    if ($course['chapter_count'] > 0) {
        $p = $db->prepare("SELECT COUNT(*) FROM user_chapter_progress WHERE user_id=? AND course_id=? AND is_finished=1");
        $p->execute([$user['id'], $course['id']]);
        $done = (int)$p->fetchColumn();
        $course['progress'] = round(($done / $course['chapter_count']) * 100);
        $course['done']     = $done;
    } else {
        $course['progress'] = 0;
        $course['done']     = 0;
    }
}
unset($course);

// Stats
$totalCourses   = count($courses);
$completedCount = count(array_filter($courses, fn($c) => $c['progress'] == 100));
$inProgressCount = count(array_filter($courses, fn($c) => $c['progress'] > 0 && $c['progress'] < 100));

layout_head('Dashboard');
layout_sidebar($user);
layout_topbar('Dashboard', $user);
?>

<div class="page-content">

  <!-- Welcome Banner -->
  <div class="card mb-4" style="background:linear-gradient(135deg,var(--primary),var(--primary-light));border:none;color:#fff">
    <div class="card-body-custom" style="padding:28px">
      <div class="d-flex align-items-center gap-16 flex-wrap" style="gap:16px">
        <div>
          <h3 style="color:#fff;margin-bottom:4px">Welcome back, <?= htmlspecialchars($user['name']) ?> ✦</h3>
          <p style="color:rgba(255,255,255,0.7);margin:0;font-size:.9rem">
            Continue your journey of knowledge and wisdom.
          </p>
        </div>
        <div class="ms-auto d-flex gap-2">
          <span class="badge-status <?= $user['status'] ?>" style="font-size:.8rem;padding:6px 14px">
            <i class="fa-solid fa-star me-1"></i><?= $user['status'] ?>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Stat Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="stat-icon navy"><i class="fa-solid fa-graduation-cap"></i></div>
        <div class="stat-info">
          <div class="value"><?= $totalCourses ?></div>
          <div class="label">Available Courses</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-info">
          <div class="value"><?= $completedCount ?></div>
          <div class="label">Completed</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="stat-icon gold"><i class="fa-solid fa-spinner"></i></div>
        <div class="stat-info">
          <div class="value"><?= $inProgressCount ?></div>
          <div class="label">In Progress</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-scroll"></i></div>
        <div class="stat-info">
          <div class="value"><?= $user['year_of_registration'] ?: '—' ?></div>
          <div class="label">Since Year</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Courses Grid -->
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="section-title mb-0"><i class="fa-solid fa-book-open"></i> Your Active Courses</h5>
    <a href="courses.php" class="btn btn-outline-custom btn-sm">View All <i class="fa-solid fa-arrow-right ms-1"></i></a>
  </div>

  <?php if (empty($courses)): ?>
    <div class="empty-state">
      <i class="fa-solid fa-book-open"></i>
      <h5>No Courses Available</h5>
      <p>There are no active courses available for your membership level yet. Check back soon!</p>
    </div>
  <?php else: ?>
  <div class="row g-3">
    <?php foreach ($courses as $course): ?>
    <div class="col-12 col-sm-6 col-lg-4">
      <a href="course.php?id=<?= $course['id'] ?>" class="text-decoration-none">
        <div class="course-card">
          <div class="course-thumb">
            <?php if ($course['photo']): ?>
              <img src="assets/uploads/<?= htmlspecialchars($course['photo']) ?>" alt="">
            <?php else: ?>
              <div class="no-img"><i class="fa-solid fa-book-open-reader"></i></div>
            <?php endif; ?>
            <span class="degree-badge <?= str_replace(' ','-',$course['degree']) ?>">
              <?= htmlspecialchars($course['degree']) ?>
            </span>
          </div>
          <div class="course-body">
            <h5><?= htmlspecialchars($course['title']) ?></h5>
            <p><?= htmlspecialchars($course['description'] ?? '') ?></p>
            <div class="progress-bar-custom">
              <div class="progress-fill" id="progress-<?= $course['id'] ?>"
                   style="width:<?= $course['progress'] ?>%"></div>
            </div>
          </div>
          <div class="course-footer">
            <span class="chapters-count">
              <i class="fa-solid fa-list me-1" style="color:var(--gold)"></i>
              <?= $course['done'] ?>/<?= $course['chapter_count'] ?> chapters
            </span>
            <span class="pct" id="pct-<?= $course['id'] ?>"><?= $course['progress'] ?>%</span>
          </div>
        </div>
      </a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

<?php layout_scripts(); ?>
