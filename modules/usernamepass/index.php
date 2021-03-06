<?php
/**
 * Basic authentication module using username and password
 *
 * Registers users with their username, password, name and email address
 *
 * This is the module that is enabled by default in user_config.sample.php
 * because it requires not configuration.
 *
 * @package StartupAPI
 * @subpackage Authentication\UsernamePassword
 */
class UsernamePasswordAuthenticationModule extends AuthenticationModule
{
	public function getID()
	{
		return "userpass";
	}

	public function getLegendColor()
	{
		return "a3a3a3";
	}

	public function getTitle()
	{
		return "Username / Password";
	}

	public function getUserCredentials($user)
	{
		$db = UserConfig::getDB();

		$userid = $user->getID();

		if ($stmt = $db->prepare('SELECT username FROM '.UserConfig::$mysql_prefix.'users WHERE id = ?'))
		{
			if (!$stmt->bind_param('i', $userid))
			{
				throw new DBBindParamException($db, $stmt);
			}
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($username))
			{
				throw new DBBindResultException($db, $stmt);
			}

			$stmt->fetch();
			$stmt->close();

			// if user used password recovery and remembered his old password
			// then clean temporary password and password reset flag
			// (don't reset the flag if was was set for some other reasons)
			if (!is_null($username))
			{
				return new UsernamePassUserCredentials($username);
			}
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		return null;
	}

	public function getTotalConnectedUsers()
	{
		$db = UserConfig::getDB();

		$conns = 0;

		if ($stmt = $db->prepare('SELECT count(*) AS conns FROM '.UserConfig::$mysql_prefix.'users WHERE username IS NOT NULL'))
		{
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($conns))
			{
				throw new DBBindResultException($db, $stmt);
			}

			$stmt->fetch();
			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		return $conns;
	}
	/*
	 * retrieves aggregated registrations numbers
	 */
	public function getDailyRegistrations()
	{
		$db = UserConfig::getDB();

		$dailyregs = array();

		if ($stmt = $db->prepare('SELECT CAST(regtime AS DATE) AS regdate, count(*) AS regs FROM '.UserConfig::$mysql_prefix.'users WHERE username IS NOT NULL GROUP BY regdate'))
		{
			if (!$stmt->execute())
			{
				throw new DBExecuteStmtException($db, $stmt);
			}
			if (!$stmt->bind_result($regdate, $regs))
			{
				throw new DBBindResultException($db, $stmt);
			}

			while($stmt->fetch() === TRUE)
			{
				$dailyregs[] = array('regdate' => $regdate, 'regs' => $regs);
			}

			$stmt->close();
		}
		else
		{
			throw new DBPrepareStmtException($db);
		}

		return $dailyregs;
	}

	public function renderLoginForm($action)
	{
		$slug = $this->getID();
		?>
		<form class="form-horizontal" action="<?php echo $action; ?>" method="POST">
			<fieldset>
				<legend>Enter your username and password to log in</legend>

				<div class="control-group<?php if (array_key_exists('username', $errors)) { ?> error" title="<?php echo UserTools::escape(implode("\n", $errors['username'])) ?><?php } ?>">
					<label class="control-label" for="startupapi-<?php echo $slug ?>-login-username">Username</label>
					<div class="controls">
						<input id="startupapi-<?php echo $slug ?>-login-username" name="username" type="text" size="25" maxlength="25"/>
					</div>
				</div>

				<div<?php if (UserConfig::$allowRememberMe) {?> style="margin-bottom: 0.5em"<?php } ?> class="control-group<?php if (array_key_exists('pass', $errors)) { ?> error" title="<?php echo UserTools::escape(implode("\n", $errors['pass'])) ?><?php } ?>">
					<label class="control-label" for="startupapi-<?php echo $slug ?>-login-pass">Password</label>
					<div class="controls">
						<input id="startupapi-<?php echo $slug ?>-login-pass" name="pass" type="password" autocomplete="off"/>
						<a id="startupapi-usernamepass-login-forgotpass" href="<?php echo UserConfig::$USERSROOTURL?>/modules/usernamepass/forgotpassword.php">Forgot password?</a></li>
					</div>
				</div>

				<?php if (UserConfig::$allowRememberMe) {?>
				<div style="margin-bottom: 0.5em" class="control-group">
					<div class="controls">
						<label class="checkbox">
							<input type="checkbox" name="remember" value="yes"<?php if (UserConfig::$rememberMeDefault) {?> checked<?php }?>/>
							remember me
						</label>
					</div>
				</div>
				<?php }?>

				<div class="control-group">
					<div class="controls">
						<button class="btn btn-primary" type="submit" name="login">Login</button>
						<?php if (UserConfig::$enableRegistration) {?><a class="btn" href="<?php echo UserConfig::$USERSROOTURL?>/register.php">or sign up here</a><?php } ?>
					</div>
				</div>
			</fieldset>
		</form>
		<?php
	}

