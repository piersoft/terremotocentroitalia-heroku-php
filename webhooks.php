<?php
	die("To be fixed.");

	define('GITHUB_REPO', 'git@github.com:emergenzeHack/terremotocentro.git');
	define('GITLAB_REPO', 'https://' . getenv('GITLAB_AUTH') . '@gitlab.com/emergenzeHack/terremotocentro');

	$token = getenv('WEBHOOKS_TOKEN');
	if (!$token || $_GET['token'] != $token || ($_GET['type'] != 'csv' && $_GET['type'] != 'sync')) {
		header("HTTP/1.1 401 Unauthorized");
		exit;
	}
	$ssh_key = getenv('GITHUB_TERREMOTOCENTRO_SSH_KEY');
	$home = getenv('HOME');
	mkdir($home . '/.ssh', 0700);
	file_put_contents($home . '/.ssh/id_rsa', $ssh_key);
	file_put_contents($home . '/.ssh/config', "Host github.com\n\tStrictHostKeyChecking no\n");
	chmod($home . '/.ssh/id_rsa', 0600);

	exec('rm -rf /tmp/terremotocentro');
	if ($_GET['type'] == 'csv') {
		passthru('git clone ' . GITHUB_REPO . ' /tmp/terremotocentro 2>&1');
		chdir('/tmp/terremotocentro');
		passthru('git config user.name terremotocentro && git config user.email terremotocentroita@gmail.com');
		passthru('bash scripts/csvupdate.sh 2>&1');
	} elseif ($_GET['type'] == 'sync') {
		passthru('git clone --mirror ' . GITHUB_REPO . ' /tmp/terremotocentro 2>&1');
		chdir('/tmp/terremotocentro');
		passthru('git push --all ' . GITLAB_REPO . ' 2>&1');
	}
	exec('rm -rf /tmp/terremotocentro');
?>
