<?php

require_once '/app/vendor/autoload.php';

function get_base_git_branch() : string
{
    if (strlen($_SERVER['GITHUB_BASE_REF'] ?? '') > 0) {
        // This env variable is non empty on pull_request events
        // Github Actions only has branches available under `remotes/origin`.
        return "remotes/origin/" . $_SERVER['GITHUB_BASE_REF'];
    }

    return str_replace("refs/heads/", "remotes/origin/", $_SERVER['GITHUB_REF']) . "...";
}

function get_head_git_ref() : string
{
    if (strlen($_SERVER['GITHUB_HEAD_REF'] ?? '') > 0) {
        // This env variable is non empty on pull_request events
        // Github Actions only has branches available under `remotes/origin`.
        return "remotes/origin/" . $_SERVER['GITHUB_HEAD_REF'];
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

$githubToken = $_SERVER['GITHUB_TOKEN'];
$repo = $_SERVER['GITHUB_REPOSITORY'];

$tools = ['phpcs'];
$bufferByJob = [];
$renderedFailuresByJob = [];

$checkstyleParser = new \Doctrine\GithubActions\CheckstyleParser();

foreach ($tools as $tool) {
    $changedViolations = (calculate_changed_violation_lines("/tmp/" . $tool . ".xml"));
    $violations = $checkstyleParser->parseFile('/tmp/' . $tool . '.xml', $_SERVER['PWD']);

    if (count($violations) > 0) {
        $annotations = [];
        $buffer = "";

        foreach ($violations as $failure) {
            if (isset($changedViolations[$failure['file']][$failure['line']])) {
                printf("[%s] %s:%d - %s\n", $tool, $failure['file'], $failure['line'], $failure['body']);

                $buffer .= "<details>\n";
                $buffer .= "<summary><code>{$failure['name']} in {$failure['class']} {$failure['file']}:{$failure['line']}</code></summary>\n\n";

                if (strlen($failure['body']) > 0) {
                    $buffer .= "<code><pre class=\"term\">{$failure['body']}</pre></code>\n\n";
                }

                $buffer .= "in <a href=\"##{$failure['job']}\">Job {$failure['job']}</a>\n";
                $buffer .= "</details>";
                $buffer .= "\n\n\n";

                if (count($annotations) >= 50) {
                    continue;
                }

                $annotations[] = [
                    'path' => $file,
                    'start_line' => $failure['line'],
                    'end_line' => $failure['line'],
                    'annotation_level' => $failure['type'],
                    'raw_details' => substr($failure['body'], 0, 64 * 1024),
                    'message' => "{$failure['name']} in {$failure['class']}: " . substr($failure['body'], 0, 128),
                ];
            }
        }

        $titlesByTool = [
            'phpcs' => 'PHP Code Sniffer',
            'phan' => 'Phan Static Analyzer',
            'phpstan' => 'PHP Stan',
            'psalm' => 'Psalm Static Analyzer',
        ];

        $ch = curl_init("https://api.github.com/repos/{$repo}/check-runs");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Accept: application/vnd.github.antiope-preview+json',
                'Authorization: Bearer ' . $githubToken,
                'User-Agent: https://github.com/doctrine/quality-checks-report-action',
            ]
        );

        $data = json_encode([
            'name' => 'doctrine-' . $tool,
            'head_sha' => $_SERVER['GITHUB_SHA'],
            'status' => 'completed',
            'conclusion' => count($violations) === 0 ? 'success' : 'failure',
            'completed_at' => gmdate('Y-m-d') . 'T' . gmdate('H:i:s') . 'Z',
            'output' => [
                'title' => $titlesByTool[$tool] . ' Report',
                'summary' => "There are " . count($violations) . " errors.",
                'text' => $buffer,
                'annotations' => $annotations,
            ],
        ], JSON_PARTIAL_OUTPUT_ON_ERROR);

        if (!$data) {
            fwrite(STDERR, sprintf("--- :warning: :github: Error for job %s - json_encode failed with json_last_error() == %s\n", $job, json_last_error_msg()));

            continue;
        }

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);

        if ($info['http_code'] >= 400) {
            fwrite(STDERR, sprintf("--- :warning: :github: Error for job %s [%d] %s\n", $job, $info['http_code'], $response));
            fwrite(STDERR, sprintf("data sent:\n%s\n", $data));

            continue;
        }
    }
}

echo "all check runners OK!\n";