	public function renderRegistrationForm($full = false, $action = null, $errors = null, $data = null)
	{
		$slug = $this->getID();
		?>
		<form class="form-horizontal" action="<?php echo $action; ?>" method="POST">
			<fieldset>
				<legend>Enter your information to create an account</legend>

				<div class="control-group<?php if (array_key_exists('username', $errors)) { ?> error" title="<?php echo UserTools::escape(implode("\n", $errors['username'])) ?><?php } ?>">
					<label class="control-label" for="startupapi-<?php echo $slug ?>-registration-username">Username</label>
					<div class="controls">
						<input id="startupapi-<?php echo $slug ?>-registration-username" name="username" type="text" size="25" maxlength="25" value="<?php echo array_key_exists('username', $data) ? UserTools::escape($data['username']) : '' ?>"/>
					</div>
				</div>

				<div class="control-group<?php if (array_key_exists('pass', $errors)) { ?> error" title="<?php echo UserTools::escape(implode("\n", $errors['pass'])) ?><?php } ?>">
					<label class="control-label" for="startupapi-<?php echo $slug ?>-registration-pass">Set a password</label>
					<div class="controls">
						<input id="startupapi-<?php echo $slug ?>-registration-pass" name="pass" type="password" autocomplete="off"/>
					</div>
				</div>

				<div class="control-group<?php if (array_key_exists('repeatpass', $errors)) { ?> error" title="<?php echo UserTools::escape(implode("\n", $errors['repeatpass'])) ?><?php } ?>">
					<label class="control-label" for="startupapi-<?php echo $slug ?>-registration-repeatpass">Repeat new password</label>
					<div class="controls">
						<input id="startupapi-<?php echo $slug ?>-registration-repeatpass" name="repeatpass" type="password" autocomplete="off"/>
					</div>
				</div>

				<div class="control-group<?php if (array_key_exists('name', $errors)) { ?> error" title="<?php echo UserTools::escape(implode("\n", $errors['name'])) ?><?php } ?>">
					<label class="control-label" for="startupapi-<?php echo $slug ?>-registration-name">Name</label>
					<div class="controls">
						<input id="startupapi-<?php echo $slug ?>-registration-name" name="name" type="text" value="<?php echo UserTools::escape(array_key_exists('name', $data) ? $data['name'] : '') ?>"/>
					</div>
				</div>

				<div class="control-group<?php if (array_key_exists('email', $errors)) { ?> error" title="<?php echo UserTools::escape(implode("\n", $errors['email'])) ?><?php } ?>">
					<label class="control-label" for="startupapi-<?php echo $slug ?>-registration-email">Email</label>
					<div class="controls">
						<input id="startupapi-<?php echo $slug ?>-registration-email" name="email" type="email" value="<?php echo UserTools::escape(array_key_exists('email', $data) ? $data['email'] : '') ?>"/>
					</div>
				</div>

				<?php
				if (!is_null(UserConfig::$currentTOSVersion) && is_callable(UserConfig::$onRenderTOSLinks)) {
					?>
					<div  style="margin-bottom: 0" class="control-group">
						<div class="controls">
						<?php
						call_user_func(UserConfig::$onRenderTOSLinks);
						?>
						</div>
					</div>
					<?php
				}
				?>

				<div class="control-group">
					<div class="controls">
						<button class="btn btn-primary" type="submit" name="register">Register</button> <a class="btn" href="<?php echo UserConfig::$USERSROOTURL ?>/login.php">or login here</a>
					</div>
				</div>
			</fieldset>
		</form>
		<?php
	}

