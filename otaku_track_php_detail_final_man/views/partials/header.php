<?php $u=current_user(); ?>
<!doctype html><html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?=e(APP_NAME)?><?=isset($title)?' — '.e($title):''?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
<link href="<?=e(asset_url('assets/css/app.css'))?>" rel="stylesheet"></head>
<body>
<nav class="site-nav"><div class="container nav-inner">
<a class="brand" href="<?=url('home')?>"><span class="brand-mark">O</span><span>Otaku Track</span></a>
<div class="nav-links"><a href="<?=url('search&type=anime')?>">Explore</a><a href="<?=url('forum')?>">Forum</a><a href="<?=url('search&type=manga')?>">Manga</a></div>
<form class="nav-search" method="get" action="<?=e(asset_url('index.php'))?>"><input type="hidden" name="page" value="search"><input type="hidden" name="type" value="<?=e(get('type','anime') === 'manga' ? 'manga' : 'anime')?>"><input name="q" value="<?=e((string)get('q',''))?>" placeholder="Search anime..." aria-label="Search anime"><button><i class="fa-solid fa-magnifying-glass"></i></button></form>
<div class="nav-actions"><button class="icon-btn" id="theme-toggle" title="Ganti tema" type="button" aria-label="Ganti tema"><i class="fa-regular fa-moon"></i></button><button class="icon-btn menu-btn" type="button" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
<?php if($u): ?><a class="user-pill" href="<?=url('profile')?>"><span class="mini-avatar"><?=e(strtoupper(substr($u['username'],0,1)))?></span><?=e($u['username'])?></a><?php else: ?><a class="login-link" href="<?=url('login')?>">Login</a><a class="register-btn" href="<?=url('register')?>">Register</a><?php endif; ?></div>
</div></nav>
<main>
<?php if($msg=flash('success')):?><div class="container pt-3"><div class="alert alert-success"><?=e($msg)?></div></div><?php endif; ?>
<?php if($msg=flash('error')):?><div class="container pt-3"><div class="alert alert-danger"><?=e($msg)?></div></div><?php endif; ?>
