<?php

define ("REPO", getenv("TRAVIS_REPO_SLUG"));
define ("REPO_NAME", explode("/", REPO)[1]);
define ("SCRIPT_DIR", __DIR__);
define ("WIKI_DIR", SCRIPT_DIR . "/../" . REPO_NAME . ".wiki");
define ("WIKI_FILE", WIKI_DIR . "/Home.md");
define ("README_FILE", SCRIPT_DIR . "/../README.md");
define ("VERSION_FILE", SCRIPT_DIR . "/../SHOPVERSIONS");
define ("TRAVIS_FILE", SCRIPT_DIR . "/../.travis.yml");
define ("CHANGELOG_FILE", SCRIPT_DIR . "/../CHANGELOG.md");

// Update this if you're using a different shop system.
require SCRIPT_DIR . "/../vendor/autoload.php";

use Symfony\Component\Yaml\Yaml;

/**
 * Maps over the configured PHP versions and prefixes them
 *
 * @param $version
 * @return string
 */
function prefixWithPhp($version) {
    return "PHP " . number_format($version, 1);
}

/**
 * Joins an array with commas and a conjunction before the last item
 * (e.g. "x, y and z")
 *
 * @param $list
 * @param string $conjunction
 * @return string
 */
function naturalLanguageJoin($list, $conjunction = 'and') {
    $last = array_pop($list);

    if ($list) {
        return implode(', ', $list) . ' ' . $conjunction . ' ' . $last;
    }

    return $last;
}

/**
 * Wraps each line of the changelog in proper formatting.
 *
 * @param $change
 * @return string
 */
function generateChangelogLine($change) {
    return "<li>{$change}</li>";
}

/**
 * Generates the necessary version string for the compatible shop versions and PHP versions.
 *
 * @param $shopVersions
 * @param $phpVersions
 * @return array
 */
function makeTextVersions($shopVersions, $phpVersions) {
    $versionRange = $shopVersions["tested"];
    $phpVersions = array_map("prefixWithPhp", $phpVersions);
    $phpVersionString = naturalLanguageJoin($phpVersions);

    // We don't need a from-to range if the versions are the same.
    if ($shopVersions["compatibility"] !== $shopVersions["tested"]) {
        $versionRange = $shopVersions["compatibility"] . " - " . $shopVersions["tested"];
    }

    return [
        "versionRange" => $versionRange,
        "phpVersionString" => $phpVersionString
    ];
}

/**
 * Generates the text for the release notes on GitHub
 *
 * @param $shopVersions
 * @param $phpVersions
 * @return string
 */
function generateReleaseVersions($shopVersions, $phpVersions) {
    $releaseVersions = makeTextVersions($shopVersions, $phpVersions);

    $releaseNotes  = "<ul>" . join("", array_map("generateChangelogLine", $shopVersions['changelog'])) . "</ul>";
    $releaseNotes .= "<em><strong>Tested version(s):</strong> {$shopVersions['shopsystem']} {$shopVersions['tested']} with {$releaseVersions['phpVersionString']}</em><br>";
    $releaseNotes .= "<em><strong>Compatibility:</strong> {$shopVersions['shopsystem']} {$releaseVersions['versionRange']} with {$releaseVersions['phpVersionString']}</em>";

    return $releaseNotes;
}

/**
 * Updates the compatibility versions and release date on the home page of the repository wiki
 * (NOTE: This function directly manipulates the necessary file)
 *
 * @param $shopVersions
 * @param $phpVersions
 */
