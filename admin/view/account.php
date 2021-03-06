<?php
$account_id = htmlspecialchars($_REQUEST['id']);
if (is_null($account = Account::getByID($account_id))) {
	$template_data['message'] = array("Can't find account with id $account_id");
	$template_data['fatal'] = 1;
	return;
}

// UserTools::debug("Account: " . var_export($account, true));

$plan_data = array('slug', 'name', 'description', 'base_price', 'base_period', 'details_url', 'grace_period');

$schedule_data = array('name', 'description', 'charge_amount', 'charge_period');

$BREADCRUMB_EXTRA = $account->getName();

$template_data['useSubscriptions'] = UserConfig::$useSubscriptions;

$template_data['account_id'] = $account_id;

$template_data['account_name'] = $account->getName();
$template_data['account_isActive'] = $account->isActive();
$template_data['account_engine'] = is_null($account->getPaymentEngine()) ? 'None' : $account->getPaymentEngine()->getTitle();

$next_charge = $account->getNextCharge();
if (!is_null($next_charge)) {
	$template_data['account_next_charge'] = preg_replace("/ .*/", "", $next_charge);
}

if (UserConfig::$useSubscriptions) {
	$plan = $account->getPlan();

	foreach ($plan_data as $d) {
		$template_data['plan_' . $d] =  $plan->$d;
	}

	$downgrade = Plan::getPlanBySlug($plan->downgrade_to);
	if ($downgrade) {
		$template_data['plan_downgrade_to'] = $downgrade->name;
		$template_data['plan_downgrade_to_slug'] = $downgrade->slug;
	}

	$next_plan = $account->getNextPlan();
	if ($next_plan) {
		foreach ($plan_data as $d) {
			$template_data['next_plan_' . $d] = $next_plan->$d;
		}
	}

	$schedule = $account->getSchedule();
	if ($schedule) {
		foreach ($schedule_data as $d) {
			$template_data['schedule_' . $d] = $schedule->$d;
		}
	}

	$schedule = $account->getNextSchedule();
	if ($schedule) {
		foreach ($schedule_data as $d) {
			$template_data['next_schedule_' . $d] = $schedule->$d;
		}
	}

	$template_data['charges'] = $account->getCharges();
	$template_data['balance'] = $account->getBalance();
}

$acctount_users = $account->getUsers();
$users = array();

uasort($acctount_users, function($a, $b) {
			// sort by role first
			if ($a[1] !== $b[1]) {
				return $b[1] - $a[1];
			}

			// then sort by user name
			return strcmp($a[0]->getName(), $b[0]->getName());
		});

foreach ($acctount_users as $user_and_role) {
	$user = $user_and_role[0];
	$role = $user_and_role[1];

	$users[] = array('id' => $user->getID(), 'name' => $user->getName(), 'admin' => $role ? true : false);
}
$template_data['users'] = $users;
$template_data['USERSROOTURL'] = UserConfig::$USERSROOTURL;
