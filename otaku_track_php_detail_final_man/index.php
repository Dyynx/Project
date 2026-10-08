<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';
$page = (string) get('page', 'home');

/* ---------- POST actions ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) post('action', '');

    if ($action === 'register') {
        $username = trim((string) post('username'));
        $email = trim((string) post('email'));
        $password = (string) post('password');

        if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            flash('error', 'Data register tidak valid. Username 3-50 karakter dan password minimal 6 karakter.');
            redirect('register');
        }

        try {
            $stmt = db()->prepare('INSERT INTO users (username,email,password_hash) VALUES (?,?,?)');
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
            flash('success', 'Registrasi berhasil. Silakan login.');
            redirect('login');
        } catch (PDOException $e) {
            flash('error', 'Username atau email sudah digunakan.');
            redirect('register');
        }
    }

    if ($action === 'login') {
        $login = trim((string) post('login'));
        $password = (string) post('password');

        $stmt = db()->prepare('SELECT * FROM users WHERE username=? OR email=? LIMIT 1');
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            flash('success', 'Selamat datang kembali, ' . $user['username'] . '!');
            redirect('home');
        }

        flash('error', 'Login gagal. Periksa username/email dan password.');
        redirect('login');
    }

    if ($action === 'forgot') {
        $email = trim((string) post('email'));
        $stmt = db()->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(24));
            $hash = hash('sha256', $token);
            $update = db()->prepare('UPDATE users SET reset_token_hash=?, reset_expires_at=DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE id=?');
            $update->execute([$hash, $user['id']]);
            $_SESSION['dev_reset_url'] = url('reset&token=' . urlencode($token));
        }

        flash('success', 'Jika email terdaftar, token reset sudah dibuat. Pada mode tugas, link reset ditampilkan di halaman.');
        redirect('forgot');
    }

    if ($action === 'reset_password') {
        $token = (string) post('token');
        $password = (string) post('password');
        $hash = hash('sha256', $token);

        $stmt = db()->prepare('SELECT id FROM users WHERE reset_token_hash=? AND reset_expires_at > NOW()');
        $stmt->execute([$hash]);
        $user = $stmt->fetch();

        if (!$user || strlen($password) < 6) {
            flash('error', 'Token reset tidak valid atau password terlalu pendek.');
            redirect('forgot');
        }

        $update = db()->prepare('UPDATE users SET password_hash=?, reset_token_hash=NULL, reset_expires_at=NULL WHERE id=?');
        $update->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        unset($_SESSION['dev_reset_url']);
        flash('success', 'Password berhasil diubah. Silakan login.');
        redirect('login');
    }

    if ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        redirect('home');
    }

    if ($action === 'save_list') {
        require_login();
        $u = current_user();
        $type = post('media_type') === 'manga' ? 'manga' : 'anime';
        $malId = (int) post('mal_id');
        $aniId = (int) post('anilist_id');
        $externalId = $malId ?: $aniId;
        $title = trim((string) post('title'));
        $image = trim((string) post('image_url'));
        $status = trim((string) post('status'));
        $score = post('score') !== '' ? (float) post('score') : null;
        $progress = max(0, (int) post('progress'));

        $allowed = ['watching','completed','on_hold','dropped','plan_to_watch','reading','plan_to_read'];
        if (!in_array($status, $allowed, true) || !$externalId || !$title) {
            flash('error', 'Data list tidak valid.');
            redirect('home');
        }

        $stmt = db()->prepare(
            'INSERT INTO media_lists (user_id,media_type,external_id,title,image_url,status,score,progress)
             VALUES (?,?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE title=VALUES(title), image_url=VALUES(image_url), status=VALUES(status), score=VALUES(score), progress=VALUES(progress)'
        );
        $stmt->execute([$u['id'],$type,$externalId,$title,$image,$status,$score,$progress]);
        flash('success', 'List berhasil diperbarui.');
        redirect(media_detail_url($type, $malId, $aniId));
    }

    if ($action === 'delete_list') {
        require_login();
        $stmt = db()->prepare('DELETE FROM media_lists WHERE user_id=? AND media_type=? AND external_id=?');
        $stmt->execute([current_user()['id'], post('media_type'), (int) post('external_id')]);
        flash('success', 'Item dihapus dari list.');
        redirect('profile');
    }

    if ($action === 'create_thread') {
        require_login();
        $title = trim((string) post('title'));
        $body = trim((string) post('body'));
        $category = (int) post('category_id');
        if ($title === '' || $body === '' || !$category) {
            flash('error', 'Judul, isi, dan kategori wajib diisi.');
            redirect('forum');
        }

        $stmt = db()->prepare('INSERT INTO forum_threads (category_id,user_id,title,body,external_id,media_type) VALUES (?,?,?,?,?,?)');
        $stmt->execute([
            $category,
            current_user()['id'],
            $title,
            $body,
            post('external_id') ? (int) post('external_id') : null,
            in_array(post('media_type'), ['anime','manga'], true) ? post('media_type') : null
        ]);
        redirect('thread&id=' . db()->lastInsertId());
    }

    if ($action === 'reply') {
        require_login();
        $threadId = (int) post('thread_id');
        $body = trim((string) post('body'));
        if ($body === '') {
            flash('error', 'Reply tidak boleh kosong.');
            redirect('thread&id=' . $threadId);
        }
        $stmt = db()->prepare('SELECT is_locked FROM forum_threads WHERE id=?');
        $stmt->execute([$threadId]);
        $thread = $stmt->fetch();
        if (!$thread || $thread['is_locked']) {
            flash('error', 'Thread tidak ditemukan atau sedang dikunci.');
            redirect('forum');
        }

        $stmt = db()->prepare('INSERT INTO forum_replies (thread_id,user_id,body) VALUES (?,?,?)');
        $stmt->execute([$threadId,current_user()['id'],$body]);
        redirect('thread&id=' . $threadId);
    }

    if ($action === 'vote') {
        require_login();
        $threadId = (int) post('thread_id');
        $check = db()->prepare('SELECT id FROM forum_votes WHERE thread_id=? AND user_id=?');
        $check->execute([$threadId,current_user()['id']]);
        if ($check->fetch()) {
            db()->prepare('DELETE FROM forum_votes WHERE thread_id=? AND user_id=?')->execute([$threadId,current_user()['id']]);
        } else {
            db()->prepare('INSERT INTO forum_votes (thread_id,user_id) VALUES (?,?)')->execute([$threadId,current_user()['id']]);
        }
        redirect('thread&id=' . $threadId);
    }

    if ($action === 'add_watch_link') {
        require_admin();
        $stmt = db()->prepare('INSERT INTO watch_links (external_id,media_type,provider,url,created_by) VALUES (?,?,?,?,?)');
        $stmt->execute([(int) post('external_id'),post('media_type'),trim((string)post('provider')),trim((string)post('url')),current_user()['id']]);
        flash('success','Watch link ditambahkan.');
        redirect(media_detail_url((string) post('media_type'), (int) post('external_id'), 0));
    }

    if ($action === 'admin_toggle_user') {
        require_admin();
        $id = (int) post('user_id');
        $role = post('role') === 'admin' ? 'admin' : 'user';
        db()->prepare('UPDATE users SET role=? WHERE id=?')->execute([$role,$id]);
        redirect('admin');
    }

    if ($action === 'admin_toggle_thread') {
        require_admin();
        $id = (int) post('thread_id');
        db()->prepare('UPDATE forum_threads SET is_hidden=1-is_hidden WHERE id=?')->execute([$id]);
        redirect('admin');
    }

    if ($action === 'admin_lock_thread') {
        require_admin();
        $id = (int) post('thread_id');
        db()->prepare('UPDATE forum_threads SET is_locked=1-is_locked WHERE id=?')->execute([$id]);
        redirect('admin');
    }
}

/* ---------- GET pages ---------- */
if ($page === 'home') {
    $popular = $api->popularAnime(12);
    $top = $api->topAnime(6);
    $manga = $api->popularManga(8);
    $apiOnline = !empty($popular) || !empty($top);
    render('home', compact('popular','top','manga','apiOnline'));
    exit;
}