function generateWikiRelease($shopVersions, $phpVersions) {
    if (!file_exists(WIKI_FILE )) {
        fwrite(STDERR, "ERROR: Wiki files do not exist." . PHP_EOL);
        exit(1);
    }

    $wikiPage = file_get_contents(WIKI_FILE);
    $releaseDate = date("Y-m-d");
    $releaseVersions = makeTextVersions($shopVersions, $phpVersions);

    // Matching all the replaceable table rows.
    // The format is | **<string>** | <content> |
    $testedRegex = "/^\|\s?\*.?Tested.*\|(.*)\|/mi";
    $compatibilityRegex = "/^\|\s?\*.?Compatibility.*\|(.*)\|/mi";
    $extVersionRegex = "/^\|\s?\*.?Extension.*\|(.*)\|/mi";

    $testedReplace = "| **Tested version(s):** | {$shopVersions['shopsystem']} {$shopVersions['tested']} with {$releaseVersions['phpVersionString']} |";
    $compatibilityReplace = "| **Compatibility:** | {$shopVersions['shopsystem']} {$releaseVersions['versionRange']} with {$releaseVersions['phpVersionString']} |";
    $extVersionReplace = "| **Extension version** | ![Release](https://img.shields.io/github/release/" . REPO . ".png?nolink \"Release\") ({$releaseDate}), [change log](https://github.com/" . REPO . "/releases) |";

    $wikiPage = preg_replace($testedRegex, $testedReplace, $wikiPage);
    $wikiPage = preg_replace($compatibilityRegex, $compatibilityReplace, $wikiPage);
    $wikiPage = preg_replace($extVersionRegex, $extVersionReplace, $wikiPage);

    file_put_contents(WIKI_FILE, $wikiPage);
}

/**
 * Updates the README badge to use the latest shop version we're compatible with.
 * (NOTE: This function directly manipulates the necessary file)
 *
 * @param $shopVersions
 */
function generateReadmeReleaseBadge($shopVersions) {
    if (!file_exists(README_FILE )) {
        fwrite(STDERR, "ERROR: README file does not exist." . PHP_EOL);
        exit(1);
    }

    $readmeContent = file_get_contents(README_FILE);

    $shopBadge = $shopVersions['shopsystem'] . " v" . $shopVersions['tested'];
    $shopBadgeUrl = str_replace(" ", "-", $shopBadge);

    // We're matching the image tag in Markdown. [![Shopsytem v1.2.3] ... ]
    $badgeRegex = "/\[\!\[{$shopVersions['shopsystem']}.*\]/mi";
    $badgeReplace = "[![{$shopBadge}](https://img.shields.io/badge/{$shopBadgeUrl}-green.svg)]";

    $readmeContent = preg_replace($badgeRegex, $badgeReplace, $readmeContent);

    file_put_contents(README_FILE, $readmeContent);
}

/**
 * Loads and parses the versions file.
 *
 * @param $filePath
 * @return array
 */

function parseVersionsFile($filePath) {
    // Bail out if we don"t have defined shop versions and throw a loud error.
    if (!file_exists($filePath)) {
        fwrite(STDERR, "ERROR: No shop version file exists" . PHP_EOL);
        exit(1);
    }

    // Load the file and parse json out of it
    $json = json_decode(
        file_get_contents(VERSION_FILE)
    );

    // compare release versions
    $cmp = function($a, $b) {
        return version_compare($a->release, $b->release);
    };

    // if file contains an array of versions return the latest
    if (is_array($json)) {
        uasort($json, $cmp);
        return (array)end($json);
    } else {
        return (array) $json;
    }
}

$shopVersions = parseVersionsFile(VERSION_FILE);

// Grab the Travis config for parsing the supported PHP versions
$travisConfig = Yaml::parse(file_get_contents(TRAVIS_FILE));
$phpVersions = $travisConfig['php'];

// Get the arguments passed to the command line script.
$options = getopt('wr');

// The indication of a command line argument being passed is an entry in the array with a "false" value.
// So instead we check if the key exists in the array.

// If we get -w passed, we're doing a wiki update.
if (key_exists('w', $options)) {
    generateWikiRelease($shopVersions, $phpVersions);
    exit(0);
}

// If -r is passed, that's for the badge in the README
if (key_exists('r', $options)) {
    generateReadmeReleaseBadge($shopVersions);
    exit(0);
}

// Otherwise just output the release notes, the rest will be handled by Travis
echo generateReleaseVersions($shopVersions, $phpVersions);