<?php
require_once(dirname(__DIR__) . '/global.php');
require_once(dirname(__DIR__) . '/classes/User.php');
require_once(__DIR__ . '/adminMenus.php');

$current_user = User::require_login(false);

if (!$current_user->isAdmin()) {
	require_once(__DIR__ . '/admin_access_only.php');
	exit;
}

if (array_key_exists('impersonate', $_POST)) {
	if ($current_user->isTheSameAs(User::getUser($_POST['impersonate']))) {
		header('Location: #msg=cantimpersonateself');
		exit;
	}

	$impersonated_user = $current_user->impersonate(User::getUser($_POST['impersonate']));
	if ($impersonated_user !== null) {
		header('Location: ' . UserConfig::$DEFAULTLOGINRETURN);
		exit;
	} else {
		header('Location: #msg=cantimpersonate');
		exit;
	}
}

$ADMIN_ROOT = UserConfig::$USERSROOTURL . '/admin';

$features_num = count(Feature::getAll());

$admin_menu = new AdminMenu(array(
			new Menu('home', 'Home', $ADMIN_ROOT . '/', 'home'),
			/*
			  new menuSection('dashboards', 'Dashboards', array(
			  new menu('basic', 'Basic Metrics', $ADMIN_ROOT . '/', 'signal')
			  )),
			 */
			new MenuSection('users', 'Users', null, array(
				new Menu('activity', 'Activity', $ADMIN_ROOT . '/activity.php', 'signal'),
				new Menu('accounts', 'Accounts', null, 'folder-open'),
				new Menu('plans', 'Plans', null, 'folder-open'),
				new Menu('registrations', 'Registered Users', $ADMIN_ROOT . '/users.php', 'user'),
				new Menu('cohorts', 'Cohort Analysis', $ADMIN_ROOT . '/cohorts.php', 'th'),
				new Menu('bymodule', 'Registrations By Module', $ADMIN_ROOT . '/bymodule.php', 'th-large'),
				new Menu('invitations', 'Invitations', $ADMIN_ROOT . '/invitations.php', 'envelope', UserConfig::$enableInvitations, 'Invitations are disabled in configuration'),
			)),
			new MenuSection('money', 'Money', null, array(
				new Menu('outstanding', 'Outstanding charges', $ADMIN_ROOT . '/outstanding.php', 'certificate', UserConfig::$useSubscriptions, 'Subscriptions are disabled in configuration'),
				new Menu('transactions', 'Transactions', null, 'list', UserConfig::$useSubscriptions),
				new Menu('payment_method', 'Payment methods', null, 'th-large', UserConfig::$useSubscriptions)
					), null, UserConfig::$useSubscriptions),
			new MenuSection('promotion', 'Promotion', null, array(
				new Menu('sources', 'Sources', $ADMIN_ROOT . '/sources.php', 'random'),
				new Menu('campaigns', 'Campaign management', $ADMIN_ROOT . '/campaigns.php', 'comment')
			)),
			new MenuSection('gamification', 'Gamification', null, array(
				new Menu('badges', 'Badges', $ADMIN_ROOT . '/badges.php', 'star')
			)),
			new MenuSection('settings', 'Settings', null, array(
				new Menu('systemsettings', 'System Settings', $ADMIN_ROOT . '/settings.php', 'wrench'),
				new Menu('features', 'Features', $ADMIN_ROOT . '/features.php', 'check', $features_num > 0, 'No features defined in this app')
			))
		));

if (isset($ADMIN_SECTION)) {
	$admin_menu->setActive($ADMIN_SECTION);
}

if (!isset($BREADCRUMB_EXTRA)) {
	$BREADCRUMB_EXTRA = null;
}
?><!DOCTYPE html>
<html lang="en">
	<head>
		<title><?php echo is_null(UserConfig::$appName) ? '' : UserConfig::$appName; ?><?php $admin_menu->renderTitle($BREADCRUMB_EXTRA) ?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link href="<?php echo UserConfig::$USERSROOTURL ?>/bootstrap/css/bootstrap.min.css" rel="stylesheet">
		<link href="<?php echo UserConfig::$USERSROOTURL ?>/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
		<script src="<?php echo UserConfig::$USERSROOTURL ?>/jquery-1.8.2.min.js"></script>
		<script src="<?php echo UserConfig::$USERSROOTURL ?>/bootstrap/js/bootstrap.min.js"></script>
		<link href="<?php echo UserConfig::$USERSROOTURL ?>/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
		<link rel="stylesheet" type="text/css" href="<?php echo UserConfig::$USERSROOTURL ?>/themes/<?php echo UserConfig::$theme ?>/startupapi.css">
		<style>
			.startupapi-sidebar.affix {
				top: 4em;
			}
		</style>
	</head>
	<body>
		<div class="navbar">
			<div class="navbar-inner">
				<span class="brand">
					<img class="startupapi-logo" width="20" height="20" src="<?php echo UserConfig::$USERSROOTURL ?>/images/header_icon.png"/>
					<a href="<?php echo UserConfig::$SITEROOTURL ?>"><?php echo is_null(UserConfig::$appName) ? '' : UserConfig::$appName; ?></a>
					&middot; Admin Panel
				</span>

				<span></span>

				<?php $admin_menu->renderTopNav() ?>

				<ul class="nav pull-right">
					<li><a href="<?php echo UserConfig::$USERSROOTURL ?>/edit.php" title="<?php echo UserTools::escape($current_user->getName()) ?>'s user information"><?php echo $current_user->getName() ?></a></li>
					<li><a href="<?php echo UserConfig::$USERSROOTURL ?>/logout.php">Logout</a></li>
				</ul>
			</div>
		</div>
		<div class="container-fluid">
			<div class="row-fluid">
				<div class="span3">
					<div class="well sidebar-nav startupapi-sidebar">

						<?php $admin_menu->render() ?>

					</div>
					<!--Sidebar content-->
				</div>

				<!-- admin header ends -->

				<div class="span9">
					<?php
					$admin_menu->renderBreadCrumbs($BREADCRUMB_EXTRA);
					?>
				</div>