if ($page === 'login') { render('auth/login'); exit; }
if ($page === 'register') { render('auth/register'); exit; }
if ($page === 'forgot') { render('auth/forgot'); exit; }
if ($page === 'health') { require __DIR__ . '/health.php'; exit; }

if ($page === 'reset') {
    $token = (string) get('token');
    render('auth/reset', ['token' => $token]);
    exit;
}

if ($page === 'search') {
    $q = trim((string) get('q', ''));
    $type = get('type', 'anime') === 'manga' ? 'manga' : 'anime';
    $filters = [
        'format' => get('format'),
        'type' => get('format'),
        'status' => get('status'),
        'rating' => get('rating'),
        'min_score' => get('min_score'),
        'order_by' => get('order_by', 'popularity'),
        'sort' => get('sort', 'desc'),
        'genre' => get('genre'),
        'season' => get('season'),
        'year' => get('year'),
    ];
    if ($q !== '') {
        $results = $type === 'anime' ? $api->searchAnime($q,$filters) : $api->searchManga($q,$filters);
    } else {
        $aniSort = get('order_by') === 'score' ? 'SCORE_DESC' : 'POPULARITY_DESC';
        $filters['sort'] = $aniSort;
        $results = $api->browse($type,$filters,70);
    }
    render('anime/search', compact('q','type','results','filters'));
    exit;
}