	public function renderEditUserForm($action, $errors, $user, $data)
	{
		$slug = $this->getID();
		?>
			<form class="form-horizontal" action="<?php echo $action; ?>" method="POST">
				<fieldset>
					<legend>Update your name, email and password</legend>
					<div class="control-group<?php if (array_key_exists('username', $errors)) { ?> error" title="<?php echo UserTools::escape(implode("\n", $errors['username'])) ?><?php } ?>">
						<label class="control-label" for="startupapi-<?php echo $slug ?>-edit-username">Username</label>
						<div class="controls">
							<?php
							$username = $user->getUsername();

							if (is_null($username)) {
								?>
								<input id="username" name="startupapi-<?php echo $slug ?>-edit-username" type="text" maxlength="25" value="<?php echo array_key_exists('username', $data) ? UserTools::escape($data['username']) : '' ?>"/>
								<?php
							} else {
								?>
								<input disabled="disabled" title="Sorry, you can't change your username" value="<?php echo UserTools::escape($username) ?>"/>
							<?php } ?>
						</div>
					</div>
					<legend>Name and email</legend>

					<div class="control-group<?php if (array_key_exists('name', $errors)) { ?> error" title="<?php echo UserTools::escape(implode("\n", $errors['name'])) ?><?php } ?>">
						<label class="control-label" for="startupapi-<?php echo $slug ?>-edit-name">Name</label>
						<div class="controls">
							<input id="startupapi-<?php echo $slug ?>-edit-name" name="name" type="text" value="<?php echo UserTools::escape(array_key_exists('name', $data) ? $data['name'] : $user->getName()) ?>"/>
						</div>
					</div>

					<div class="control-group<?php if (array_key_exists('email', $errors)) { ?> error" title="<?php echo UserTools::escape(implode("\n", $errors['email'])) ?><?php } ?>">
						<label class="control-label" for="startupapi-<?php echo $slug ?>-edit-email">Email</label>
						<div class="controls">
							<input id="startupapi-<?php echo $slug ?>-edit-email" name="email" type="email" value="<?php echo UserTools::escape(array_key_exists('email', $data) ? $data['email'] : $user->getEmail()) ?>"/>
							<?php if ($user->getEmail() && !$user->isEmailVerified()) { ?><a id="startupapi-usernamepass-edit-verify-email" href="<?php echo UserConfig::$USERSROOTURL ?>/verify_email.php">Email address is not verified yet, click here to verify</a><?php } ?>
						</div>
					</div>

					<legend>Change password</legend>

					<?php if (!is_null($user->getUsername())) { ?>
						<div class="control-group<?php if (array_key_exists('currentpass', $errors)) { ?> error" title="<?php echo UserTools::escape(implode("\n", $errors['currentpass'])) ?><?php } ?>">
							<label class="control-label" for="startupapi-<?php echo $slug ?>-edit-currentpass">Current password</label>
							<div class="controls">
								<input id="startupapi-<?php echo $slug ?>-edit-currentpass" name="currentpass" type="password" autocomplete="off"/>
							</div>
						</div>
					<?php } ?>
					<div class="control-group<?php if (array_key_exists('pass', $errors)) { ?> error" title="<?php echo UserTools::escape(implode("\n", $errors['pass'])) ?><?php } ?>">
						<label class="control-label" for="startupapi-<?php echo $slug ?>-edit-pass"><?php if (is_null($user->getUsername())) { ?>Set a<?php } else { ?>New<?php } ?> password</label>
						<div class="controls">
							<input id="startupapi-<?php echo $slug ?>-edit-pass" name="pass" type="password" autocomplete="off"/>
						</div>
					</div>
					<div class="control-group<?php if (array_key_exists('repeatpass', $errors)) { ?> error" title="<?php echo UserTools::escape(implode("\n", $errors['repeatpass'])) ?><?php } ?>">
						<label class="control-label" for="startupapi-<?php echo $slug ?>-edit-repeatpass">Repeat new password</label>
						<div class="controls">
							<input id="startupapi-<?php echo $slug ?>-edit-repeatpass" name="repeatpass" type="password" autocomplete="off"/>
						</div>
					</div>

					<div class="control-group">
						<div class="controls">
							<button class="btn" type="submit" name="save">Save changes</button>
						</div>
					</div>
				</fieldset>
				<?php UserTools::renderCSRFNonce(); ?>
			</form>
		<?php
	}

	public function processLogin($data, &$remember)
	{
		$remember = UserConfig::$allowRememberMe && array_key_exists('remember', $data);

		$db = UserConfig::getDB();

		$user = User::getUserByUsernamePassword($data['username'], $data['pass']);

		if (!is_null($user))
		{
			$user->recordActivity(USERBASE_ACTIVITY_LOGIN_UPASS);
		}

		return $user;
	}

