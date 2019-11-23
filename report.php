<?php

require_once '/app/vendor/autoload.php';

echo "Hello world!";

$githubVars = [];
foreach ($_SERVER as $k => $v) {
    if (strpos($k, 'GITHUB_') === 0) {
        $githubVars[$k] = $v;
    }
}
var_dump($githubVars);

echo file_get_contents("/tmp/phpcs.xml");

function get_base_git_branch() : string
{
    if (strlen($_SERVER['GITHUB_BASE_REF'] ?? '') > 0) {
        return $_SERVER['GITHUB_BASE_REF'];
    }

    return str_replace("refs/heads/", "", $_SERVER['GITHUB_REF']) . "...";
}

function get_head_git_ref() : string
{
    if (strlen($_SERVER['GITHUB_HEAD_REF'] ?? '') > 0) {
        return $_SERVER['GITHUB_HEAD_REF'];
    }

    return $_SERVER['GITHUB_SHA'];
}

function generate_diff_to_base($repositoryRoot) : void
{
    chdir($repositoryRoot);
    $base = get_base_git_branch();
    // we do not want to diff with current master/PR-base, but with
    // the common ancestor of this commit and master/PR-base
    $cmd = sprintf(
        '(git diff $(git merge-base %s %s) > /tmp/base.diff)',
        escapeshellarg($base),
        escapeshellarg(get_head_git_ref()),
    );
    shell_exec($cmd);
}
function calculate_changed_violation_lines(string $file) : array
{
    $diff = new \exussum12\CoverageChecker\DiffFileLoader('/tmp/base.diff');
    $loader = new \exussum12\CoverageChecker\CheckstyleLoader($file);
    $matcher = new \exussum12\CoverageChecker\FileMatchers\EndsWith();
    $coverageCheck = new \exussum12\CoverageChecker\CoverageCheck($diff, $loader, $matcher);

    $lines = $coverageCheck->getCoveredLines();

    return $lines['uncoveredLines'];
}

generate_diff_to_base($_SERVER['PWD']);

echo file_get_contents("/tmp/base.diff");

var_dump(calculate_changed_violation_lines("/tmp/phpcs.xml"));
