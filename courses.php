<?php
require 'includes/config.php';
require 'includes/layout.php';
$user = requireLogin();
$db   = getDB();

$degrees = ['Public'];
if (in_array($user['status'], ['Member','Founder','Administrator'])) {
    $degrees = ['Public','Apprentice','Fellowcraft','Master Mason'];
}
$placeholders = implode(',', array_fill(0, count($degrees), '?'));

$filter_degree = $_GET['degree'] ?? '';
$search        = trim($_GET['q'] ?? '');

$params = $degrees;
$where  = "c.is_active = 1 AND c.degree IN ($placeholders)";

if ($filter_degree && in_array($filter_degree, $degrees)) {
    $where  .= " AND c.degree = ?";
    $params[] = $filter_degree;
}

if ($search) {
    $where  .= " AND (c.title LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $db->prepare("SELECT c.*,
    (SELECT COUNT(*) FROM chapters ch WHERE ch.course_id=c.id AND ch.is_active=1) AS chapter_count
    FROM courses c WHERE $where ORDER BY c.created_at DESC");
$stmt->execute($params);
$courses = $stmt->fetchAll();

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

layout_head('All Courses');
layout_sidebar($user);
layout_topbar('All Courses', $user);
?>

<div class="page-content">

  <!-- Filters -->
  <div class="card mb-4">
    <div class="card-body-custom">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-12 col-md-5">
          <label class="form-label mb-1">Search</label>
          <input type="text" name="q" class="form-control" placeholder="Search courses..."
                 value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label mb-1">Degree</label>
          <select name="degree" class="form-select">
            <option value="">All Degrees</option>
            <?php foreach ($degrees as $d): ?>
              <option value="<?= $d ?>" <?= $filter_degree===$d?'selected':'' ?>><?= $d ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <button type="submit" class="btn btn-primary-custom w-100">
            <i class="fa-solid fa-magnifying-glass me-1"></i> Search
          </button>
        </div>
        <?php if ($search || $filter_degree): ?>
        <div class="col-12 col-md-2">
          <a href="courses.php" class="btn btn-outline-custom w-100">Clear</a>
        </div>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="section-title mb-0"><i class="fa-solid fa-graduation-cap"></i> Courses (<?= count($courses) ?>)</h5>
  </div>

  <?php if (empty($courses)): ?>
    <div class="empty-state">
      <i class="fa-solid fa-graduation-cap"></i>
      <h5>No Courses Found</h5>
      <p>No courses match your current search or filter criteria.</p>
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
              <div class="progress-fill" style="width:<?= $course['progress'] ?>%"></div>
            </div>
          </div>
          <div class="course-footer">
            <span class="chapters-count">
              <i class="fa-solid fa-list me-1" style="color:var(--gold)"></i>
              <?= $course['done'] ?>/<?= $course['chapter_count'] ?> chapters
            </span>
            <span class="pct"><?= $course['progress'] ?>%</span>
          </div>
        </div>
      </a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

<?php layout_scripts(); ?>