	public function processRegistration($data, &$remember)
	{
		$remember = UserConfig::$allowRememberMe && UserConfig::$rememberUserOnRegistration;

		$errors = array();
		if (array_key_exists('pass', $data) && array_key_exists('repeatpass', $data) && $data['pass'] !== $data['repeatpass'])
		{
			$errors['repeatpass'][] = 'Passwords don\'t match';
		}

		if (array_key_exists('pass', $data) && strlen($data['pass']) < 6)
		{
			$errors['pass'][] = 'Passwords must be at least 6 characters long';
		}

		if (array_key_exists('username', $data))
		{
			$username = strtolower(trim(mb_convert_encoding($data['username'], 'UTF-8')));

			if (strlen($username) < 2)
			{
				$errors['username'][] = 'Username must be at least 2 characters long';
			}

			if (strlen($username) > 25)
			{
				$errors['username'][] = 'Username must be no more then 25 characters long';
			}

			if (preg_match('/^[a-z][a-z0-9.]*[a-z0-9]$/', $username) !== 1)
			{
				$errors['username'][] = "Username must start with the letter and contain only latin letters, digits or '.' symbols";

			}
		}
		else
		{
			$errors['username'][] = "No username passed";
		}

		if (array_key_exists('name', $data))
		{
			$name = trim(mb_convert_encoding($data['name'], 'UTF-8'));
			if ($name == '')
			{
				$errors['name'][] = "Name can't be empty";
			}
		}
		else
		{
			$errors['name'][] = 'No name specified';
		}

		if (array_key_exists('email', $data))
		{
			$email = trim(mb_convert_encoding($data['email'], 'UTF-8'));
			if (filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE)
			{
				$errors['email'][] = 'Invalid email address';
			}
		}
		else
		{
			$errors['email'][] = 'No email specified';
		}

		if (count($errors) > 0)
		{
			throw new InputValidationException('Validation failed', 0, $errors);
		}

		if (count(User::getUsersByEmailOrUsername($username)) > 0 ) {
			$errors['username'][] = "This username is already used, please pick another one";
		}
		if (count(User::getUsersByEmailOrUsername($email)) > 0 ) {
			$errors['email'][] = "This email is already used by another user, please enter another email address.";
		}

		if (count($errors) > 0)
		{
			throw new ExistingUserException('User already exists', 0, $errors);
		}

		// ok, let's create a user
		$user = User::createNew($name, $username, $email, $data['pass']);
		$user->recordActivity(USERBASE_ACTIVITY_REGISTER_UPASS);
		return $user;
	}

