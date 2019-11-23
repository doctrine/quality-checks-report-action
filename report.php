<?php

require_once 'vendor/autoload.php';

echo "Hello world!";

var_dump(array_keys($_SERVER));
var_dump($_SERVER['GITHUB_WORKSPACE']);
var_dump($_SERVER['GITHUB_BASEREF']);
var_dump($_SERVER['GITHUB_HEADREF']);

echo file_get_contents("/tmp/phpcs.xml");

function get_base_git_branch() : string
{
    return $_SERVER['GITHUB_BASEREF'];
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
        escapeshellarg($_SERVER['GITHUB_HEADREF']),
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

generate_diff_to_base();

echo file_get_contents("/tmp/base.diff");
