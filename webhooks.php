<?php
	define('GITHUB_REPO', 'https://' . getenv('GITHUB_USERNAME') . ':' . getenv('GITHUB_PASSWORD') . '@github.com/emergenzeHack/terremotocentro');
	define('GITLAB_REPO', 'https://' . getenv('GITLAB_AUTH') . '@gitlab.com/emergenzeHack/terremotocentro');

	function is_empty_dir($dir) {
		return (($files = @scandir($dir)) && count($files) <= 2);
	}

	function exec_log($cmd) {
		exec($cmd, $out, $ret);
		error_log(join('\n', $out));
		return !$ret;
	}

	function cleanup() {
		rmdir('/tmp/terremotocentro.lock');
	}

	$token = getenv('WEBHOOKS_TOKEN');
//	if (!$token || $_GET['token'] != $token || ($_GET['type'] != 'csv' && $_GET['type'] != 'sync')) { FIXME
	if (!$token || $_GET['token'] != $token || $_GET['type'] != 'issue') {
		exit;
	}

	$i = 0;
	// Poor man™ locking (funziona solo se hai un worker, come nel caso di Heroku free)
	while (!@mkdir('/tmp/terremotocentro.lock')) {
		sleep(1);
		$i++;
		if ($i && ($i % 5) == 0)
			error_log("Locked for $i seconds");
	}
	register_shutdown_function('cleanup');

	if (is_dir('/tmp/terremotocentro') && !is_empty_dir('/tmp/terremotocentro')) {
		chdir('/tmp/terremotocentro');
		exec_log('git fetch origin master && git reset --hard origin/master');
	} else {
		exec_log('git clone ' . GITHUB_REPO . ' /tmp/terremotocentro');
		chdir('/tmp/terremotocentro');
		exec_log('git config user.name terremotocentro && git config user.email terremotocentroita@gmail.com');
	}
	// Scarico le pagine da Google Spreadsheet e le converto in CSV
	if ($_GET['type'] == 'csv') {
		exec_log('bash scripts/csvupdate.sh');
	// Scarico le issue da GitHub e le converto in CSV
	} elseif ($_GET['type'] == 'issue') {
		exec_log('python2 scripts/github2CSV.py _data/issues.csv && sed -i \'s/\r$//g\' _data/issues.csv') or exit(0);
		exec_log('git add _data/issues.csv');
		exec_log('git commit -m "auto issues CSV update ' . date('c') . '"');
		exec_log('git push origin master');
	// Sincronizzo GitLab con GitHub
	} elseif ($_GET['type'] == 'sync') {
		chdir('/tmp/terremotocentro');
		exec_log('git push ' . GITLAB_REPO . ' master:master');
	}
?>