if ($page === 'detail') {
    // Legacy compatibility: poster links now target public/detail.php directly.
    // Keep old /index.php?page=detail&id=... URLs working for bookmarks and previous builds.
    $query = $_GET;
    unset($query['page']);
    $location = asset_url('detail.php');
    if ($query) $location .= '?' . http_build_query($query);
    header('Location: ' . $location, true, 302);
    exit;
}

if ($page === 'profile') {
    require_login();
    $u = current_user();
    $stmt = db()->prepare('SELECT * FROM media_lists WHERE user_id=? ORDER BY updated_at DESC');
    $stmt->execute([$u['id']]);
    $items = $stmt->fetchAll();

    $statsStmt = db()->prepare('SELECT status, COUNT(*) total FROM media_lists WHERE user_id=? GROUP BY status');
    $statsStmt->execute([$u['id']]);
    $stats = [];
    foreach ($statsStmt as $row) $stats[$row['status']] = (int)$row['total'];

    render('profile/profile', compact('u','items','stats'));
    exit;
}

if ($page === 'forum') {
    $categories = db()->query('SELECT c.*, COUNT(t.id) thread_count FROM forum_categories c LEFT JOIN forum_threads t ON t.category_id=c.id AND t.is_hidden=0 GROUP BY c.id ORDER BY c.id')->fetchAll();
    $threads = db()->query(
        'SELECT t.*, u.username, c.name category_name,
                (SELECT COUNT(*) FROM forum_replies r WHERE r.thread_id=t.id AND r.is_hidden=0) reply_count,
                (SELECT COUNT(*) FROM forum_votes v WHERE v.thread_id=t.id) vote_count
         FROM forum_threads t
         JOIN users u ON u.id=t.user_id
         JOIN forum_categories c ON c.id=t.category_id
         WHERE t.is_hidden=0
         ORDER BY t.updated_at DESC LIMIT 30'
    )->fetchAll();
    render('forum/forum', compact('categories','threads'));
    exit;
}

if ($page === 'thread') {
    $id = (int)get('id');
    $stmt = db()->prepare(
        'SELECT t.*, u.username, c.name category_name,
                (SELECT COUNT(*) FROM forum_votes v WHERE v.thread_id=t.id) vote_count
         FROM forum_threads t
         JOIN users u ON u.id=t.user_id
         JOIN forum_categories c ON c.id=t.category_id
         WHERE t.id=? AND t.is_hidden=0'
    );
    $stmt->execute([$id]);
    $thread = $stmt->fetch();
    if (!$thread) { http_response_code(404); render('404'); exit; }

    $stmt = db()->prepare(
        'SELECT r.*, u.username FROM forum_replies r JOIN users u ON u.id=r.user_id
         WHERE r.thread_id=? AND r.is_hidden=0 ORDER BY r.created_at'
    );
    $stmt->execute([$id]);
    $replies = $stmt->fetchAll();

    render('forum/thread', compact('thread','replies'));
    exit;
}

if ($page === 'admin') {
    require_admin();
    $users = db()->query('SELECT id,username,email,role,created_at FROM users ORDER BY created_at DESC LIMIT 50')->fetchAll();
    $threads = db()->query(
        'SELECT t.id,t.title,t.is_locked,t.is_hidden,t.created_at,u.username,c.name category_name
         FROM forum_threads t JOIN users u ON u.id=t.user_id JOIN forum_categories c ON c.id=t.category_id
         ORDER BY t.created_at DESC LIMIT 50'
    )->fetchAll();
    render('admin/dashboard', compact('users','threads'));
    exit;
}

http_response_code(404);
render('404');