	/*
	 * Updates user information
	 *
	 * returns true if successful and false if unsuccessful
	 *
	 * throws InputValidationException if there are problems with input data
	 */
	public function processEditUser($user, $data)
	{
		$errors = array();

		$has_username = !is_null($user->getUsername());

		// only validate username if user didn't specify it yet
		if (!$has_username)
		{
			if (array_key_exists('username', $data))
			{
				$username = strtolower(trim(mb_convert_encoding($data['username'], 'UTF-8')));

				if (strlen($username) < 2)
				{
					$errors['username'][] = 'Username must be at least 2 characters long';
				}

				if (strlen($username) > 25)
				{
					$errors['username'][] = 'Username must be no more then 25 characters long';
				}

				if (preg_match('/^[a-z][a-z0-9.]*[a-z0-9]$/', $username) !== 1)
				{
					$errors['username'][] = "Username must start with the letter and contain only latin letters, digits or '.' symbols";
				}
			}
			else
			{
				$errors['username'][] = "No username passed";
			}
		}

		if (array_key_exists('name', $data))
		{
			$name = trim(mb_convert_encoding($data['name'], 'UTF-8'));
			if ($name == '')
			{
				$errors['name'][] = "Name can't be empty";
			}
		}
		else
		{
			$errors['name'][] = 'No name specified';
		}

		if (array_key_exists('email', $data))
		{
			$email = trim(mb_convert_encoding($data['email'], 'UTF-8'));
			if (filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE)
			{
				$errors['email'][] = 'Invalid email address';
			}
		}
		else
		{
			$errors['email'][] = 'No email specified';
		}

		if (!$has_username)
		{
			$existing_users = User::getUsersByEmailOrUsername($username);
			if (!array_key_exists('username', $errors) &&
				(count($existing_users) > 0 && !$existing_users[0]->isTheSameAs($user))
			) {
				$errors['username'][] = "This username is already used, please pick another one";
			}
		}

		$existing_users = User::getUsersByEmailOrUsername($email);
		if (!array_key_exists('email', $errors) &&
			(count($existing_users) > 0 && !$existing_users[0]->isTheSameAs($user))
		) {
			$errors['email'][] = "This email is already used by another user, please enter another email address.";
		}

		// don't change password if username was already set and no password fields are edited
		$changepass = false;

		// Force password setup when user sets username for the first time
		if (!$has_username)
		{
			$changepass = true;
		}
		else if (array_key_exists('currentpass', $data) &&
			array_key_exists('pass', $data) &&
			array_key_exists('repeatpass', $data) &&
			($data['currentpass'] != '' || $data['pass'] != '' || $data['repeatpass'] != ''))
		{
			$changepass = true;

			if (!$user->checkPass($data['currentpass']))
			{
				$errors['currentpass'][] = 'You entered wrong current password';
			}
		}

		if ($changepass)
		{
			// both passwords must be passed and non-empty
			if (array_key_exists('pass', $data) && array_key_exists('repeatpass', $data) &&
					($data['pass'] != '' || $data['repeatpass'] != '')
				)
			{
				if (strlen($data['pass']) < 6)
				{
					$errors['pass'][] = 'Passwords must be at least 6 characters long';
				}

				if ($data['pass'] !== $data['repeatpass'])
				{
					$errors['repeatpass'][] = 'Passwords don\'t match';
				}
			}
			else
			{
				if ($has_username)
				{
					$errors['pass'][] = 'You must specify new password';
				}
				else
				{
					$errors['pass'][] = 'You must set password when setting username and email';
				}
			}
		}

		if (count($errors) > 0)
		{
			throw new InputValidationException('Validation failed', 0, $errors);
		}

		if ($changepass)
		{
			$user->setPass($data['pass']);
			if ($has_username) {
				$user->recordActivity(USERBASE_ACTIVITY_UPDATEPASS);
			}
		}

		if (!$has_username)
		{
			$user->setUsername($username);
			$user->recordActivity(USERBASE_ACTIVITY_ADDED_UPASS);
		}

		$user->setName($name);
		$user->setEmail($email);
		$user->save();

		$user->recordActivity(USERBASE_ACTIVITY_UPDATEUSERINFO);

		return true;
	}

	/**
	 * Updates user's password
	 *
	 * @param User $user User object
	 * @param array $data Form data
	 *
	 * @return boolean True if password update was successful, false otherwise
	 *
	 * @throws InputValidationException
	 */
	public function processUpdatePassword($user, $data)
	{
		$errors = array();

		if (array_key_exists('pass', $data) ||
			array_key_exists('repeatpass', $data))
		{
			if (array_key_exists('pass', $data) && array_key_exists('repeatpass', $data) && $data['pass'] !== $data['repeatpass'])
			{
				$errors['repeatpass'] = 'Passwords don\'t match';
			}

			if (array_key_exists('pass', $data) && strlen($data['pass']) < 6)
			{
				$errors['pass'] = 'Passwords must be at least 6 characters long';
			}
		}
		else
		{
			$errors['pass'] = 'Passwords must be specified';
		}

		if (count($errors) > 0)
		{
			throw new InputValidationException('Validation failed', 0, $errors);
		}

		$user->setPass($data['pass']);
		$user->setRequiresPasswordReset(false);
		$user->save();

		$user->resetTemporaryPassword();

		$user->recordActivity(USERBASE_ACTIVITY_RESETPASS);

		return true;
	}

	/**
	 * Bypasses required password reset flag if set to true
	 *
	 * THIS SHOULD ONLY BE SET ON PASSWORD RESET PAGE
	 * SETTING THIS ON OTHER PAGES CAN RESULT IN SECURITY BREACH
	 *
	 * @var boolean
	 *
	 * @internal
	 */
	public static $IGNORE_PASSWORD_RESET = false;
}

/**
 * Username credentials
 *
 * @package StartupAPI
 * @subpackage Authentication\UsernamePassword
 */
class UsernamePassUserCredentials extends UserCredentials {
	/**
	 * @var string Username
	 */
	private $username;

	/**
	 * Creates Username credentials object
	 *
	 * @param type $username
	 */
	public function __construct($username) {
		$this->username = $username;
	}

	/**
	 * Returns user's username
	 *
	 * @return string Username
	 */
	public function getUsername() {
		return $this->username;
	}

	public function getHTML() {
		return $this->username;
	}
}
