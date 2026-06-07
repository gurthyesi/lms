<?php
require 'includes/config.php';
require 'includes/layout.php';
$user = requireLogin();
$db   = getDB();

$courseId = (int)($_GET['id'] ?? 0);
if (!$courseId) { header('Location: dashboard.php'); exit; }

// Fetch course
$stmt = $db->prepare("SELECT * FROM courses WHERE id = ? AND is_active = 1");
$stmt->execute([$courseId]);
$course = $stmt->fetch();
if (!$course) { header('Location: dashboard.php'); exit; }

// Access check
if (!canAccessDegree($course['degree'], $user)) {
    header('Location: dashboard.php'); exit;
}

// Chapters
$stmt = $db->prepare("SELECT ch.*,
    (SELECT COUNT(*) FROM chapter_documents WHERE chapter_id = ch.id) AS doc_count,
    (SELECT COUNT(*) FROM chapter_links    WHERE chapter_id = ch.id) AS link_count,
    (SELECT COUNT(*) FROM chapter_likes    WHERE chapter_id = ch.id) AS like_count,
    (SELECT COUNT(*) FROM chapter_comments WHERE chapter_id = ch.id) AS comment_count,
    (SELECT is_finished FROM user_chapter_progress WHERE user_id=? AND chapter_id=ch.id LIMIT 1) AS is_finished
    FROM chapters ch
    WHERE ch.course_id = ? AND ch.is_active = 1
    ORDER BY ch.sort_order ASC, ch.id ASC");
$stmt->execute([$user['id'], $courseId]);
$chapters = $stmt->fetchAll();

$total    = count($chapters);
$finished = count(array_filter($chapters, fn($c) => $c['is_finished']));
$progress = $total > 0 ? round(($finished / $total) * 100) : 0;

layout_head($course['title']);
layout_sidebar($user);
layout_topbar('Course', $user);
?>

<div class="page-content">

  <!-- Breadcrumb -->
  <div class="breadcrumb-custom">
    <a href="dashboard.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
    <i class="fa-solid fa-chevron-right"></i>
    <a href="courses.php">Courses</a>
    <i class="fa-solid fa-chevron-right"></i>
    <span><?= htmlspecialchars($course['title']) ?></span>
  </div>

  <!-- Course Header -->
  <div class="card mb-4 overflow-hidden">
    <div style="display:flex;flex-direction:row;flex-wrap:wrap">
      <!-- Thumbnail -->
      <div style="width:200px;min-height:200px;flex-shrink:0;background:linear-gradient(135deg,var(--primary),var(--primary-light));position:relative">
        <?php if ($course['photo']): ?>
          <img src="assets/uploads/<?= htmlspecialchars($course['photo']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;min-height:200px">
        <?php else: ?>
          <div style="width:100%;height:200px;display:flex;align-items:center;justify-content:center;font-size:4rem;color:rgba(201,168,76,0.4)">
            <i class="fa-solid fa-book-open-reader"></i>
          </div>
        <?php endif; ?>
        <span class="degree-badge <?= str_replace(' ','-',$course['degree']) ?>" style="position:absolute;bottom:12px;left:12px">
          <?= htmlspecialchars($course['degree']) ?>
        </span>
      </div>
      <!-- Info -->
      <div style="flex:1;padding:24px;min-width:0">
        <h2 style="margin-bottom:8px"><?= htmlspecialchars($course['title']) ?></h2>
        <p style="color:var(--text-muted);font-size:.9rem;margin-bottom:20px"><?= nl2br(htmlspecialchars($course['description'] ?? '')) ?></p>

        <div class="d-flex gap-4 flex-wrap mb-3">
          <div style="font-size:.85rem;color:var(--text-muted)">
            <i class="fa-solid fa-list-check me-1" style="color:var(--gold)"></i>
            <?= $total ?> Chapter<?= $total !== 1 ? 's' : '' ?>
          </div>
          <div style="font-size:.85rem;color:var(--text-muted)">
            <i class="fa-solid fa-circle-check me-1" style="color:var(--gold)"></i>
            <?= $finished ?> Completed
          </div>
        </div>

        <!-- Progress -->
        <div style="margin-bottom:8px">
          <div class="d-flex justify-content-between mb-1" style="font-size:.82rem;font-weight:700;color:var(--primary)">
            <span>Progress</span>
            <span id="pct-<?= $courseId ?>"><?= $progress ?>%</span>
          </div>
          <div class="progress-bar-custom" style="height:10px">
            <div class="progress-fill" id="progress-<?= $courseId ?>" style="width:<?= $progress ?>%"></div>
          </div>
        </div>

        <?php if ($progress == 100): ?>
          <div class="alert-custom alert-success mt-2" style="font-size:.85rem">
            <i class="fa-solid fa-trophy"></i> Congratulations! You've completed this course.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Chapters List -->
  <h5 class="section-title"><i class="fa-solid fa-list-ol"></i> Chapters</h5>

  <?php if (empty($chapters)): ?>
    <div class="empty-state">
      <i class="fa-solid fa-book"></i>
      <h5>No Chapters Yet</h5>
      <p>This course doesn't have any chapters yet. Check back soon!</p>
    </div>
  <?php else: ?>
    <?php foreach ($chapters as $i => $ch): ?>
    <div class="chapter-item <?= $ch['is_finished'] ? 'completed' : '' ?>"
         data-id="<?= $ch['id'] ?>"
         onclick="openChapter(<?= $ch['id'] ?>, <?= $courseId ?>); document.getElementById('chapterModalTitle').textContent = '<?= addslashes(htmlspecialchars($ch['title'])) ?>'">
      <div class="chapter-num">
        <?php if ($ch['is_finished']): ?>
          <i class="fa-solid fa-check"></i>
        <?php else: ?>
          <?= $i + 1 ?>
        <?php endif; ?>
      </div>
      <div class="chapter-info">
        <h6><?= htmlspecialchars($ch['title']) ?></h6>
        <p><?= htmlspecialchars($ch['description'] ?? '') ?></p>
      </div>
      <div class="chapter-badges">
        <?php if ($ch['youtube_url']): ?>
          <div class="badge-icon has-video" title="Video lesson"><i class="fa-brands fa-youtube"></i></div>
        <?php endif; ?>
        <?php if ($ch['doc_count'] > 0): ?>
          <div class="badge-icon has-docs" title="<?= $ch['doc_count'] ?> document(s)"><i class="fa-solid fa-file-lines"></i></div>
        <?php endif; ?>
        <?php if ($ch['link_count'] > 0): ?>
          <div class="badge-icon has-links" title="<?= $ch['link_count'] ?> link(s)"><i class="fa-solid fa-link"></i></div>
        <?php endif; ?>
      </div>
      <div style="display:flex;align-items:center;gap:6px;color:var(--text-muted);font-size:.8rem;flex-shrink:0">
        <i class="fa-regular fa-heart"></i><?= $ch['like_count'] ?>
        <i class="fa-regular fa-comment ms-1"></i><?= $ch['comment_count'] ?>
        <i class="fa-solid fa-chevron-right ms-2" style="font-size:.7rem"></i>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>

<?php layout_scripts(); ?>
